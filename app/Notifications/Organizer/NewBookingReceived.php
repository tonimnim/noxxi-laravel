<?php

namespace App\Notifications\Organizer;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewBookingReceived extends Notification implements ShouldQueue
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
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $currency = $this->booking->currency ?? 'KES';
        $amount = number_format($this->booking->total_amount, 0);
        
        return (new MailMessage)
            ->subject("New Booking - {$this->booking->event->title}")
            ->greeting("Great news!")
            ->line("You have received a new booking for {$this->booking->event->title}")
            ->line("Customer: {$this->booking->customer_name}")
            ->line("Tickets: {$this->booking->quantity}")
            ->line("Total Amount: {$currency} {$amount}")
            ->action('View Booking', url("/organizer/dashboard/bookings/{$this->booking->id}"))
            ->line('The payment has been confirmed and tickets have been issued.');
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $currency = $this->booking->currency ?? 'KES';
        $amount = number_format($this->booking->total_amount, 0);
        
        return [
            'format' => 'filament',
            'title' => 'New Booking Received!',
            'body' => "{$this->booking->customer_name} booked {$this->booking->quantity} tickets for {$currency} {$amount}",
            'icon' => 'heroicon-o-ticket',
            'color' => 'success',
            'url' => "/organizer/dashboard/bookings/{$this->booking->id}",
            'actions' => [
                [
                    'name' => 'view',
                    'label' => 'View Details',
                    'url' => "/organizer/dashboard/bookings/{$this->booking->id}",
                    'color' => 'primary',
                ]
            ],
            'booking_id' => $this->booking->id,
            'event_id' => $this->booking->event_id,
            'amount' => $this->booking->total_amount,
            'quantity' => $this->booking->quantity,
        ];
    }
}