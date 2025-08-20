<?php

namespace App\Notifications\User;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    protected Booking $booking;

    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database', 'mail'];
        
        // Add SMS if phone number exists
        if ($notifiable->phone_number) {
            $channels[] = 'sms';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $currency = $this->booking->currency ?? 'KES';
        $amount = number_format($this->booking->total_amount, 0);
        
        return (new MailMessage)
            ->subject("Booking Confirmed - {$this->booking->event->title}")
            ->greeting("Hello {$this->booking->customer_name}!")
            ->line("Your booking has been confirmed!")
            ->line("Event: {$this->booking->event->title}")
            ->line("Date: {$this->booking->event->event_date->format('F j, Y at g:i A')}")
            ->line("Venue: {$this->booking->event->venue_name}")
            ->line("Tickets: {$this->booking->quantity}")
            ->line("Total Paid: {$currency} {$amount}")
            ->line("Reference: {$this->booking->booking_reference}")
            ->action('View Tickets', url("/user/tickets/{$this->booking->id}"))
            ->line('Please present this email or your ticket QR code at the venue.')
            ->line('Thank you for using NOXXI!');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): array
    {
        $eventDate = $this->booking->event->event_date->format('M j');
        
        return [
            'message' => "NOXXI: Booking confirmed! {$this->booking->event->title} on {$eventDate}. " .
                        "Ref: {$this->booking->booking_reference}. " .
                        "Show this SMS or QR code at venue.",
            'recipient' => $notifiable->phone_number,
        ];
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'format' => 'filament',
            'title' => 'Booking Confirmed!',
            'body' => "Your tickets for {$this->booking->event->title} are ready",
            'icon' => 'heroicon-o-check-circle',
            'color' => 'success',
            'url' => "/user/tickets/{$this->booking->id}",
            'actions' => [
                [
                    'name' => 'view_tickets',
                    'label' => 'View Tickets',
                    'url' => "/user/tickets/{$this->booking->id}",
                    'color' => 'primary',
                ],
                [
                    'name' => 'add_to_calendar',
                    'label' => 'Add to Calendar',
                    'url' => "/user/tickets/{$this->booking->id}/calendar",
                    'color' => 'gray',
                ]
            ],
            'booking_id' => $this->booking->id,
            'event_id' => $this->booking->event_id,
            'event_title' => $this->booking->event->title,
            'event_date' => $this->booking->event->event_date->toISOString(),
            'reference' => $this->booking->booking_reference,
        ];
    }
}