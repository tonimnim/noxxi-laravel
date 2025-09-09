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
