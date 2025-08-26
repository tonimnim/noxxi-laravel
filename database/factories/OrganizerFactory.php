<?php

namespace Database\Factories;

use App\Models\Organizer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Organizer>
 */
class OrganizerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Organizer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'business_name' => $this->faker->company(),
            'business_description' => $this->faker->paragraph(),
            'business_logo_url' => $this->faker->imageUrl(200, 200, 'business'),
            'business_country' => $this->faker->randomElement(['KE', 'NG', 'ZA', 'GH', 'UG', 'TZ', 'EG']),
            'business_address' => $this->faker->address(),
            'business_timezone' => 'Africa/Nairobi',
            'business_type' => 'company',
            'payment_methods' => ['mpesa', 'card'],
            'default_currency' => $this->faker->randomElement(['KES', 'NGN', 'ZAR', 'GHS', 'UGX', 'TZS', 'EGP', 'USD']),
            'total_events' => 0,
            'total_tickets_sold' => 0,
            'total_revenue' => [],
            'rating' => 0,
            'total_reviews' => 0,
            'commission_rate' => $this->faker->randomFloat(2, 5, 20), // 5% to 20%
            'settlement_period_days' => 7,
            'auto_approve_events' => true,
            'is_active' => true,
            'is_featured' => false,
            'is_verified' => true,
            'verification_status' => 'verified',
            'verified_at' => now(),
            'metadata' => [],
            'absorb_payout_fees' => false,
            'payout_method' => 'bank',
            'payout_frequency' => 'weekly',
            'bank_name' => $this->faker->randomElement(['Equity Bank', 'KCB Bank', 'Standard Chartered', 'Absa Bank']),
            'bank_account_number' => $this->faker->bankAccountNumber(),
            'bank_account_name' => $this->faker->name(),
            'mpesa_number' => null,
            'bank_details' => [],
            'status' => 'normal',
            'priority_support' => false,
            'auto_featured_listings' => false,
            'monthly_volume' => 0,
            'total_commission_paid' => 0,
            'premium_since' => null,
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'last_payout_at' => null,
        ];
    }

    /**
     * Indicate that the organizer is premium.
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'premium',
            'priority_support' => true,
            'auto_featured_listings' => true,
            'commission_rate' => 5, // Lower commission for premium
            'premium_since' => now(),
        ]);
    }

    /**
     * Indicate that the organizer is not verified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => false,
        ]);
    }

    /**
     * Indicate that the organizer uses M-Pesa payout.
     */
    public function withMpesa(): static
    {
        return $this->state(fn (array $attributes) => [
            'payout_method' => 'mpesa',
            'mpesa_number' => '+254'.$this->faker->numerify('#########'),
        ]);
    }
}
