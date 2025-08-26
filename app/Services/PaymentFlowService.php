<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentFlowService
{
    protected TransactionService $transactionService;

    protected PaystackService $paystackService;

    protected TicketService $ticketService;

    protected NotificationService $notificationService;

    protected FinancialCalculationService $financialService;

    public function __construct(
        TransactionService $transactionService,
        PaystackService $paystackService,
        TicketService $ticketService,
        NotificationService $notificationService,
        FinancialCalculationService $financialService
    ) {
        $this->transactionService = $transactionService;
        $this->paystackService = $paystackService;
        $this->ticketService = $ticketService;
        $this->notificationService = $notificationService;
        $this->financialService = $financialService;
    }

    /**
     * Initialize payment for a booking
     * Creates transaction and returns payment gateway URL
     */
    public function initializePayment(Booking $booking, string $paymentMethod): array
    {
        // Validate booking can be paid
        $validation = $this->validateBookingForPayment($booking);
        if (! $validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['message'],
                'errors' => $validation['errors'] ?? [],
            ];
        }

        // Check if transaction already exists for this booking
        $existingTransaction = Transaction::where('booking_id', $booking->id)
            ->whereIn('status', [Transaction::STATUS_PENDING, Transaction::STATUS_COMPLETED])
            ->first();

        if ($existingTransaction) {
            if ($existingTransaction->status === Transaction::STATUS_COMPLETED) {
                return [
                    'success' => false,
                    'message' => 'This booking has already been paid',
                ];
            }

            // Use existing pending transaction
            $transaction = $existingTransaction;
        } else {
            // Determine payment gateway
            $paymentGateway = $this->determinePaymentGateway($paymentMethod);

            // Create new transaction with proper financial tracking
            $transaction = $this->financialService->createFinancialTransaction(
                $booking,
                $paymentGateway,
                $paymentMethod,
                ['initiated_at' => now()]
            );
        }

        // Initialize with payment gateway
        try {
            $result = $this->initializeWithGateway($transaction, $booking, $paymentMethod);

            if (! $result['success']) {
                return $result;
            }

            // Update booking to show payment in progress
            $booking->update([
                'payment_status' => 'processing',
                'payment_method' => $paymentMethod,
            ]);

            return [
                'success' => true,
                'data' => [
                    'transaction_id' => $transaction->id,
                    'booking_id' => $booking->id,
                    'authorization_url' => $result['authorization_url'] ?? null,
                    'access_code' => $result['access_code'] ?? null,
                    'reference' => $result['reference'] ?? $transaction->payment_reference,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Payment initialization failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to initialize payment. Please try again.',
            ];
        }
    }

    /**
     * Validate booking can be paid
     */
    protected function validateBookingForPayment(Booking $booking): array
    {
        $errors = [];

        // Check booking status
        if ($booking->status !== 'pending') {
            $errors[] = "Booking is {$booking->status} and cannot be paid";
        }

        // Check if already paid
        if ($booking->payment_status === 'paid') {
            $errors[] = 'Booking is already paid';
        }

        // Check if booking has expired
        if ($booking->expires_at && $booking->expires_at < now()) {
            $errors[] = 'Booking has expired. Please create a new booking.';
        }

        // Check if event is still valid
        $event = $booking->event;
        if ($event->status !== 'published') {
            $errors[] = 'Event is no longer available for booking';
        }

        if ($event->event_date < now()) {
            $errors[] = 'Event has already passed';
        }

        return [
            'valid' => empty($errors),
            'message' => $errors[0] ?? null,
            'errors' => $errors,
        ];
    }

    /**
     * Determine payment gateway based on method
     */
    protected function determinePaymentGateway(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'card' => 'paystack',
            'mpesa' => 'paystack', // M-Pesa through Paystack
            'bank_transfer' => 'paystack',
            default => 'paystack',
        };
    }

    /**
     * Initialize payment with specific gateway
     */
    protected function initializeWithGateway(Transaction $transaction, Booking $booking, string $paymentMethod): array
    {
        // Generate unique reference if not exists
        if (! $transaction->payment_reference) {
            $transaction->payment_reference = $this->paystackService->generateReference('NXI');
            $transaction->save();
        }

        // Initialize with Paystack
        $result = $this->paystackService->initializeTransaction([
            'email' => $booking->customer_email ?? $booking->user->email,
            'amount' => $transaction->amount,
            'reference' => $transaction->payment_reference,
            'currency' => $transaction->currency,
            'callback_url' => config('services.paystack.callback_url'),
            'channels' => $this->getPaymentChannels($paymentMethod),
            'metadata' => [
                'transaction_id' => $transaction->id,
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'event_title' => $booking->event->title,
                'booking_reference' => $booking->booking_reference,
            ],
        ]);

        // Update transaction with gateway reference
        $transaction->update([
            'gateway_reference' => $result['access_code'] ?? null,
            'metadata' => array_merge($transaction->metadata ?? [], [
                'paystack_access_code' => $result['access_code'] ?? null,
                'paystack_reference' => $result['reference'] ?? null,
                'payment_method' => $paymentMethod,
            ]),
        ]);

        return [
            'success' => true,
            'authorization_url' => $result['authorization_url'],
            'access_code' => $result['access_code'],
            'reference' => $result['reference'],
        ];
    }

    /**
     * Get allowed payment channels for Paystack
     */
    protected function getPaymentChannels(string $paymentMethod): array
    {
        return match ($paymentMethod) {
            'card' => ['card'],
            'mpesa' => ['mobile_money'],
            'bank_transfer' => ['bank_transfer', 'bank'],
            default => ['card', 'bank', 'mobile_money'],
        };
    }

    /**
     * Process successful payment webhook
     */
    public function processSuccessfulPayment(Transaction $transaction, array $paymentData): bool
    {
        return DB::transaction(function () use ($transaction, $paymentData) {
            // Update transaction
            $this->transactionService->processPaystackPayment(
                $transaction,
                $paymentData['reference'] ?? $transaction->payment_reference,
                $paymentData
            );

            // Create tickets
            $this->ticketService->createTicketsForBooking($transaction->booking);

            // Send confirmation
            $this->notificationService->sendBookingConfirmation($transaction->booking);

            Log::info('Payment processed successfully', [
                'transaction_id' => $transaction->id,
                'booking_id' => $transaction->booking_id,
            ]);

            return true;
        });
    }

    /**
     * Handle payment amount mismatch (security issue)
     * Reverses the payment and marks as fraudulent
     */
    public function handleAmountMismatch(Transaction $transaction, float $expectedAmount, float $receivedAmount): void
    {
        Log::critical('Payment amount mismatch detected', [
            'transaction_id' => $transaction->id,
            'expected' => $expectedAmount,
            'received' => $receivedAmount,
            'difference' => abs($expectedAmount - $receivedAmount),
        ]);

        // Mark transaction as fraudulent
        $transaction->update([
            'status' => Transaction::STATUS_FAILED,
            'failure_reason' => 'Amount mismatch - possible fraud attempt',
            'metadata' => array_merge($transaction->metadata ?? [], [
                'fraud_detection' => [
                    'type' => 'amount_mismatch',
                    'expected_amount' => $expectedAmount,
                    'received_amount' => $receivedAmount,
                    'detected_at' => now()->toIso8601String(),
                ],
            ]),
        ]);

        // Cancel the booking
        if ($transaction->booking) {
            $transaction->booking->cancel('Payment verification failed - amount mismatch');
        }

        // TODO: Implement automatic refund through Paystack
        // This would require Paystack refund API integration
        // For now, flag for manual review
        $this->flagForManualReview($transaction, 'Amount mismatch requires manual refund');
    }

    /**
     * Flag transaction for manual review
     */
    protected function flagForManualReview(Transaction $transaction, string $reason): void
    {
        // Create admin notification or log for manual review
        Log::alert('Transaction flagged for manual review', [
            'transaction_id' => $transaction->id,
            'reason' => $reason,
            'booking_id' => $transaction->booking_id,
            'amount' => $transaction->amount,
        ]);

        // Update transaction metadata
        $transaction->update([
            'metadata' => array_merge($transaction->metadata ?? [], [
                'manual_review_required' => true,
                'review_reason' => $reason,
                'flagged_at' => now()->toIso8601String(),
            ]),
        ]);

        // TODO: Send notification to admin dashboard
    }

    /**
     * Cancel expired bookings
     * Called by scheduled job
     */
    public function cancelExpiredBookings(): int
    {
        $expiredBookings = Booking::where('status', 'pending')
            ->where('payment_status', 'unpaid')
            ->where('created_at', '<', now()->subMinutes(30))
            ->get();

        $cancelledCount = 0;

        foreach ($expiredBookings as $booking) {
            // Check if there's a pending transaction
            $pendingTransaction = Transaction::where('booking_id', $booking->id)
                ->where('status', Transaction::STATUS_PENDING)
                ->first();

            if ($pendingTransaction) {
                // Mark transaction as expired
                $pendingTransaction->update([
                    'status' => Transaction::STATUS_FAILED,
                    'failure_reason' => 'Booking expired - payment not completed',
                ]);
            }

            // Cancel the booking
            $booking->cancel('Expired - payment not completed within 30 minutes');
            $cancelledCount++;

            Log::info('Expired booking cancelled', [
                'booking_id' => $booking->id,
                'created_at' => $booking->created_at,
            ]);
        }

        return $cancelledCount;
    }
}
