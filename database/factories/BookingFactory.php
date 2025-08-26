<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Booking::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 5);
        $subtotal = $this->faker->randomFloat(2, 100, 5000);
        $serviceFee = round($subtotal * 0.05, 2); // 5% service fee
        $totalAmount = $subtotal + $serviceFee;

        return [
            'event_id' => Event::factory(),
            'user_id' => User::factory(),
            'booking_reference' => 'BK-'.strtoupper(Str::random(8)),
            'customer_name' => $this->faker->name(),
            'customer_email' => $this->faker->safeEmail(),
            'customer_phone' => $this->faker->phoneNumber(),
            'ticket_types' => [
                [
                    'name' => 'Regular',
                    'price' => $subtotal / $quantity,
                    'quantity' => $quantity,
                ],
            ],
            'ticket_quantity' => $quantity,
            'quantity' => $quantity, // Alias for ticket_quantity
            'subtotal' => $subtotal,
            'service_fee' => $serviceFee,
            'total_amount' => $totalAmount,
            'currency' => 'KES',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => null,
            'payment_reference' => null,
            'booking_metadata' => [],
            'expires_at' => now()->addMinutes(30),
        ];
    }

    /**
     * Indicate that the booking is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_method' => 'mpesa',
            'payment_reference' => 'PAY-'.strtoupper(Str::random(10)),
            'expires_at' => null,
        ]);
    }

    /**
     * Indicate that the booking is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'payment_status' => 'unpaid',
        ]);
    }

    /**
     * Indicate that the booking is refunded.
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'refunded',
            'payment_status' => 'refunded',
        ]);
    }

    /**
     * Set specific currency for the booking.
     */
    public function withCurrency(string $currency): static
    {
        return $this->state(fn (array $attributes) => [
            'currency' => $currency,
        ]);
    }
}
