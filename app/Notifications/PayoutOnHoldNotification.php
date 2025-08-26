<?php

namespace App\Notifications;

use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutOnHoldNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $payout;
    protected $reason;
    protected $emailContent;

    /**
     * Create a new notification instance.
     */
    public function __construct(Payout $payout, string $reason = '')
    {
        $this->payout = $payout;
        $this->reason = $reason;
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
            ->when(!empty($this->reason), function ($message) use ($content) {
                return $message->line($content['line3']);
            })
            ->line($content['line4'])
            ->action('Contact Support', url('/support'))
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
            'status' => 'on_hold',
            'reason' => $this->reason,
            'message' => 'Your payout request has been placed on hold for review.',
        ];
    }
    
    /**
     * Get email template from settings or use default
     */
    protected function getEmailTemplate(): array
    {
        // Check if custom template exists in database/cache
        $template = cache()->get('email_template_payout_on_hold');
        
        if ($template) {
            return $template;
        }
        
        // Default template
        return [
            'subject' => 'Payout Request On Hold',
            'greeting' => 'Hello {organizer_name},',
            'line1' => 'Your payout request of {currency} {amount} has been placed on hold for review.',
            'line2' => 'Reference: {reference}',
            'line3' => 'Reason: {reason}',
            'line4' => 'Our team will review your request and get back to you within 2-3 business days. If you have any questions, please don\'t hesitate to contact our support team.',
            'footer' => 'Thank you for your patience and understanding.'
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
            '{reason}' => $this->reason ?: 'Pending verification',
        ];
        
        $parsed = [];
        foreach ($template as $key => $value) {
            $parsed[$key] = strtr($value, $replacements);
        }
        
        return $parsed;
    }
}