<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Ticket;
use App\Services\BookingValidationService;
use App\Services\TicketTypeValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    protected TicketTypeValidator $ticketTypeValidator;

    protected BookingValidationService $bookingValidationService;

    public function __construct(
        TicketTypeValidator $ticketTypeValidator,
        BookingValidationService $bookingValidationService
    ) {
        $this->ticketTypeValidator = $ticketTypeValidator;
        $this->bookingValidationService = $bookingValidationService;
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
            'ticket_types' => 'required|array|min:1',
            'ticket_types.*.name' => 'required|string|max:255', // Ticket type name from event config
            'ticket_types.*.quantity' => 'required|integer|min:1|max:100',
            // NO PRICE VALIDATION - prices come from server
            'customer_details' => 'array',
            'customer_details.phone' => 'string|nullable',
        ]);

        // Get event (without lock first, for validation)
        $event = Event::findOrFail($validated['event_id']);

        // Phase 2: Comprehensive booking validation
        $bookingValidation = $this->bookingValidationService->validateBookingRequest(
            $request->user(),
            $event,
            $validated['ticket_types']
        );

        if (! $bookingValidation['valid']) {
            return response()->json([
                'success' => false,
                'message' => 'Booking validation failed',
                'errors' => $bookingValidation['errors'],
                'warnings' => $bookingValidation['warnings'] ?? [],
            ], 400);
        }

        // Use database lock to prevent race conditions
        return $this->bookingValidationService->validateWithLocking(
            $event,
            $validated['ticket_types'],
            function ($lockedEvent) use ($request, $validated) {

                // Validate and prepare tickets with server-side prices
                $ticketValidation = $this->ticketTypeValidator->validateAndPrepareTickets(
                    $lockedEvent,
                    $validated['ticket_types']
                );

                if (! $ticketValidation['valid']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid ticket selection',
                        'errors' => $ticketValidation['errors'],
                    ], 400);
                }

                // Use server-calculated values
                $validatedTickets = $ticketValidation['tickets'];
                $subtotal = $ticketValidation['subtotal'];
                $ticketQuantity = $ticketValidation['total_quantity'];
                $currency = $ticketValidation['currency']; // Use event's currency

                // Calculate service fee based on event/organizer settings
                $serviceFee = $this->ticketTypeValidator->calculateServiceFee($lockedEvent, $subtotal);
                $totalAmount = $subtotal + $serviceFee;

                // Create booking with server-side calculated values
                $booking = Booking::create([
                    'user_id' => $request->user()->id,
                    'event_id' => $lockedEvent->id,
                    'booking_reference' => 'BK'.strtoupper(Str::random(8)),
                    'ticket_quantity' => $ticketQuantity,
                    'ticket_types' => $validatedTickets, // Store validated tickets with server prices
                    'subtotal' => $subtotal,
                    'service_fee' => $serviceFee,
                    'total_amount' => $totalAmount,
                    'currency' => $currency, // Use event's currency, not hardcoded
                    'status' => 'pending',
                    'payment_status' => 'unpaid',
                    // Use individual customer fields instead of customer_details
                    'customer_name' => $request->user()->full_name ?? $request->user()->name,
                    'customer_email' => $request->user()->email,
                    'customer_phone' => $validated['customer_details']['phone'] ?? $request->user()->phone_number ?? '',
                    // Set expiry time for booking (30 minutes to complete payment)
                    'expires_at' => now()->addMinutes(30),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Booking created successfully',
                    'data' => [
                        'booking' => $booking->load('event'),
                        'booking_id' => $booking->id,
                        'booking_reference' => $booking->booking_reference,
                        'amount' => $totalAmount,
                        'currency' => $currency,
                        'expires_at' => $booking->expires_at,
                        'payment_options' => ['card', 'mpesa'], // Available payment methods
                    ],
                ], 201);
            }); // Timeout handled in validateWithLocking
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
