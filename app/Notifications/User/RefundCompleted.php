<?php

namespace App\Notifications\User;

use App\Models\RefundRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RefundCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    protected RefundRequest $refundRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(RefundRequest $refundRequest)
    {
        $this->refundRequest = $refundRequest->load(['booking.event', 'transaction']);
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
            ->subject("Refund Completed - {$event->title}")
            ->greeting("Hello {$notifiable->full_name}!")
            ->line('Your refund has been successfully processed.')
            ->line("Event: {$event->title}")
            ->line("Booking Reference: {$booking->booking_reference}")
            ->line("Refund Amount: {$currency} {$amount}")
            ->line('The funds have been sent to your original payment method.')
            ->line('Depending on your payment provider, it may take 2-3 business days for the funds to appear in your account.')
            ->line('Transaction Reference: '.($this->refundRequest->transaction->gateway_reference ?? 'N/A'))
            ->action('View Details', url("/user/bookings/{$booking->id}"))
            ->line('Thank you for using NOXXI.');
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
            'title' => 'Refund Completed!',
            'body' => "{$currency} {$amount} has been refunded for {$event->title}",
            'icon' => 'heroicon-o-check-badge',
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
            'status' => 'completed',
            'transaction_reference' => $this->refundRequest->transaction->gateway_reference ?? null,
        ];
    }
}
