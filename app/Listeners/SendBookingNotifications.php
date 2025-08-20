<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Notifications\Organizer\NewBookingReceived;
use App\Notifications\User\BookingConfirmation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendBookingNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(BookingCreated $event): void
    {
        $booking = $event->booking;
        
        // Notify the customer
        if ($booking->user) {
            $booking->user->notify(new BookingConfirmation($booking));
        }
        
        // Notify the organizer
        if ($booking->event && $booking->event->organizer && $booking->event->organizer->user) {
            $booking->event->organizer->user->notify(new NewBookingReceived($booking));
        }
    }
}