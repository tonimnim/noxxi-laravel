<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Event;
use App\Models\RefundRequest;
use App\Models\User;
use App\Notifications\BookingConfirmed;
use App\Notifications\EventReminder;
use App\Notifications\Organizer\RefundRequested;
use App\Notifications\User\RefundApproved;
use App\Notifications\User\RefundCompleted;
use App\Notifications\User\RefundRejected;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send booking confirmation notification.
     * Email template is managed by admin - tickets are only available in the app.
     */
    public function sendBookingConfirmation(Booking $booking): void
    {
        try {
            $user = $booking->user;

            // Send email using admin-configured template
            // The BookingConfirmed notification class will use the template
            $user->notify(new BookingConfirmed($booking));

            Log::info('Booking confirmation email sent - tickets available in app', [
                'user_id' => $user->id,
                'booking_id' => $booking->id,
                'ticket_count' => $booking->tickets()->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send booking confirmation', [
                'error' => $e->getMessage(),
                'booking_id' => $booking->id,
            ]);
        }
    }

    /**
     * Send event reminders to all attendees.
     * Should be called by a scheduled job.
     */
    public function sendEventReminders(int $hoursBeforeEvent = 24): int
    {
        $count = 0;
        $reminderTime = Carbon::now()->addHours($hoursBeforeEvent);

        // Find events happening within the specified time window
        $events = Event::where('event_date', '>=', $reminderTime->copy()->subMinutes(30))
            ->where('event_date', '<=', $reminderTime->copy()->addMinutes(30))
            ->where('is_active', true)
            ->get();

        foreach ($events as $event) {
            // Get all confirmed bookings for this event
            $bookings = $event->bookings()
                ->where('status', 'confirmed')
                ->with('user')
                ->get();

            foreach ($bookings as $booking) {
                try {
                    // Check if reminder was already sent
                    $alreadySent = $booking->user->notifications()
                        ->where('type', 'App\\Notifications\\EventReminder')
                        ->where('created_at', '>', Carbon::now()->subHours(12))
                        ->whereJsonContains('data->event_id', $event->id)
                        ->exists();

                    if (! $alreadySent) {
                        $booking->user->notify(new EventReminder($event, $booking, $hoursBeforeEvent));
                        $count++;
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send event reminder', [
                        'error' => $e->getMessage(),
                        'booking_id' => $booking->id,
                        'event_id' => $event->id,
                    ]);
                }
            }
        }

        Log::info('Event reminders sent', [
            'count' => $count,
            'hours_before' => $hoursBeforeEvent,
        ]);

        return $count;
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->find($notificationId);

        if ($notification && is_null($notification->read_at)) {
            $notification->markAsRead();

            return true;
        }

        return false;
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(User $user): int
    {
        return $user->unreadNotifications->markAsRead();
    }

    /**
     * Get unread notification count for a user.
     */
    public function getUnreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    /**
     * Clean old read notifications (older than 30 days).
     */
    public function cleanOldNotifications(int $daysOld = 30): int
    {
        $date = Carbon::now()->subDays($daysOld);

        $count = \Illuminate\Notifications\DatabaseNotification::where('read_at', '<', $date)
            ->delete();

        Log::info('Old notifications cleaned', [
            'count' => $count,
            'days_old' => $daysOld,
        ]);

        return $count;
    }

    /**
     * Send notification when refund is requested.
     */
    public function sendRefundRequested(RefundRequest $refundRequest): void
    {
        try {
            // Notify organizer
            $organizer = $refundRequest->booking->event->organizer;
            if ($organizer->user) {
                $organizer->user->notify(new RefundRequested($refundRequest));

                Log::info('Refund request notification sent to organizer', [
                    'refund_request_id' => $refundRequest->id,
                    'organizer_id' => $organizer->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send refund request notification', [
                'error' => $e->getMessage(),
                'refund_request_id' => $refundRequest->id,
            ]);
        }
    }

    /**
     * Send notification when refund is approved.
     */
    public function sendRefundApproved(RefundRequest $refundRequest): void
    {
        try {
            $user = $refundRequest->user;
            $user->notify(new RefundApproved($refundRequest));

            Log::info('Refund approved notification sent to customer', [
                'refund_request_id' => $refundRequest->id,
                'user_id' => $user->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send refund approved notification', [
                'error' => $e->getMessage(),
                'refund_request_id' => $refundRequest->id,
            ]);
        }
    }

    /**
     * Send notification when refund is rejected.
     */
    public function sendRefundRejected(RefundRequest $refundRequest): void
    {
        try {
            $user = $refundRequest->user;
            $user->notify(new RefundRejected($refundRequest));

            Log::info('Refund rejected notification sent to customer', [
                'refund_request_id' => $refundRequest->id,
                'user_id' => $user->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send refund rejected notification', [
                'error' => $e->getMessage(),
                'refund_request_id' => $refundRequest->id,
            ]);
        }
    }

    /**
     * Send notification when refund is processed (money sent).
     */
    public function sendRefundProcessed(RefundRequest $refundRequest): void
    {
        try {
            $user = $refundRequest->user;
            $user->notify(new RefundCompleted($refundRequest));

            Log::info('Refund completed notification sent to customer', [
                'refund_request_id' => $refundRequest->id,
                'user_id' => $user->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send refund completed notification', [
                'error' => $e->getMessage(),
                'refund_request_id' => $refundRequest->id,
            ]);
        }
    }
}
