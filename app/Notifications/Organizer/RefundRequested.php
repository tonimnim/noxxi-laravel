<?php

namespace App\Notifications\Organizer;

use App\Models\RefundRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RefundRequested extends Notification implements ShouldQueue
{
    use Queueable;

    protected RefundRequest $refundRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(RefundRequest $refundRequest)
    {
        $this->refundRequest = $refundRequest->load(['booking.event', 'user']);
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
        $amount = number_format($this->refundRequest->requested_amount, 2);
        $booking = $this->refundRequest->booking;
        $event = $booking->event;

        return (new MailMessage)
            ->subject("Refund Request - {$event->title}")
            ->greeting('Refund Request Received')
            ->line("A customer has requested a refund for {$event->title}")
            ->line("Customer: {$this->refundRequest->user->full_name}")
            ->line("Booking Reference: {$booking->booking_reference}")
            ->line("Amount Requested: {$currency} {$amount}")
            ->line("Reason: {$this->refundRequest->reason}")
            ->line("Event Date: {$event->event_date->format('F j, Y at g:i A')}")
            ->action('Review Request', url("/organizer/dashboard/refund-requests/{$this->refundRequest->id}"))
            ->line('Please review and process this refund request promptly.');
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $currency = $this->refundRequest->currency;
        $amount = number_format($this->refundRequest->requested_amount, 2);

        return [
            'format' => 'filament',
            'title' => 'New Refund Request',
            'body' => "{$this->refundRequest->user->full_name} requested {$currency} {$amount} refund",
            'icon' => 'heroicon-o-receipt-refund',
            'color' => 'warning',
            'url' => "/organizer/dashboard/refund-requests/{$this->refundRequest->id}",
            'actions' => [
                [
                    'name' => 'review',
                    'label' => 'Review Request',
                    'url' => "/organizer/dashboard/refund-requests/{$this->refundRequest->id}",
                    'color' => 'primary',
                ],
            ],
            'refund_request_id' => $this->refundRequest->id,
            'booking_id' => $this->refundRequest->booking_id,
            'event_id' => $this->refundRequest->booking->event_id,
            'amount' => $this->refundRequest->requested_amount,
            'customer' => $this->refundRequest->user->full_name,
        ];
    }
}
