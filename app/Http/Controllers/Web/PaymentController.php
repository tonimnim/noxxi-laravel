<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Transaction;
use App\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected PaystackService $paystackService;

    public function __construct(PaystackService $paystackService)
    {
        $this->paystackService = $paystackService;
    }

    /**
     * Initialize Paystack payment for all payment methods (Card, M-Pesa, Apple Pay)
     */
    public function initializePaystack(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => 'required|uuid|exists:bookings,id',
        ]);

        try {
            // Get booking and verify ownership
            $booking = Booking::where('id', $validated['booking_id'])
                ->where('user_id', Auth::id())
                ->where('status', 'pending')
                ->where('payment_status', 'unpaid')
                ->with(['event', 'transactions'])
                ->firstOrFail();

            // Get the transaction for this booking
            $transaction = $booking->transactions()->first();

            if (! $transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'No transaction found for this booking',
                ], 400);
            }

            // Generate unique reference if not exists
            if (! $transaction->payment_reference) {
                $transaction->payment_reference = $this->paystackService->generateReference('NXI');
                $transaction->save();
            }

            // Initialize Paystack transaction
            // SECURITY: Amount comes from database, not from frontend
            $result = $this->paystackService->initializeTransaction([
                'email' => Auth::user()->email,
                'amount' => $transaction->amount, // PaystackService handles conversion
                'reference' => $transaction->payment_reference,
                'currency' => $transaction->currency,
                'callback_url' => url('/payment/callback'),
                'channels' => $this->getPaymentChannels($booking->payment_method),
                'metadata' => [
                    'transaction_id' => $transaction->id,
                    'booking_id' => $booking->id,
                    'user_id' => Auth::id(),
                    'event_title' => $booking->event->title ?? 'Event Ticket',
                    'payment_method' => $booking->payment_method,
                ],
            ]);

            // Check if the result is successful
            if (! isset($result['success']) || ! $result['success']) {
                throw new \Exception('Failed to initialize Paystack payment');
            }

            // Update transaction with gateway reference
            $transaction->update([
                'gateway_reference' => $result['access_code'] ?? null,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'paystack_access_code' => $result['access_code'] ?? null,
                    'paystack_reference' => $result['reference'] ?? null,
                ]),
            ]);

            Log::info('Paystack payment initialized', [
                'transaction_id' => $transaction->id,
                'booking_id' => $booking->id,
                'reference' => $result['reference'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment initialized successfully',
                'data' => [
                    'authorization_url' => $result['authorization_url'],
                    'access_code' => $result['access_code'],
                    'reference' => $result['reference'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Payment initialization failed', [
                'booking_id' => $validated['booking_id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize payment. Please try again.',
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Handle payment callback from Paystack
     */
    public function handleCallback(Request $request)
    {
        $reference = $request->query('reference');

        if (! $reference) {
            return redirect('/')->with('error', 'Invalid payment reference');
        }

        try {
            // Verify payment with Paystack
            $result = $this->paystackService->verifyTransaction($reference);

            if (! $result['success']) {
                Log::error('Payment verification failed', ['reference' => $reference]);

                return redirect('/')->with('error', 'Payment verification failed');
            }

            // Get transaction by reference
            $transaction = Transaction::where('payment_reference', $reference)->first();

            if (! $transaction) {
                Log::error('Transaction not found', ['reference' => $reference]);

                return redirect('/')->with('error', 'Transaction not found');
            }

            // Get booking
            $booking = Booking::find($transaction->booking_id);

            if (! $booking) {
                Log::error('Booking not found', ['transaction_id' => $transaction->id]);

                return redirect('/')->with('error', 'Booking not found');
            }

            // Ensure the user is authenticated (they should be from the checkout process)
            // If not authenticated, authenticate them using the booking's user
            if (! Auth::check()) {
                $user = \App\Models\User::find($booking->user_id);
                if ($user) {
                    Auth::login($user);
                    Log::info('User authenticated in payment callback', ['user_id' => $user->id]);
                }
            }

            // Check if payment was successful
            if ($result['status'] === 'success') {
                // Update transaction status
                $transaction->update([
                    'status' => 'completed',
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                    'gateway_response' => $result,
                ]);

                // Update booking status
                $booking->update([
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                    'confirmed_at' => now(),
                ]);

                // Create tickets for the booking
                $this->createTickets($booking);

                // Log success
                Log::info('Payment successful', [
                    'booking_id' => $booking->id,
                    'reference' => $reference,
                    'amount' => $transaction->amount,
                ]);

                // Redirect to success page
                return redirect('/booking/confirmation/'.$booking->id)
                    ->with('success', 'Payment successful! Your tickets have been confirmed.');
            } else {
                // Payment failed
                $transaction->update([
                    'status' => 'failed',
                    'payment_status' => 'failed',
                    'gateway_response' => $result,
                ]);

                $booking->update([
                    'status' => 'cancelled',
                    'payment_status' => 'failed',
                ]);

                Log::warning('Payment failed', [
                    'booking_id' => $booking->id,
                    'reference' => $reference,
                ]);

                return redirect('/listings/'.$booking->event_id)
                    ->with('error', 'Payment failed. Please try again.');
            }
        } catch (\Exception $e) {
            Log::error('Payment callback error', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return redirect('/')->with('error', 'An error occurred processing your payment');
        }
    }

    /**
     * Create tickets for confirmed booking
     */
    private function createTickets(Booking $booking)
    {
        try {
            $tickets = [];
            $ticketTypes = $booking->ticket_types ?? [];

            foreach ($ticketTypes as $ticketType) {
                for ($i = 0; $i < $ticketType['quantity']; $i++) {
                    $ticketCode = 'TKT'.strtoupper(\Str::random(8));
                    $tickets[] = [
                        'id' => \Str::uuid(),
                        'booking_id' => $booking->id,
                        'event_id' => $booking->event_id,
                        'ticket_code' => $ticketCode,
                        'ticket_hash' => hash('sha256', $ticketCode.$booking->id),
                        'ticket_type' => $ticketType['name'] ?? 'General',
                        'price' => $ticketType['price'] ?? 0,
                        'currency' => $booking->currency,
                        'status' => 'valid',
                        'qr_code' => $this->generateQrCode($booking->id, $ticketCode),
                        'holder_name' => $booking->customer_name,
                        'holder_email' => $booking->customer_email,
                        'holder_phone' => $booking->customer_phone,
                        'assigned_to' => $booking->user_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (! empty($tickets)) {
                \DB::table('tickets')->insert($tickets);
                Log::info('Tickets created for booking', [
                    'booking_id' => $booking->id,
                    'ticket_count' => count($tickets),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to create tickets', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate QR code for ticket
     */
    private function generateQrCode($bookingId, $ticketCode)
    {
        // Simple QR code data - you can enhance this
        return base64_encode(json_encode([
            'booking_id' => $bookingId,
            'ticket_code' => $ticketCode,
            'timestamp' => time(),
            'hash' => hash('sha256', $bookingId.$ticketCode.config('app.key')),
        ]));
    }

    /**
     * Get payment channels based on payment method
     */
    private function getPaymentChannels(string $paymentMethod): array
    {
        return match ($paymentMethod) {
            'mpesa' => ['mobile_money'],
            'card' => ['card'],
            'apple' => ['apple_pay'],
            default => ['card', 'bank', 'ussd', 'mobile_money'],
        };
    }
}
