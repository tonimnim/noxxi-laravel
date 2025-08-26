<?php

namespace App\Notifications\User;

use App\Models\RefundRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RefundApproved extends Notification implements ShouldQueue
{
    use Queueable;

    protected RefundRequest $refundRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(RefundRequest $refundRequest)
    {
        $this->refundRequest = $refundRequest->load(['booking.event']);
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
        $currency = $this->refundRequest->currency;
        $amount = number_format($this->refundRequest->approved_amount ?? $this->refundRequest->requested_amount, 2);
        $booking = $this->refundRequest->booking;
        $event = $booking->event;

        return (new MailMessage)
            ->subject("Refund Approved - {$event->title}")
            ->greeting("Hello {$notifiable->full_name}!")
            ->line('Good news! Your refund request has been approved.')
            ->line("Event: {$event->title}")
            ->line("Booking Reference: {$booking->booking_reference}")
            ->line("Refund Amount: {$currency} {$amount}")
            ->line('The refund will be processed to your original payment method within 3-5 business days.')
            ->line('You will receive a confirmation once the refund is completed.')
            ->action('View Details', url("/user/bookings/{$booking->id}"))
            ->line('Thank you for your patience.');
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $currency = $this->refundRequest->currency;
        $amount = number_format($this->refundRequest->approved_amount ?? $this->refundRequest->requested_amount, 2);
        $event = $this->refundRequest->booking->event;

        return [
            'format' => 'filament',
            'title' => 'Refund Approved!',
            'body' => "{$currency} {$amount} refund approved for {$event->title}",
            'icon' => 'heroicon-o-check-circle',
            'color' => 'success',
            'url' => "/user/bookings/{$this->refundRequest->booking_id}",
            'actions' => [
                [
                    'name' => 'view',
                    'label' => 'View Details',
                    'url' => "/user/bookings/{$this->refundRequest->booking_id}",
                    'color' => 'primary',
                ],
            ],
            'refund_request_id' => $this->refundRequest->id,
            'booking_id' => $this->refundRequest->booking_id,
            'event_id' => $event->id,
            'amount' => $this->refundRequest->approved_amount ?? $this->refundRequest->requested_amount,
            'status' => 'approved',
        ];
    }
}
