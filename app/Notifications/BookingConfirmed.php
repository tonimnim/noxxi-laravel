<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmed extends Notification implements ShouldQueue
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
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     * Uses admin-configurable email template.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $event = $this->booking->event;
        
        // Get email template from settings (admin-configured)
        $template = $this->getEmailTemplate();
        
        // Replace placeholders with actual values
        $variables = [
            '{customer_name}' => $notifiable->full_name,
            '{event_name}' => $event->title,
            '{event_date}' => $event->event_date->format('F j, Y g:i A'),
            '{venue_name}' => $event->venue_name,
            '{venue_address}' => $event->venue_address,
            '{booking_reference}' => $this->booking->booking_reference,
            '{total_amount}' => $this->booking->currency . ' ' . number_format($this->booking->total_amount, 2),
            '{ticket_count}' => $this->booking->tickets()->count(),
            '{organizer_name}' => $event->organizer->organization_name,
        ];
        
        $subject = str_replace(array_keys($variables), array_values($variables), $template['subject']);
        $body = str_replace(array_keys($variables), array_values($variables), $template['body']);
        
        $message = (new MailMessage)
            ->subject($subject)
            ->greeting($template['greeting'] ?? 'Hello ' . $notifiable->full_name . '!')
            ->line($body);
            
        // Only add action button if tickets should be viewed in app
        if ($template['show_app_button'] ?? true) {
            $message->line('')
                ->line('Your tickets are available in the Noxxi app.')
                ->line('Please open the app to view and manage your tickets.');
        }
        
        if ($template['footer'] ?? null) {
            $message->line('')
                ->line($template['footer']);
        }
        
        return $message;
    }
    
    /**
     * Get email template from admin settings (database)
     */
    private function getEmailTemplate(): array
    {
        // Fetch template from database
        $template = \DB::table('email_templates')
            ->where('name', 'booking_confirmed')
            ->where('is_active', true)
            ->first();
        
        if ($template) {
            $settings = json_decode($template->settings, true) ?? [];
            return [
                'subject' => $template->subject,
                'greeting' => $template->greeting,
                'body' => $template->body,
                'footer' => $template->footer,
                'show_app_button' => $settings['show_app_reminder'] ?? true,
            ];
        }
        
        // Fallback to default if template not found
        return [
            'subject' => 'Booking Confirmed - {event_name}',
            'greeting' => 'Hello {customer_name}!',
            'body' => "Thank you for purchasing tickets for {event_name}.\n\n" .
                     "Your tickets are available in the Noxxi app.",
            'show_app_button' => true,
            'footer' => 'Thank you for using Noxxi!',
        ];
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $event = $this->booking->event;
        
        return [
            'type' => 'booking_confirmed',
            'booking_id' => $this->booking->id,
            'event_id' => $event->id,
            'title' => 'Booking Confirmed',
            'message' => 'Your booking for ' . $event->title . ' has been confirmed.',
            'booking_reference' => $this->booking->booking_reference,
            'amount' => $this->booking->total_amount,
            'currency' => $this->booking->currency,
            'event_date' => $event->event_date->toIso8601String(),
            'venue' => $event->venue_name,
        ];
    }
}