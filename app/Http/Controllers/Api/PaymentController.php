<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Transaction;
use App\Services\NotificationService;
use App\Services\PaymentFlowService;
use App\Services\PaystackService;
use App\Services\TicketService;
use App\Services\BookingValidationService;
use App\Services\TicketTypeValidator;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Event;

class PaymentController extends Controller
{
    protected TransactionService $transactionService;

    protected NotificationService $notificationService;

    protected TicketService $ticketService;

    protected PaystackService $paystackService;

    protected PaymentFlowService $paymentFlowService;
    
    protected BookingValidationService $bookingValidationService;
    
    protected TicketTypeValidator $ticketTypeValidator;

    public function __construct(
        TransactionService $transactionService,
        NotificationService $notificationService,
        TicketService $ticketService,
        PaystackService $paystackService,
        PaymentFlowService $paymentFlowService,
        BookingValidationService $bookingValidationService,
        TicketTypeValidator $ticketTypeValidator
    ) {
        $this->transactionService = $transactionService;
        $this->notificationService = $notificationService;
        $this->ticketService = $ticketService;
        $this->paystackService = $paystackService;
        $this->paymentFlowService = $paymentFlowService;
        $this->bookingValidationService = $bookingValidationService;
        $this->ticketTypeValidator = $ticketTypeValidator;
    }

