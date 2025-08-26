<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Transaction;
use App\Services\NotificationService;
use App\Services\PaymentFlowService;
use App\Services\PaystackService;
use App\Services\TicketService;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected TransactionService $transactionService;

    protected NotificationService $notificationService;

    protected TicketService $ticketService;

    protected PaystackService $paystackService;

    protected PaymentFlowService $paymentFlowService;

    public function __construct(
        TransactionService $transactionService,
        NotificationService $notificationService,
        TicketService $ticketService,
        PaystackService $paystackService,
        PaymentFlowService $paymentFlowService
    ) {
        $this->transactionService = $transactionService;
        $this->notificationService = $notificationService;
        $this->ticketService = $ticketService;
        $this->paystackService = $paystackService;
        $this->paymentFlowService = $paymentFlowService;
    }

    /**
     * Initialize payment with Paystack.
     * Phase 3: Now creates transaction on payment initialization
     */
    public function initializePaystack(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => 'required|uuid|exists:bookings,id',
            'payment_method' => 'required|string|in:card,mpesa,bank_transfer',
        ]);

        // Get booking and verify ownership
        $booking = Booking::where('id', $validated['booking_id'])
            ->where('user_id', $request->user()->id)
            ->with(['event', 'event.organizer'])
            ->firstOrFail();

        // Initialize payment (creates transaction if needed)
        $result = $this->paymentFlowService->initializePayment(
            $booking,
            $validated['payment_method']
        );

        if (! $result['success']) {
            return response()->json($result, 400);
        }

        Log::info('Payment initialized', [
            'booking_id' => $booking->id,
            'payment_method' => $validated['payment_method'],
        ]);

        return response()->json($result);
    }

    /**
     * Initialize M-Pesa payment.
     * Phase 3: Now creates transaction on payment initialization
     */
    public function initializeMpesa(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => 'required|uuid|exists:bookings,id',
            'phone_number' => 'required|string|regex:/^254[0-9]{9}$/',
        ]);

        // Get booking and verify ownership
        $booking = Booking::where('id', $validated['booking_id'])
            ->where('user_id', $request->user()->id)
            ->with(['event', 'event.organizer'])
            ->firstOrFail();

        // Store phone number in booking
        $booking->update(['customer_phone' => $validated['phone_number']]);

        // Initialize payment with M-Pesa method
        $result = $this->paymentFlowService->initializePayment(
            $booking,
            'mpesa'
        );

        if (! $result['success']) {
            return response()->json($result, 400);
        }

        Log::info('M-Pesa payment initialized', [
            'booking_id' => $booking->id,
            'phone_number' => $validated['phone_number'],
        ]);

        return response()->json($result);
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
                    $transaction->update([
                        'status' => Transaction::STATUS_FAILED,
                        'failure_reason' => 'Payment failed at gateway',
                    ]);
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
            $transaction = Transaction::where('payment_reference', $reference)->first();

            if ($transaction && $transaction->status === Transaction::STATUS_PENDING) {
                $transaction->update([
                    'status' => Transaction::STATUS_FAILED,
                    'failure_reason' => $processedEvent['message'] ?? 'Payment failed',
                ]);

                Log::info('Paystack payment failed', [
                    'reference' => $reference,
                ]);
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
