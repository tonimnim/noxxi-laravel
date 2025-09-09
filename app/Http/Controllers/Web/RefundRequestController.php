<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\RefundRequest;
use App\Services\NotificationService;
use App\Services\RefundService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefundRequestController extends Controller
{
    use ApiResponse;

    protected RefundService $refundService;
    protected NotificationService $notificationService;

    public function __construct(RefundService $refundService, NotificationService $notificationService)
    {
        $this->refundService = $refundService;
        $this->notificationService = $notificationService;
    }

    /**
     * Create a new refund request (for web authenticated users)
     */
    public function store(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|uuid|exists:bookings,id',
            'ticket_id' => 'nullable|uuid|exists:tickets,id',
            'requested_amount' => 'required|numeric|min:0',
            'currency' => 'required|string',
            'reason' => 'required|string|max:500',
            'customer_message' => 'nullable|string|max:1000',
        ]);

        try {
            $user = auth()->user();
            
            // Get the booking and verify ownership
            $booking = Booking::with(['event.organizer', 'tickets'])->find($request->booking_id);
            
            if (!$booking) {
                return $this->error('Booking not found', 404);
            }
            
            if ($booking->user_id !== $user->id) {
                return $this->error('You are not authorized to request a refund for this booking', 403);
            }
            
            // Validate the refund request
            $validation = $this->refundService->validateRefundRequest(
                $booking, 
                $user, 
                $request->requested_amount
            );
            
            if (!$validation['valid']) {
                return $this->error(implode(' ', $validation['errors']), 400);
            }
            
            // Calculate refund amount based on policy
            $refundCalculation = $this->refundService->calculateRefundAmount($booking);
            
            // Create refund request with database lock to prevent concurrent requests
            $refundRequest = DB::transaction(function () use ($booking, $user, $request, $refundCalculation) {
                // Lock the booking record
                $lockedBooking = Booking::where('id', $booking->id)->lockForUpdate()->first();
                
                // Check for existing pending requests again with lock
                $existingRequest = RefundRequest::where('booking_id', $lockedBooking->id)
                    ->whereIn('status', [
                        RefundRequest::STATUS_PENDING,
                        RefundRequest::STATUS_REVIEWING,
                        RefundRequest::STATUS_APPROVED,
                        'processing'
                    ])
                    ->first();
                    
                if ($existingRequest) {
                    throw new \Exception('A refund request is already pending for this booking.');
                }
                
                // Create the refund request
                return RefundRequest::create([
                    'booking_id' => $lockedBooking->id,
                    'user_id' => $user->id,
                    'reason' => $request->reason,
                    'requested_amount' => min($request->requested_amount, $refundCalculation['total_refund_amount']),
                    'currency' => $lockedBooking->currency,
                    'status' => RefundRequest::STATUS_PENDING,
                    'customer_message' => $request->customer_message ?? $request->reason,
                ]);
            });
            
            // Send notifications to organizer
            try {
                // Notify organizer
                $organizer = $booking->event->organizer;
                if ($organizer && $organizer->user) {
                    // Manually insert notification to database
                    DB::table('notifications')->insert([
                        'id' => \Str::uuid(),
                        'type' => 'App\Notifications\Organizer\RefundRequested',
                        'notifiable_type' => 'App\Models\User',
                        'notifiable_id' => $organizer->user->id,
                        'data' => json_encode([
                            'format' => 'filament',
                            'title' => 'New Refund Request',
                            'body' => "{$user->full_name} requested {$refundRequest->currency} {$refundRequest->requested_amount} refund for {$booking->event->title}",
                            'icon' => 'heroicon-o-receipt-refund',
                            'color' => 'warning',
                            'url' => "/organizer/dashboard/refund-requests/{$refundRequest->id}",
                            'refund_request_id' => $refundRequest->id,
                            'booking_id' => $refundRequest->booking_id,
                            'event_id' => $booking->event_id,
                            'amount' => $refundRequest->requested_amount,
                            'currency' => $refundRequest->currency,
                            'customer' => $user->full_name ?? $user->email,
                            'reason' => $refundRequest->reason,
                        ]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    Log::info('Refund request notification sent to organizer', [
                        'organizer_id' => $organizer->user->id,
                        'refund_request_id' => $refundRequest->id,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to send refund request notification', [
                    'refund_request_id' => $refundRequest->id,
                    'error' => $e->getMessage(),
                ]);
            }
            
            return $this->success([
                'id' => $refundRequest->id,
                'status' => $refundRequest->status,
                'requested_amount' => $refundRequest->requested_amount,
                'currency' => $refundRequest->currency,
                'message' => 'Your refund request has been submitted successfully. The event organizer has been notified.',
            ], 'Refund request created successfully', 201);
            
        } catch (\Exception $e) {
            Log::error('Error creating refund request', [
                'user_id' => auth()->id(),
                'booking_id' => $request->booking_id,
                'error' => $e->getMessage(),
            ]);
            
            return $this->error($e->getMessage(), 400);
        }
    }
}