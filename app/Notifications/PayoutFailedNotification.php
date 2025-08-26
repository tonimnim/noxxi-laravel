<?php

namespace App\Notifications;

use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Payout $payout;

    protected string $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct(Payout $payout, string $reason = '')
    {
        $this->payout = $payout;
        $this->reason = $reason ?: 'Processing error occurred';
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
        $currency = $this->payout->currency ?? 'KES';
        $amount = number_format($this->payout->net_amount, 2);

        return (new MailMessage)
            ->subject('Payout Failed - Action Required')
            ->greeting('Hello '.$notifiable->full_name.',')
            ->line('Unfortunately, your payout request could not be processed.')
            ->line('**Payout Details:**')
            ->line('Reference: '.$this->payout->reference)
            ->line('Amount: '.$currency.' '.$amount)
            ->line('Reason: '.$this->reason)
            ->line('The amount has been returned to your available balance and you can request a new payout.')
            ->action('Request New Payout', url('/organizer/payout-request'))
            ->line('If you continue to experience issues, please contact our support team.')
            ->salutation('Best regards, The Noxxi Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payout_failed',
            'payout_id' => $this->payout->id,
            'reference' => $this->payout->reference,
            'amount' => $this->payout->net_amount,
            'currency' => $this->payout->currency,
            'reason' => $this->reason,
            'failed_at' => now()->toIso8601String(),
        ];
    }
}
