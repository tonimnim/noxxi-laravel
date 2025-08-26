<?php

namespace App\Notifications;

use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $payout;
    protected $emailContent;

    /**
     * Create a new notification instance.
     */
    public function __construct(Payout $payout)
    {
        $this->payout = $payout;
        // Get email template from settings or use default
        $this->emailContent = $this->getEmailTemplate();
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
        $content = $this->parseTemplate($this->emailContent);
        
        return (new MailMessage)
            ->subject($content['subject'])
            ->greeting($content['greeting'])
            ->line($content['line1'])
            ->line($content['line2'])
            ->line($content['line3'])
            ->action('View Payout Details', url('/organizer/dashboard/payouts'))
            ->line($content['footer']);
    }

    /**
     * Get the array representation of the notification for database.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'payout_id' => $this->payout->id,
            'reference' => $this->payout->reference,
            'amount' => $this->payout->net_amount,
            'currency' => $this->payout->currency,
            'status' => 'approved',
            'message' => 'Your payout request has been approved and will be processed shortly.',
        ];
    }
    
    /**
     * Get email template from settings or use default
     */
    protected function getEmailTemplate(): array
    {
        // Check if custom template exists in database/cache
        $template = cache()->get('email_template_payout_approved');
        
        if ($template) {
            return $template;
        }
        
        // Default template
        return [
            'subject' => 'Payout Request Approved',
            'greeting' => 'Hello {organizer_name}!',
            'line1' => 'Good news! Your payout request of {currency} {amount} has been approved.',
            'line2' => 'Reference: {reference}',
            'line3' => 'The funds will be transferred to your registered {payment_method} within 24-48 hours.',
            'footer' => 'Thank you for using our platform!'
        ];
    }
    
    /**
     * Parse template with actual values
     */
    protected function parseTemplate($template): array
    {
        $replacements = [
            '{organizer_name}' => $this->payout->organizer->business_name,
            '{currency}' => $this->payout->currency ?? 'KES',
            '{amount}' => number_format($this->payout->net_amount, 2),
            '{reference}' => $this->payout->reference,
            '{payment_method}' => ucfirst($this->payout->payment_method ?? 'account'),
        ];
        
        $parsed = [];
        foreach ($template as $key => $value) {
            $parsed[$key] = strtr($value, $replacements);
        }
        
        return $parsed;
    }
}