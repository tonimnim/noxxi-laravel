<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\RefundRequest;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RefundService
{
    protected PaystackService $paystackService;

    protected NotificationService $notificationService;

    public function __construct(
        PaystackService $paystackService,
        NotificationService $notificationService
    ) {
        $this->paystackService = $paystackService;
        $this->notificationService = $notificationService;
    }

    /**
     * Process a refund request
     */
    public function processRefund(RefundRequest $refundRequest): array
    {
        $booking = $refundRequest->booking;
        $originalTransaction = $booking->paymentTransaction;

        if (! $originalTransaction) {
            throw new \Exception('No payment transaction found for this booking');
        }

        DB::beginTransaction();

        try {
            // Update refund request status
            $refundRequest->update([
                'status' => 'processing',
                'processed_at' => now(),
            ]);

            // Process refund based on payment gateway
            $refundResult = $this->processGatewayRefund(
                $originalTransaction,
                $refundRequest->requested_amount,
                $refundRequest->reason
            );

            if ($refundResult['success']) {
                // Create refund transaction
                $refundTransaction = Transaction::create([
                    'id' => Str::uuid(),
                    'type' => Transaction::TYPE_REFUND,
                    'booking_id' => $booking->id,
                    'refund_request_id' => $refundRequest->id,
                    'organizer_id' => $booking->event->organizer_id,
                    'user_id' => $booking->user_id,
                    'amount' => $refundRequest->requested_amount,
                    'currency' => $booking->currency,
                    'payment_gateway' => $originalTransaction->payment_gateway,
                    'payment_method' => $originalTransaction->payment_method,
                    'payment_reference' => $refundResult['reference'],
                    'gateway_reference' => $refundResult['gateway_reference'] ?? null,
                    'status' => Transaction::STATUS_COMPLETED,
                    'metadata' => [
                        'original_transaction_id' => $originalTransaction->id,
                        'refund_reason' => $refundRequest->reason,
                        'gateway_response' => $refundResult['response'] ?? null,
                    ],
                ]);

                // Update refund request
                $refundRequest->update([
                    'status' => 'processed',
                    'transaction_id' => $refundTransaction->id,
                    'processed_amount' => $refundRequest->requested_amount,
                    'gateway_response' => $refundResult['response'] ?? null,
                    'admin_notes' => 'Refund processed successfully',
                ]);

                // Update booking status
                if ($refundRequest->requested_amount >= $booking->total_amount) {
                    // Full refund - cancel booking and tickets
                    $booking->update([
                        'status' => 'refunded',
                        'payment_status' => 'refunded',
                        'refunded_at' => now(),
                    ]);

                    // Cancel all tickets
                    $booking->tickets()->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                        'cancelled_reason' => 'Booking refunded',
                    ]);
                } else {
                    // Partial refund
                    $booking->update([
                        'payment_status' => 'partial_refund',
                    ]);
                }

                // Send notification
                $this->notificationService->sendRefundProcessed($refundRequest);

                DB::commit();

                Log::info('Refund processed successfully', [
                    'refund_request_id' => $refundRequest->id,
                    'amount' => $refundRequest->requested_amount,
                    'reference' => $refundResult['reference'],
                ]);

                return [
                    'success' => true,
                    'transaction_id' => $refundTransaction->id,
                    'reference' => $refundResult['reference'],
                    'message' => 'Refund processed successfully',
                ];

            } else {
                // Refund failed
                $refundRequest->update([
                    'status' => 'failed',
                    'admin_notes' => $refundResult['message'] ?? 'Refund failed at payment gateway',
                    'gateway_response' => $refundResult['response'] ?? null,
                ]);

                DB::commit();

                Log::error('Refund failed', [
                    'refund_request_id' => $refundRequest->id,
                    'error' => $refundResult['message'] ?? 'Unknown error',
                ]);

                return [
                    'success' => false,
                    'message' => $refundResult['message'] ?? 'Refund failed',
                ];
            }

        } catch (\Exception $e) {
            DB::rollback();

            // Update refund request with error
            $refundRequest->update([
                'status' => 'failed',
                'admin_notes' => 'Error: '.$e->getMessage(),
            ]);

            Log::error('Refund processing error', [
                'refund_request_id' => $refundRequest->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Process refund with payment gateway
     */
    protected function processGatewayRefund(
        Transaction $originalTransaction,
        float $amount,
        string $reason
    ): array {
        // All payments go through Paystack (including M-Pesa via mobile_money channel)
        if ($originalTransaction->payment_gateway === 'paystack') {
            return $this->processPaystackRefund($originalTransaction, $amount, $reason);
        }

        throw new \Exception('Unsupported payment gateway for refund: '.$originalTransaction->payment_gateway);
    }

    /**
     * Process Paystack refund
     */
    protected function processPaystackRefund(
        Transaction $originalTransaction,
        float $amount,
        string $reason
    ): array {
        try {
            // Convert amount to kobo (smallest unit)
            $amountInKobo = $amount * 100;

            // Call Paystack refund API
            $response = $this->paystackService->createRefund([
                'transaction' => $originalTransaction->gateway_reference ?? $originalTransaction->payment_reference,
                'amount' => $amountInKobo, // Amount in kobo
                'currency' => $originalTransaction->currency,
                'customer_note' => $reason,
                'merchant_note' => 'Refund requested by customer',
            ]);

            if ($response['status'] === true) {
                return [
                    'success' => true,
                    'reference' => $response['data']['id'] ?? Str::uuid(),
                    'gateway_reference' => $response['data']['id'] ?? null,
                    'response' => $response['data'] ?? null,
                ];
            }

            return [
                'success' => false,
                'message' => $response['message'] ?? 'Refund failed at Paystack',
                'response' => $response,
            ];

        } catch (\Exception $e) {
            Log::error('Paystack refund error', [
                'transaction_id' => $originalTransaction->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to process Paystack refund: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Calculate refund amount based on policy
     */
    public function calculateRefundAmount(Booking $booking): array
    {
        $event = $booking->event;
        $hoursUntilEvent = now()->diffInHours($event->event_date, false);
        $dayUntilEvent = ceil($hoursUntilEvent / 24);

        // Default refund policy (can be customized per event)
        $refundPercentage = 100;
        $serviceFeeRefundable = true;

        if ($hoursUntilEvent < 0) {
            // Event has passed
            $refundPercentage = 0;
        } elseif ($hoursUntilEvent <= 24) {
            // Within 24 hours - no refund
            $refundPercentage = 0;
        } elseif ($dayUntilEvent <= 3) {
            // 1-3 days before event - 50% refund
            $refundPercentage = 50;
            $serviceFeeRefundable = false;
        } elseif ($dayUntilEvent <= 7) {
            // 4-7 days before event - 75% refund
            $refundPercentage = 75;
            $serviceFeeRefundable = false;
        } else {
            // More than 7 days - full refund
            $refundPercentage = 100;
            $serviceFeeRefundable = true;
        }

        // Calculate amounts
        $ticketAmount = $booking->subtotal;
        $serviceFee = $booking->service_fee;

        $refundableTicketAmount = ($ticketAmount * $refundPercentage) / 100;
        $refundableServiceFee = $serviceFeeRefundable ? $serviceFee : 0;
        $totalRefundAmount = $refundableTicketAmount + $refundableServiceFee;

        return [
            'ticket_amount' => $ticketAmount,
            'service_fee' => $serviceFee,
            'refund_percentage' => $refundPercentage,
            'refundable_ticket_amount' => $refundableTicketAmount,
            'refundable_service_fee' => $refundableServiceFee,
            'total_refund_amount' => $totalRefundAmount,
            'days_until_event' => max(0, $dayUntilEvent),
            'policy_description' => $this->getRefundPolicyDescription($dayUntilEvent),
        ];
    }

    /**
     * Get refund policy description
     */
    protected function getRefundPolicyDescription(int $daysUntilEvent): string
    {
        if ($daysUntilEvent < 0) {
            return 'Event has passed - no refunds available';
        } elseif ($daysUntilEvent == 0) {
            return 'Event is today - no refunds available';
        } elseif ($daysUntilEvent <= 1) {
            return 'Within 24 hours of event - no refunds available';
        } elseif ($daysUntilEvent <= 3) {
            return '50% refund (service fee non-refundable)';
        } elseif ($daysUntilEvent <= 7) {
            return '75% refund (service fee non-refundable)';
        } else {
            return 'Full refund including service fee';
        }
    }

    /**
     * Validate if a refund request can be created (edge case handling)
     */
    public function validateRefundRequest(Booking $booking, User $user, float $requestedAmount): array
    {
        $errors = [];

        // Edge case: Check if booking belongs to user
        if ($booking->user_id !== $user->id) {
            $errors[] = 'This booking does not belong to you.';
        }

        // Edge case: Check if booking is paid
        if (! in_array($booking->payment_status, ['paid', 'partial_refund'])) {
            $errors[] = 'Only paid bookings can be refunded.';
        }

        // Edge case: Event has already occurred
        if ($booking->event->event_date < now()) {
            $errors[] = 'Cannot refund tickets for past events.';
        }

        // Edge case: Check for existing pending refund requests
        $existingRequest = RefundRequest::where('booking_id', $booking->id)
            ->whereIn('status', [
                RefundRequest::STATUS_PENDING,
                RefundRequest::STATUS_REVIEWING,
                RefundRequest::STATUS_APPROVED,
                'processing',
            ])
            ->first();

        if ($existingRequest) {
            $errors[] = 'A refund request is already pending for this booking.';
        }

        // Edge case: Check total refunded amount
        $totalRefunded = RefundRequest::where('booking_id', $booking->id)
            ->where('status', 'processed')
            ->sum('processed_amount');

        $remainingAmount = $booking->total_amount - $totalRefunded;

        if ($totalRefunded >= $booking->total_amount) {
            $errors[] = 'This booking has already been fully refunded.';
        }

        // Edge case: Validate requested amount
        if ($requestedAmount <= 0) {
            $errors[] = 'Refund amount must be greater than zero.';
        }

        if ($requestedAmount > $remainingAmount) {
            $errors[] = 'Refund amount cannot exceed the remaining booking amount of '.
                       $booking->currency.' '.number_format($remainingAmount, 2);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'max_refundable' => $remainingAmount,
            'total_refunded' => $totalRefunded,
        ];
    }

    /**
     * Handle concurrent refund requests (using database locking)
     */
    public function createRefundRequestWithLock(Booking $booking, User $user, array $data): ?RefundRequest
    {
        return DB::transaction(function () use ($booking, $user, $data) {
            // Lock the booking record to prevent concurrent requests
            $lockedBooking = Booking::where('id', $booking->id)->lockForUpdate()->first();

            // Re-validate with locked record
            $validation = $this->validateRefundRequest($lockedBooking, $user, $data['requested_amount']);

            if (! $validation['valid']) {
                throw new \Exception(implode(' ', $validation['errors']));
            }

            // Create refund request
            return RefundRequest::create([
                'booking_id' => $lockedBooking->id,
                'user_id' => $user->id,
                'reason' => $data['reason'],
                'requested_amount' => $data['requested_amount'],
                'currency' => $lockedBooking->currency,
                'status' => RefundRequest::STATUS_PENDING,
                'customer_message' => $data['customer_message'] ?? null,
            ]);
        });
    }

    /**
     * Check if organizer has sufficient balance for refund
     */
    public function checkOrganizerBalance(RefundRequest $refundRequest): bool
    {
        $organizer = $refundRequest->booking->event->organizer;
        $balanceService = app(AvailableBalanceService::class);
        $balance = $balanceService->getAvailableBalance($organizer);

        // Check if organizer has enough balance to cover the refund
        // This is important for platforms that deduct refunds from future payouts
        return $balance['available_balance'] >= $refundRequest->approved_amount;
    }

    /**
     * Handle insufficient balance scenario
     */
    public function handleInsufficientBalance(RefundRequest $refundRequest): void
    {
        // Put refund on hold
        $refundRequest->update([
            'status' => 'on_hold',
            'admin_notes' => 'Insufficient organizer balance. Refund will be processed when balance is available.',
        ]);

        // Notify organizer
        try {
            $this->notificationService->sendInsufficientBalanceNotification($refundRequest);
        } catch (\Exception $e) {
            Log::error('Failed to send insufficient balance notification', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Retry failed refunds
     */
    public function retryFailedRefund(RefundRequest $refundRequest): array
    {
        if ($refundRequest->status !== 'failed') {
            return [
                'success' => false,
                'message' => 'Only failed refunds can be retried.',
            ];
        }

        // Reset status to approved for retry
        $refundRequest->update([
            'status' => RefundRequest::STATUS_APPROVED,
            'admin_notes' => 'Retrying refund processing...',
        ]);

        // Process refund again
        return $this->processRefund($refundRequest);
    }

    /**
     * Handle partial refunds
     */
    public function processPartialRefund(Booking $booking, float $amount, string $reason): RefundRequest
    {
        // Validate partial refund amount
        $validation = $this->validateRefundRequest($booking, $booking->user, $amount);

        if (! $validation['valid']) {
            throw new \Exception(implode(' ', $validation['errors']));
        }

        // Create partial refund request
        $refundRequest = RefundRequest::create([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'reason' => $reason,
            'requested_amount' => $amount,
            'approved_amount' => $amount,
            'currency' => $booking->currency,
            'status' => RefundRequest::STATUS_APPROVED,
            'customer_message' => 'Partial refund requested',
            'admin_response' => 'Partial refund approved',
            'approved_at' => now(),
        ]);

        // Process the partial refund
        $this->processRefund($refundRequest);

        return $refundRequest;
    }
}
