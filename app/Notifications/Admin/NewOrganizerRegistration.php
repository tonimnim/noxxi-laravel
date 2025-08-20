<?php

namespace App\Notifications\Admin;

use App\Models\Organizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrganizerRegistration extends Notification implements ShouldQueue
{
    use Queueable;

    protected Organizer $organizer;

    /**
     * Create a new notification instance.
     */
    public function __construct(Organizer $organizer)
    {
        $this->organizer = $organizer;
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
        return (new MailMessage)
            ->subject('New Organizer Registration - Verification Required')
            ->line("A new organizer has registered and requires verification:")
            ->line("Business Name: {$this->organizer->business_name}")
            ->line("Business Type: {$this->organizer->business_type}")
            ->line("Country: {$this->organizer->business_country}")
            ->action('Review Application', url("/admin/organizers/{$this->organizer->id}/edit"))
            ->line('Please review and verify this organizer account.');
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'format' => 'filament', // Required for Filament
            'title' => 'New Organizer Registration',
            'body' => "{$this->organizer->business_name} has registered and requires verification",
            'icon' => 'heroicon-o-building-office',
            'color' => 'warning',
            'url' => "/admin/organizers/{$this->organizer->id}/edit",
            'actions' => [
                [
                    'name' => 'review',
                    'label' => 'Review',
                    'url' => "/admin/organizers/{$this->organizer->id}/edit",
                    'color' => 'primary',
                ]
            ],
            'organizer_id' => $this->organizer->id,
            'organizer_name' => $this->organizer->business_name,
        ];
    }
}