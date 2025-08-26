<?php

namespace App\Notifications;

use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;

class PayoutCompletedNotification extends Notification implements ShouldQueue
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
        $channels = ['mail', 'database'];

        // Add SMS if phone number is available
        if ($notifiable->phone_number) {
            $channels[] = 'vonage';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $currency = $this->payout->currency ?? 'KES';
        $amount = number_format($this->payout->net_amount, 2);
        $method = ucfirst($this->payout->payment_method);

        return (new MailMessage)
            ->subject('Payout Completed - '.$currency.' '.$amount)
            ->greeting('Hello '.$notifiable->full_name.',')
            ->line('Great news! Your payout has been successfully processed.')
            ->line('**Payout Details:**')
            ->line('Reference: '.$this->payout->reference)
            ->line('Amount: '.$currency.' '.$amount)
            ->line('Method: '.$method)
            ->line('Date: '.$this->payout->completed_at->format('d M Y, H:i'))
            ->action('View Payout Details', url('/organizer/payouts/'.$this->payout->id))
            ->line('The funds should reflect in your account within:')
            ->line('• M-Pesa: Within minutes')
            ->line('• Bank Transfer: 1-2 business days')
            ->line('Thank you for using Noxxi!');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toVonage(object $notifiable): VonageMessage
    {
        $currency = $this->payout->currency ?? 'KES';
        $amount = number_format($this->payout->net_amount, 2);

        return (new VonageMessage)
            ->content("Noxxi: Your payout of {$currency} {$amount} has been completed. Ref: {$this->payout->reference}. Check your email for details.");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payout_completed',
            'payout_id' => $this->payout->id,
            'reference' => $this->payout->reference,
            'amount' => $this->payout->net_amount,
            'currency' => $this->payout->currency,
            'method' => $this->payout->payment_method,
            'completed_at' => $this->payout->completed_at->toIso8601String(),
        ];
    }
}
