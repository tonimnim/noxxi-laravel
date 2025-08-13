<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Booking;
use App\Models\Ticket;
use App\Services\TransactionService;
use App\Services\NotificationService;
use App\Services\TicketService;
use App\Notifications\BookingConfirmed;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected TransactionService $transactionService;
    protected NotificationService $notificationService;
    protected TicketService $ticketService;

    public function __construct(
        TransactionService $transactionService,
        NotificationService $notificationService,
        TicketService $ticketService
    ) {
        $this->transactionService = $transactionService;
        $this->notificationService = $notificationService;
        $this->ticketService = $ticketService;
    }

    /**
     * Initialize payment with Paystack.
     */
    public function initializePaystack(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transaction_id' => 'required|uuid|exists:transactions,id',
        ]);

        $transaction = Transaction::where('id', $validated['transaction_id'])
            ->where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->firstOrFail();

        // TODO: Integrate with Paystack API
        // For now, return mock data
        return response()->json([
            'success' => true,
            'data' => [
                'authorization_url' => 'https://checkout.paystack.com/mock',
                'access_code' => 'MOCK_' . strtoupper(Str::random(20)),
                'reference' => $transaction->payment_reference,
            ],
        ]);
    }

    /**
     * Initialize M-Pesa STK push.
     */
    public function initializeMpesa(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transaction_id' => 'required|uuid|exists:transactions,id',
            'phone_number' => 'required|string|regex:/^254[0-9]{9}$/',
        ]);

        $transaction = Transaction::where('id', $validated['transaction_id'])
            ->where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->firstOrFail();

        // TODO: Integrate with M-Pesa Daraja API
        // For now, return mock data
        return response()->json([
            'success' => true,
            'message' => 'STK push sent to your phone',
            'data' => [
                'merchant_request_id' => 'MPESA_' . strtoupper(Str::random(20)),
                'checkout_request_id' => 'CHK_' . strtoupper(Str::random(20)),
                'response_code' => '0',
                'response_description' => 'Success. Request accepted for processing',
            ],
        ]);
    }

    /**
     * Verify payment status.
     */
    public function verifyPayment(Request $request, string $transactionId): JsonResponse
    {
        $transaction = Transaction::where('id', $transactionId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

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
            ],
        ]);
    }

    /**
     * Paystack webhook callback.
     */
    public function paystackWebhook(Request $request): JsonResponse
    {
        // Verify webhook signature
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();
        // TODO: Verify signature with Paystack secret

        $data = $request->all();
        
        if ($data['event'] === 'charge.success') {
            $reference = $data['data']['reference'];
            $transaction = Transaction::where('payment_reference', $reference)->first();
            
            if ($transaction && $transaction->status === 'pending') {
                DB::beginTransaction();
                try {
                    // Update transaction
                    $this->transactionService->processPaystackPayment(
                        $transaction,
                        $reference,
                        [
                            'card_last4' => $data['data']['authorization']['last4'] ?? null,
                            'card_type' => $data['data']['authorization']['card_type'] ?? null,
                            'bank' => $data['data']['authorization']['bank'] ?? null,
                            'channel' => $data['data']['channel'] ?? 'card',
                        ]
                    );
                    
                    // Create tickets using TicketService
                    $this->ticketService->createTicketsForBooking($transaction->booking);
                    
                    // Send confirmation notification
                    $this->notificationService->sendBookingConfirmation($transaction->booking);
                    
                    DB::commit();
                    
                    Log::info('Paystack payment processed', ['reference' => $reference]);
                } catch (\Exception $e) {
                    DB::rollback();
                    Log::error('Failed to process Paystack payment', [
                        'reference' => $reference,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
        
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

}