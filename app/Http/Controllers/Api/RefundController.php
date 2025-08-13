<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RefundRequest;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RefundController extends Controller
{
    /**
     * Get user's refund requests.
     */
    public function index(Request $request): JsonResponse
    {
        $refunds = RefundRequest::where('user_id', $request->user()->id)
            ->with(['booking', 'booking.event'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $refunds,
        ]);
    }

    /**
     * Request a refund for a booking.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => 'required|uuid|exists:bookings,id',
            'reason' => 'required|string|min:10|max:500',
            'requested_amount' => 'required|numeric|min:0',
            'customer_message' => 'nullable|string|max:1000',
        ]);

        // Check if booking belongs to user
        $booking = Booking::where('id', $validated['booking_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // Check if booking is eligible for refund
        if (!in_array($booking->status, ['confirmed', 'completed'])) {
            return response()->json([
                'success' => false,
                'message' => 'This booking is not eligible for refund',
            ], 400);
        }

        // Check if refund amount is valid
        if ($validated['requested_amount'] > $booking->total_amount) {
            return response()->json([
                'success' => false,
                'message' => 'Refund amount cannot exceed booking total',
            ], 400);
        }

        // Check if there's already a pending refund request
        $existingRequest = RefundRequest::where('booking_id', $booking->id)
            ->whereIn('status', ['pending', 'reviewing', 'approved'])
            ->first();

        if ($existingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'A refund request is already in progress for this booking',
            ], 400);
        }

        // Check event refund policy (e.g., no refunds 24 hours before event)
        $event = $booking->event;
        $hoursUntilEvent = now()->diffInHours($event->event_date, false);
        
        if ($hoursUntilEvent <= 24 && $hoursUntilEvent >= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Refunds are not allowed within 24 hours of the event',
            ], 400);
        }

        // Create refund request
        $refundRequest = RefundRequest::create([
            'booking_id' => $booking->id,
            'user_id' => $request->user()->id,
            'reason' => $validated['reason'],
            'requested_amount' => $validated['requested_amount'],
            'currency' => $booking->currency,
            'status' => 'pending',
            'customer_message' => $validated['customer_message'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Refund request submitted successfully',
            'data' => $refundRequest->load('booking'),
        ], 201);
    }

    /**
     * Get a specific refund request.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $refund = RefundRequest::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with(['booking', 'booking.event', 'transaction'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $refund,
        ]);
    }

    /**
     * Cancel a refund request.
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $refund = RefundRequest::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // Check if refund can be cancelled
        if (!in_array($refund->status, ['pending', 'reviewing'])) {
            return response()->json([
                'success' => false,
                'message' => 'This refund request cannot be cancelled',
            ], 400);
        }

        $refund->cancel();

        return response()->json([
            'success' => true,
            'message' => 'Refund request cancelled successfully',
        ]);
    }

    /**
     * Check refund eligibility for a booking.
     */
    public function checkEligibility(Request $request, string $bookingId): JsonResponse
    {
        $booking = Booking::where('id', $bookingId)
            ->where('user_id', $request->user()->id)
            ->with('event')
            ->firstOrFail();

        $eligible = true;
        $reasons = [];

        // Check booking status
        if (!in_array($booking->status, ['confirmed', 'completed'])) {
            $eligible = false;
            $reasons[] = 'Booking is not confirmed';
        }

        // Check existing refund requests
        $existingRequest = RefundRequest::where('booking_id', $booking->id)
            ->whereIn('status', ['pending', 'reviewing', 'approved', 'processed'])
            ->first();

        if ($existingRequest) {
            $eligible = false;
            $reasons[] = 'A refund request already exists';
        }

        // Check event timing
        $hoursUntilEvent = now()->diffInHours($booking->event->event_date, false);
        if ($hoursUntilEvent <= 24 && $hoursUntilEvent >= 0) {
            $eligible = false;
            $reasons[] = 'Event is within 24 hours';
        }

        // Check if event has passed
        if ($booking->event->event_date < now()) {
            $eligible = false;
            $reasons[] = 'Event has already passed';
        }

        return response()->json([
            'success' => true,
            'data' => [
                'eligible' => $eligible,
                'reasons' => $reasons,
                'booking_amount' => $booking->total_amount,
                'max_refund_amount' => $eligible ? $booking->total_amount : 0,
                'event_date' => $booking->event->event_date,
                'hours_until_event' => max(0, $hoursUntilEvent),
            ],
        ]);
    }
}