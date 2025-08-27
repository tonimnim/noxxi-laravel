<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'event_id' => Event::factory(),
            'ticket_code' => strtoupper(Str::random(10)),
            'ticket_hash' => hash('sha256', Str::random(32)),
            'ticket_type' => 'Regular',
            'price' => $this->faker->randomFloat(2, 100, 5000),
            'currency' => 'KES',
            'seat_number' => null,
            'seat_section' => null,
            'holder_name' => $this->faker->name(),
            'holder_email' => $this->faker->safeEmail(),
            'holder_phone' => $this->faker->phoneNumber(),
            'assigned_to' => null,
            'status' => 'valid',
            'used_at' => null,
            'used_by' => null,
            'entry_gate' => null,
            'device_fingerprint' => null,
            'transferred_from' => null,
            'transferred_to' => null,
            'transferred_at' => null,
            'transfer_reason' => null,
            'special_requirements' => null,
            'notes' => null,
            'valid_from' => now(),
            'valid_until' => now()->addMonths(6),
            'offline_validation_data' => null,
            'metadata' => [],
        ];
    }

    /**
     * Indicate that the ticket has been used.
     */
    public function used(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'used',
            'used_at' => now(),
            'used_by' => User::factory(),
            'entry_gate' => 'Main',
            'device_fingerprint' => Str::random(32),
        ]);
    }

    /**
     * Indicate that the ticket has been cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_reason' => 'Booking refunded',
        ]);
    }

    /**
     * Indicate that the ticket has been transferred.
     */
    public function transferred(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'transferred',
            'transferred_from' => User::factory(),
            'transferred_to' => User::factory(),
            'transferred_at' => now(),
            'transfer_reason' => 'Gift to friend',
        ]);
    }
}