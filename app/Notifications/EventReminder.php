<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventReminder extends Notification implements ShouldQueue
{
    use Queueable;

    protected Event $event;
    protected Booking $booking;
    protected int $hoursBeforeEvent;

    /**
     * Create a new notification instance.
     */
    public function __construct(Event $event, Booking $booking, int $hoursBeforeEvent = 24)
    {
        $this->event = $event;
        $this->booking = $booking;
        $this->hoursBeforeEvent = $hoursBeforeEvent;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->hoursBeforeEvent <= 24 
            ? 'Event Tomorrow: ' . $this->event->title
            : 'Event Reminder: ' . $this->event->title;
        
        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello ' . $notifiable->full_name . '!')
            ->line('This is a reminder about your upcoming event.')
            ->line('**Event:** ' . $this->event->title)
            ->line('**Date:** ' . $this->event->event_date->format('F j, Y g:i A'))
            ->line('**Venue:** ' . $this->event->venue_name)
            ->line('**City:** ' . $this->event->city)
            ->line('**Booking Reference:** ' . $this->booking->booking_reference)
            ->action('View Your Tickets', url('/bookings/' . $this->booking->id))
            ->line('We look forward to seeing you there!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'event_reminder',
            'event_id' => $this->event->id,
            'booking_id' => $this->booking->id,
            'title' => 'Event Reminder',
            'message' => 'Your event "' . $this->event->title . '" is coming up soon!',
            'event_date' => $this->event->event_date->toIso8601String(),
            'venue' => $this->event->venue_name,
            'city' => $this->event->city,
            'hours_before' => $this->hoursBeforeEvent,
        ];
    }
}