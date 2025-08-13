<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Ticket;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
     * Get user's bookings.
     */
    public function index(Request $request): JsonResponse
    {
        $bookings = $request->user()->bookings()
            ->with(['event', 'event.organizer', 'tickets'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $bookings,
        ]);
    }

    /**
     * Create a new booking (initiate ticket purchase).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_id' => 'required|uuid|exists:events,id',
            'ticket_types' => 'required|array',
            'ticket_types.*.type' => 'required|string|in:regular,vip,early_bird,group',
            'ticket_types.*.quantity' => 'required|integer|min:1|max:10',
            'ticket_types.*.price' => 'required|numeric|min:0',
            'customer_details' => 'array',
            'customer_details.phone' => 'required_if:payment_method,mpesa|string',
            'payment_method' => 'required|string|in:card,mpesa,crypto',
        ]);

        $event = Event::findOrFail($validated['event_id']);

        // Check if event is active and not past
        if (!$event->is_active || $event->event_date < now()) {
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
        
        if (($soldTickets + $ticketQuantity) > $event->capacity) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough tickets available',
                'available' => $event->capacity - $soldTickets,
            ], 400);
        }

        $serviceFee = $subtotal * 0.03; // 3% service fee
        $totalAmount = $subtotal + $serviceFee;

        DB::beginTransaction();
        try {
            // Create booking
            $booking = Booking::create([
                'user_id' => $request->user()->id,
                'event_id' => $event->id,
                'booking_reference' => 'BK' . strtoupper(Str::random(8)),
                'ticket_quantity' => $ticketQuantity,
                'ticket_types' => $validated['ticket_types'],
                'subtotal' => $subtotal,
                'service_fee' => $serviceFee,
                'total_amount' => $totalAmount,
                'currency' => 'KES',
                'status' => 'pending',
                'payment_status' => 'pending',
                'customer_details' => $validated['customer_details'] ?? [],
            ]);

            // Create transaction record
            $paymentMethod = $validated['payment_method'];
            $paymentGateway = match($paymentMethod) {
                'card' => 'paystack',
                'mpesa' => 'mpesa',
                'crypto' => 'crypto',
                default => 'paystack',
            };

            $transaction = $this->transactionService->createTicketSale(
                $booking,
                $paymentGateway,
                $paymentMethod,
                ['customer_phone' => $validated['customer_details']['phone'] ?? null]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully',
                'data' => [
                    'booking' => $booking->load('event'),
                    'transaction_id' => $transaction->id,
                    'payment_method' => $paymentMethod,
                    'amount' => $totalAmount,
                    'currency' => 'KES',
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create booking',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific booking.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $booking = $request->user()->bookings()
            ->with(['event', 'event.organizer', 'tickets', 'transactions'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $booking,
        ]);
    }

    /**
     * Cancel a booking.
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $booking = $request->user()->bookings()->findOrFail($id);

        // Check if booking can be cancelled
        if ($booking->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending bookings can be cancelled',
            ], 400);
        }

        $booking->cancel('User cancelled');

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully',
        ]);
    }

    /**
     * Get tickets for a booking.
     */
    public function tickets(Request $request, string $id): JsonResponse
    {
        $booking = $request->user()->bookings()->findOrFail($id);
        
        if ($booking->status !== 'confirmed') {
            return response()->json([
                'success' => false,
                'message' => 'Tickets are only available for confirmed bookings',
            ], 400);
        }

        $tickets = $booking->tickets()->get();

        return response()->json([
            'success' => true,
            'data' => $tickets,
        ]);
    }
}