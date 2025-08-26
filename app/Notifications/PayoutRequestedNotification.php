<?php

namespace App\Notifications;

use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Payout $payout;

    /**
     * Create a new notification instance.
     */
    public function __construct(Payout $payout)
    {
        $this->payout = $payout;
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
        $organizer = $this->payout->organizer;
        $currency = $this->payout->currency ?? 'KES';
        $amount = number_format($this->payout->net_amount, 2);
        $method = strtoupper($this->payout->payout_method);

        return (new MailMessage)
            ->subject('New Payout Request - ' . $organizer->business_name)
            ->greeting('Admin Alert')
            ->line('A new payout request has been submitted and requires approval.')
            ->line('**Request Details:**')
            ->line('Organizer: ' . $organizer->business_name)
            ->line('Amount: ' . $currency . ' ' . $amount)
            ->line('Method: ' . $method)
            ->line('Reference: ' . $this->payout->reference_number)
            ->action('Review Payout Request', url('/admin/payouts'))
            ->line('Please review and process this payout request promptly.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payout_requested',
            'payout_id' => $this->payout->id,
            'reference' => $this->payout->reference_number,
            'organizer' => $this->payout->organizer->business_name,
            'amount' => $this->payout->net_amount,
            'currency' => $this->payout->currency,
            'method' => $this->payout->payout_method,
            'requested_at' => $this->payout->requested_at->toIso8601String(),
        ];
    }
}