    /**
     * Initialize payment with Paystack (handles both card and M-Pesa).
     * Creates booking and initiates payment in one transaction.
     */
    public function initializePaystack(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_id' => 'required|uuid|exists:events,id',
            'ticket_types' => 'required|array|min:1',
            'ticket_types.*.name' => 'required|string|max:255',
            'ticket_types.*.quantity' => 'required|integer|min:1|max:100',
            'payment_method' => 'required|string|in:card,mpesa,bank_transfer',
            'phone_number' => 'required_if:payment_method,mpesa|string|regex:/^254[0-9]{9}$/',
        ]);

        $event = Event::with('organizer')->findOrFail($validated['event_id']);
        $user = $request->user();

        // Validate booking request
        $bookingValidation = $this->bookingValidationService->validateBookingRequest(
            $user,
            $event,
            $validated['ticket_types']
        );

        if (!$bookingValidation['valid']) {
            return response()->json([
                'success' => false,
                'message' => 'Booking validation failed',
                'errors' => $bookingValidation['errors'],
            ], 400);
        }

        // Use database lock to prevent race conditions
        DB::beginTransaction();
        try {
            // Clean up any pending bookings for this user and event first
            // This ensures users can always create a fresh booking
            $deletedCount = Booking::where('user_id', $user->id)
                ->where('event_id', $event->id)
                ->where('status', 'pending')
                ->where('payment_status', '!=', 'paid')
                ->delete();
            
            if ($deletedCount > 0) {
                Log::info('Cleaned up pending bookings before creating new one', [
                    'user_id' => $user->id,
                    'event_id' => $event->id,
                    'deleted_count' => $deletedCount,
                ]);
            }

            // Lock event to prevent overselling
            $lockedEvent = Event::lockForUpdate()->findOrFail($event->id);

            // Validate and prepare tickets with server-side prices
            $ticketValidation = $this->ticketTypeValidator->validateAndPrepareTickets(
                $lockedEvent,
                $validated['ticket_types']
            );

            if (!$ticketValidation['valid']) {
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid ticket selection',
                    'errors' => $ticketValidation['errors'],
                ], 400);
            }

            // Server-calculated values
            $validatedTickets = $ticketValidation['tickets'];
            $subtotal = $ticketValidation['subtotal'];
            $ticketQuantity = $ticketValidation['total_quantity'];
            $currency = $ticketValidation['currency'];

            // Calculate platform commission (deducted from organizer, NOT added to user price)
            $platformCommission = $this->ticketTypeValidator->calculateServiceFee($lockedEvent, $subtotal);
            
            // User pays exactly the ticket price, no additional fees
            $totalAmount = $subtotal;

            // Create booking
            $booking = Booking::create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'event_id' => $lockedEvent->id,
                'booking_reference' => 'BK' . strtoupper(Str::random(8)),
                'ticket_quantity' => $ticketQuantity,
                'ticket_types' => $validatedTickets,
                'subtotal' => $subtotal,
                'service_fee' => $platformCommission, // This is the platform's commission from organizer
                'total_amount' => $totalAmount, // User pays this (same as subtotal)
                'currency' => $currency,
                'customer_name' => $user->full_name ?? $user->name,
                'customer_email' => $user->email,
                'customer_phone' => $validated['phone_number'] ?? $user->phone_number ?? '',
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => $validated['payment_method'],
            ]);

            // Load relationships
            $booking->load(['event', 'event.organizer']);

            // Initialize payment
            $result = $this->paymentFlowService->initializePayment(
                $booking,
                $validated['payment_method']
            );

            if (!$result['success']) {
                DB::rollback();
                return response()->json($result, 400);
            }

            DB::commit();

            Log::info('Payment initialized', [
                'booking_id' => $booking->id,
                'payment_method' => $validated['payment_method'],
            ]);

            return response()->json($result);
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to create booking and initialize payment', [
                'error' => $e->getMessage(),
                'event_id' => $validated['event_id'],
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize payment. Please try again.',
            ], 500);
        }
    }


    /**
     * Verify payment status.
     * SECURITY: Always verify with payment gateway for critical operations
     */
    public function verifyPayment(Request $request, string $transactionId): JsonResponse
    {
        // Get transaction and verify ownership
        $transaction = Transaction::where('id', $transactionId)
            ->where('user_id', $request->user()->id)
            ->with(['booking'])
            ->firstOrFail();

        // If transaction is pending and has Paystack reference, verify with Paystack
        if ($transaction->status === Transaction::STATUS_PENDING &&
            $transaction->payment_reference &&
            $transaction->payment_gateway === 'paystack') {

            try {
                // Verify with Paystack API for real-time status
                $verification = $this->paystackService->verifyTransaction($transaction->payment_reference);

                if ($verification['success'] && $verification['status'] === 'success') {
                    // Security: Verify amount matches
                    if ($transaction->amount != $verification['amount']) {
                        Log::error('Payment verification: Amount mismatch', [
                            'transaction_id' => $transaction->id,
                            'expected' => $transaction->amount,
                            'received' => $verification['amount'],
                        ]);

                        $transaction->update([
                            'status' => Transaction::STATUS_FAILED,
                            'failure_reason' => 'Amount mismatch during verification',
                        ]);

                        return response()->json([
                            'success' => false,
                            'message' => 'Payment verification failed',
                            'data' => [
                                'transaction_id' => $transaction->id,
                                'status' => Transaction::STATUS_FAILED,
                            ],
                        ], 400);
                    }

                    // Process successful payment if not already processed
                    if ($transaction->status === Transaction::STATUS_PENDING) {
                        DB::beginTransaction();
                        try {
                            // Update transaction
                            $this->transactionService->processPaystackPayment(
                                $transaction,
                                $verification['reference'],
                                [
                                    'channel' => $verification['channel'] ?? 'card',
                                    'paid_at' => $verification['paid_at'] ?? now(),
                                    'customer' => $verification['customer'] ?? [],
                                    'authorization' => $verification['authorization'] ?? [],
                                ]
                            );

                            // Create tickets
                            $this->ticketService->createTicketsForBooking($transaction->booking);

                            // Send confirmation
                            $this->notificationService->sendBookingConfirmation($transaction->booking);

                            DB::commit();

                            Log::info('Payment verified and processed', [
                                'transaction_id' => $transaction->id,
                            ]);

                            // Reload transaction to get updated status
                            $transaction->refresh();

                        } catch (\Exception $e) {
                            DB::rollback();
                            Log::error('Failed to process verified payment', [
                                'transaction_id' => $transaction->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                } elseif ($verification['success'] && $verification['status'] === 'failed') {
                    // Payment failed at gateway
                    DB::beginTransaction();
                    try {
                        $transaction->update([
                            'status' => Transaction::STATUS_FAILED,
                            'failure_reason' => 'Payment failed at gateway',
                        ]);
                        
                        // Delete the booking - we don't keep failed payment bookings
                        if ($transaction->booking) {
                            $bookingId = $transaction->booking->id;
                            $transaction->booking->delete();
                            
                            Log::info('Deleted booking after verification failure', [
                                'booking_id' => $bookingId,
                                'transaction_id' => $transaction->id,
                            ]);
                        }
                        
                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error('Error handling verification failure', [
                            'transaction_id' => $transaction->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

            } catch (\Exception $e) {
                Log::error('Payment verification error', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Return current transaction status
        return response()->json([
            'success' => true,
            'data' => [
                'transaction_id' => $transaction->id,
                'status' => $transaction->status,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'payment_gateway' => $transaction->payment_gateway,
                'payment_reference' => $transaction->payment_reference,
                'booking_id' => $transaction->booking_id,
                'is_completed' => $transaction->status === Transaction::STATUS_COMPLETED,
                'is_failed' => $transaction->status === Transaction::STATUS_FAILED,
                'failure_reason' => $transaction->failure_reason,
            ],
        ]);
    }

    /**
     * Paystack webhook callback.
     * SECURITY: This endpoint must validate webhook signatures
     */
    public function paystackWebhook(Request $request): JsonResponse
    {
        // Step 1: Verify webhook signature for security
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();

        // Security check: Reject if no signature
        if (! $signature) {
            Log::warning('Paystack webhook received without signature', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid request'], 400);
        }

        // Security check: Validate signature
        if (! $this->paystackService->validateWebhookSignature($payload, $signature)) {
            Log::warning('Paystack webhook signature validation failed', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Step 2: Process the webhook event
        $data = json_decode($payload, true);
        $processedEvent = $this->paystackService->processWebhookEvent($data);

        // Step 3: Handle specific events
        if ($processedEvent['event'] === 'payment_success') {
            $reference = $processedEvent['reference'];

            // Find transaction by reference
            $transaction = Transaction::where('payment_reference', $reference)
                ->with(['booking', 'booking.event'])
                ->first();

            if (! $transaction) {
                Log::warning('Paystack webhook: Transaction not found', [
                    'reference' => $reference,
                ]);

                return response()->json(['status' => 'received']);
            }

            // Security check: Verify amount matches
            if ($transaction->amount != $processedEvent['amount']) {
                // Phase 3: Handle amount mismatch with reversal
                $this->paymentFlowService->handleAmountMismatch(
                    $transaction,
                    $transaction->amount,
                    $processedEvent['amount']
                );

                return response()->json(['status' => 'received']);
            }

            // Security check: Prevent duplicate processing
            if ($transaction->status === Transaction::STATUS_COMPLETED) {
                Log::info('Paystack webhook: Transaction already processed', [
                    'reference' => $reference,
                ]);

                return response()->json(['status' => 'received']);
            }

            // Process the payment
            if ($transaction->status === Transaction::STATUS_PENDING) {
                try {
                    // Phase 3: Use PaymentFlowService for processing
                    $this->paymentFlowService->processSuccessfulPayment(
                        $transaction,
                        [
                            'reference' => $reference,
                            'card_last4' => $processedEvent['authorization']['last4'] ?? null,
                            'card_type' => $processedEvent['authorization']['card_type'] ?? null,
                            'bank' => $processedEvent['authorization']['bank'] ?? null,
                            'channel' => $processedEvent['channel'] ?? 'card',
                            'paid_at' => $processedEvent['paid_at'] ?? now(),
                        ]
                    );

                } catch (\Exception $e) {
                    Log::error('Failed to process Paystack payment', [
                        'reference' => $reference,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } elseif ($processedEvent['event'] === 'payment_failed') {
            // Handle failed payment
            $reference = $processedEvent['reference'];
            $transaction = Transaction::where('payment_reference', $reference)
                ->with('booking')
                ->first();

            if ($transaction && $transaction->status === Transaction::STATUS_PENDING) {
                DB::beginTransaction();
                try {
                    // Update transaction status
                    $transaction->update([
                        'status' => Transaction::STATUS_FAILED,
                        'failure_reason' => $processedEvent['message'] ?? 'Payment failed',
                    ]);

                    // Delete the booking entirely - we don't keep failed payment bookings
                    if ($transaction->booking) {
                        $bookingId = $transaction->booking->id;
                        $transaction->booking->delete();
                        
                        Log::info('Deleted booking after payment failure', [
                            'booking_id' => $bookingId,
                            'reference' => $reference,
                        ]);
                    }

                    DB::commit();
                    
                    Log::info('Paystack payment failed and booking cleaned up', [
                        'reference' => $reference,
                        'transaction_id' => $transaction->id,
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Error handling failed payment', [
                        'reference' => $reference,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Always return success to acknowledge receipt
        return response()->json(['status' => 'received']);
    }

    /**
     * M-Pesa callback.
     */
    public function mpesaCallback(Request $request): JsonResponse
    {
        $data = $request->all();

        // Extract M-Pesa data
        $resultCode = $data['Body']['stkCallback']['ResultCode'] ?? null;
        $resultDesc = $data['Body']['stkCallback']['ResultDesc'] ?? null;
        $merchantRequestId = $data['Body']['stkCallback']['MerchantRequestID'] ?? null;

        if ($resultCode == 0) { // Success
            $metadata = $data['Body']['stkCallback']['CallbackMetadata']['Item'] ?? [];
            $mpesaData = [];

            foreach ($metadata as $item) {
                $mpesaData[$item['Name']] = $item['Value'];
            }

            // Find transaction by phone number or reference
            $transaction = Transaction::where('status', 'pending')
                ->whereJsonContains('metadata->mpesa_merchant_request_id', $merchantRequestId)
                ->first();

            if ($transaction) {
                DB::beginTransaction();
                try {
                    // Update transaction
                    $this->transactionService->processMpesaPayment(
                        $transaction,
                        [
                            'MpesaReceiptNumber' => $mpesaData['MpesaReceiptNumber'] ?? null,
                            'PhoneNumber' => $mpesaData['PhoneNumber'] ?? null,
                            'TransactionDate' => $mpesaData['TransactionDate'] ?? null,
                            'ResultCode' => $resultCode,
                        ]
                    );

                    // Create tickets using TicketService
                    $this->ticketService->createTicketsForBooking($transaction->booking);

                    // Send confirmation notification
                    $this->notificationService->sendBookingConfirmation($transaction->booking);

                    DB::commit();

                    Log::info('M-Pesa payment processed', ['receipt' => $mpesaData['MpesaReceiptNumber'] ?? null]);
                } catch (\Exception $e) {
                    DB::rollback();
                    Log::error('Failed to process M-Pesa payment', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    /**
     * Get user's transaction history.
     */
    public function transactions(Request $request): JsonResponse
    {
        $transactions = $request->user()->transactions()
            ->with(['booking', 'booking.event'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Payment callback handler
     * Called when user is redirected back from Paystack
     */
    public function paymentCallback(Request $request): JsonResponse
    {
        $reference = $request->query('reference');

        if (! $reference) {
            return response()->json([
                'success' => false,
                'message' => 'Payment reference not provided',
            ], 400);
        }

        // Find the transaction
        $transaction = Transaction::where('payment_reference', $reference)
            ->where('user_id', $request->user()->id)
            ->with(['booking'])
            ->first();

        if (! $transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        // Verify the payment status
        return $this->verifyPayment($request, $transaction->id);
    }
}
