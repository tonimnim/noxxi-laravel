<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentSuccessful extends Notification implements ShouldQueue
{
    use Queueable;

    protected Booking $booking;
    protected array $paymentDetails;

    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking, array $paymentDetails = [])
    {
        $this->booking = $booking;
        $this->paymentDetails = $paymentDetails;
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
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_successful',
            'booking_id' => $this->booking->id,
            'title' => 'Payment Successful',
            'message' => 'Your payment of ' . $this->booking->currency . ' ' . 
                        number_format($this->booking->total_amount, 2) . ' has been processed successfully.',
            'booking_reference' => $this->booking->booking_reference,
            'amount' => $this->booking->total_amount,
            'currency' => $this->booking->currency,
            'payment_method' => $this->paymentDetails['method'] ?? 'card',
            'transaction_id' => $this->paymentDetails['transaction_id'] ?? null,
        ];
    }
}
