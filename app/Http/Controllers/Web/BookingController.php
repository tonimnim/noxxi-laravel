<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Ticket;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Create a new booking (initiate ticket purchase).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_id' => 'required|uuid|exists:events,id',
            'ticket_types' => 'required|array',
            'ticket_types.*.name' => 'required|string',
            'ticket_types.*.type' => 'required|string|in:regular,vip,early_bird,group',
            'ticket_types.*.quantity' => 'required|integer|min:1|max:10',
            'ticket_types.*.price' => 'required|numeric|min:0',
            'payment_method' => 'required|string|in:card,mpesa,apple',
            'total_amount' => 'required|numeric|min:0',
        ]);

        $event = Event::findOrFail($validated['event_id']);

        // Check if event is active and not past
        if ($event->status !== 'published' || $event->event_date < now()) {
            return response()->json([
                'success' => false,
                'message' => 'This event is no longer available for booking',
            ], 400);
        }

        // Calculate totals
        $subtotal = 0;
        $ticketQuantity = 0;
        foreach ($validated['ticket_types'] as $ticketType) {
            $subtotal += $ticketType['price'] * $ticketType['quantity'];
            $ticketQuantity += $ticketType['quantity'];
        }

        // Check ticket availability
        $soldTickets = Ticket::where('event_id', $event->id)
            ->whereIn('status', ['valid', 'used'])
            ->count();

        $maxCapacity = $event->capacity ?? 1000;
        if (($soldTickets + $ticketQuantity) > $maxCapacity) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough tickets available',
                'available' => $maxCapacity - $soldTickets,
            ], 400);
        }

        // No service fee - customer pays exact ticket price
        $serviceFee = 0;
        $totalAmount = $subtotal;

        // Get payment method
        $paymentMethod = $validated['payment_method'];

        DB::beginTransaction();
        try {
            // Create booking
            $booking = Booking::create([
                'user_id' => Auth::id(),
                'event_id' => $event->id,
                'booking_reference' => 'BK'.strtoupper(Str::random(8)),
                'quantity' => $ticketQuantity,
                'ticket_types' => $validated['ticket_types'],
                'subtotal' => $subtotal,
                'service_fee' => $serviceFee,
                'total_amount' => $totalAmount,
                'currency' => isset($event->pricing['currency']) ? $event->pricing['currency'] : 'KES',
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => $paymentMethod,
                'customer_name' => Auth::user()->name,
                'customer_email' => Auth::user()->email,
                'customer_phone' => Auth::user()->phone ?? Auth::user()->phone_number ?? null,
            ]);

            // Create transaction record
            $paymentGateway = match ($paymentMethod) {
                'card', 'apple' => 'paystack',
                'mpesa' => 'mpesa',
                default => 'paystack',
            };

            $transaction = $this->transactionService->createTicketSale(
                $booking,
                $paymentGateway,
                $paymentMethod,
                ['customer_phone' => Auth::user()->phone ?? Auth::user()->phone_number ?? null]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully',
                'data' => [
                    'booking_id' => $booking->id,
                    'booking' => $booking->load('event'),
                    'transaction_id' => $transaction->id,
                    'payment_method' => $paymentMethod,
                    'amount' => $totalAmount,
                    'currency' => $booking->currency,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();

            \Log::error('Booking creation failed: '.$e->getMessage(), [
                'user_id' => Auth::id(),
                'event_id' => $event->id,
                'error' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create booking. Please try again.',
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get booking details for confirmation page
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $booking = Booking::where('id', $id)
                ->where('user_id', Auth::id())
                ->with(['event', 'tickets', 'transactions'])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => [
                    'booking' => [
                        'id' => $booking->id,
                        'reference_number' => $booking->booking_reference,
                        'email' => $booking->customer_email,
                        'event' => [
                            'title' => $booking->event->title,
                            'event_date' => $booking->event->event_date,
                            'venue_name' => $booking->event->venue_name,
                            'currency' => $booking->currency,
                        ],
                        'tickets' => $booking->tickets->map(function ($ticket) {
                            return [
                                'id' => $ticket->id,
                                'ticket_number' => $ticket->ticket_code,
                                'ticket_type' => $ticket->ticket_type,
                                'price' => $ticket->price,
                                'qr_code' => 'data:image/png;base64,'.$ticket->qr_code,
                            ];
                        }),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found',
            ], 404);
        }
    }
}
