<?php

namespace App\Notifications\Organizer;

use App\Models\Booking;
use App\Services\FinancialCalculationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewBookingReceived extends Notification implements ShouldQueue
{
    use Queueable;

    protected Booking $booking;

    protected array $financialSummary;

    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;

        // Get financial summary for commission tracking
        $financialService = app(FinancialCalculationService::class);
        $this->financialSummary = $financialService->getBookingFinancialSummary($booking);
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
        $currency = $this->booking->currency ?? config('currencies.default', 'USD');
        $amount = number_format($this->booking->total_amount, 0);
        $commission = $this->financialSummary['commission'];
        $netAmount = number_format($this->financialSummary['organizer_net'], 2);

        return (new MailMessage)
            ->subject("New Booking - {$this->booking->event->title}")
            ->greeting('Great news!')
            ->line("You have received a new booking for {$this->booking->event->title}")
            ->line("Customer: {$this->booking->customer_name}")
            ->line("Tickets: {$this->booking->quantity}")
            ->line("Total Amount: {$currency} {$amount}")
            ->line('--- Financial Breakdown ---')
            ->line("Platform Commission ({$commission['rate']}%): {$currency} ".number_format($commission['amount'], 2))
            ->line("Gateway Fee: {$currency} ".number_format($this->financialSummary['gateway_fee'], 2))
            ->line("Your Net Revenue: {$currency} {$netAmount}")
            ->line('Commission Source: '.str_replace('_', ' ', ucfirst($commission['source'])))
            ->action('View Booking', url("/organizer/dashboard/bookings/{$this->booking->id}"))
            ->line('The payment has been confirmed and tickets have been issued.');
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $currency = $this->booking->currency ?? config('currencies.default', 'USD');
        $grossAmount = number_format($this->booking->total_amount, 0);
        $netAmount = number_format($this->financialSummary['organizer_net'], 2);

        return [
            'format' => 'filament',
            'title' => 'New Booking Received!',
            'body' => "{$this->booking->customer_name} booked {$this->booking->quantity} tickets - Net: {$currency} {$netAmount}",
            'icon' => 'heroicon-o-ticket',
            'color' => 'success',
            'url' => "/organizer/dashboard/bookings/{$this->booking->id}",
            'actions' => [
                [
                    'name' => 'view',
                    'label' => 'View Details',
                    'url' => "/organizer/dashboard/bookings/{$this->booking->id}",
                    'color' => 'primary',
                ],
            ],
            'booking_id' => $this->booking->id,
            'event_id' => $this->booking->event_id,
            'amount' => $this->booking->total_amount,
            'quantity' => $this->booking->quantity,

            // Add financial tracking for commission
            'financial_summary' => [
                'currency' => $this->financialSummary['currency'],
                'subtotal' => $this->financialSummary['subtotal'],
                'service_fee' => $this->financialSummary['service_fee'],
                'total_amount' => $this->financialSummary['total_amount'],
                'commission' => [
                    'amount' => $this->financialSummary['commission']['amount'],
                    'rate' => $this->financialSummary['commission']['rate'],
                    'type' => $this->financialSummary['commission']['type'],
                    'source' => $this->financialSummary['commission']['source'],
                ],
                'gateway_fee' => $this->financialSummary['gateway_fee'],
                'organizer_net' => $this->financialSummary['organizer_net'],
                'transaction_id' => $this->financialSummary['transaction_id'],
            ],
        ];
    }
}
