<?php

namespace App\Notifications\User;

use App\Models\RefundRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RefundRejected extends Notification implements ShouldQueue
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
        $booking = $this->refundRequest->booking;
        $event = $booking->event;
        $adminResponse = $this->refundRequest->admin_response;

        $mail = (new MailMessage)
            ->subject("Refund Request Update - {$event->title}")
            ->greeting("Hello {$notifiable->full_name},")
            ->line('We have reviewed your refund request and unfortunately cannot approve it at this time.')
            ->line("Event: {$event->title}")
            ->line("Booking Reference: {$booking->booking_reference}");

        if ($adminResponse) {
            $mail->line("Message from organizer: {$adminResponse}");
        }

        $mail->line('If you have any questions or concerns, please contact our support team.')
            ->action('View Booking', url("/user/bookings/{$booking->id}"))
            ->line('Thank you for your understanding.');

        return $mail;
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $event = $this->refundRequest->booking->event;

        return [
            'format' => 'filament',
            'title' => 'Refund Request Update',
            'body' => "Your refund request for {$event->title} has been reviewed",
            'icon' => 'heroicon-o-x-circle',
            'color' => 'danger',
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
            'status' => 'rejected',
            'rejection_reason' => $this->refundRequest->rejection_reason,
        ];
    }
}
