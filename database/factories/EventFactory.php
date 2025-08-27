<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Organizer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->sentence(3);
        $minPrice = $this->faker->randomFloat(2, 100, 1000);
        $maxPrice = $this->faker->randomFloat(2, $minPrice, 5000);
        $eventDate = $this->faker->dateTimeBetween('+1 week', '+6 months');
        $endDate = (clone $eventDate)->modify('+'.$this->faker->numberBetween(2, 12).' hours');

        return [
            'organizer_id' => Organizer::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.$this->faker->unique()->randomNumber(5),
            'description' => $this->faker->paragraphs(3, true),
            'category_id' => function () {
                // Try to find an existing category or create one
                return EventCategory::firstOrCreate(
                    ['slug' => 'events'],
                    [
                        'name' => 'Events',
                        'parent_id' => null,
                        'color_hex' => '#3B82F6',
                        'display_order' => 1,
                        'is_active' => true,
                        'is_featured' => false,
                    ]
                )->id;
            },
            'venue_name' => $this->faker->company().' Arena',
            'venue_address' => $this->faker->address(),
            'latitude' => $this->faker->latitude(-4.0, 1.5), // Kenya region
            'longitude' => $this->faker->longitude(34.0, 42.0), // Kenya region
            'city' => $this->faker->randomElement(['Nairobi', 'Mombasa', 'Kisumu', 'Nakuru', 'Eldoret']),
            'event_date' => $eventDate,
            'end_date' => $endDate,
            'ticket_types' => [
                [
                    'name' => 'Regular',
                    'price' => $minPrice,
                    'quantity' => 100,
                    'description' => 'General admission ticket',
                    'max_per_order' => 10,
                    'transferable' => true,
                    'refundable' => false,
                    'sale_start' => now()->format('Y-m-d H:i:s'),
                    'sale_end' => $eventDate->format('Y-m-d H:i:s'),
                ],
            ],
            'capacity' => 500,
            'tickets_sold' => 0,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'currency' => 'KES',
            'images' => [],
            'cover_image_url' => $this->faker->imageUrl(800, 400, 'event'),
            'tags' => implode(',', $this->faker->words(5)),
            'seo_keywords' => implode(',', $this->faker->words(5)),
            'status' => 'published',
            'featured' => false,
            'featured_until' => null,
            'requires_approval' => false,
            'age_restriction' => 0,
            'terms_conditions' => $this->faker->paragraph(),
            'refund_policy' => 'No refunds unless event is cancelled',
            'offline_mode_data' => [],
            'view_count' => 0,
            'share_count' => 0,
            'published_at' => now(),
            'media' => [],
            'policies' => [],
            'marketing' => [],
            'category_metadata' => [],
            'qr_secret_key' => Str::random(32),
            'gates_config' => [],
            'ticket_sales_config' => [],
            'first_published_at' => now(),
            'last_modified_at' => now(),
            'modified_by' => 'system',
            'analytics' => [],
            'draft_data' => null,
            'draft_saved_at' => null,
            'is_featured' => false,
            'commission_rate' => null,
            'commission_type' => 'percentage',
        ];
    }

    /**
     * Indicate that the event is in draft status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
            'first_published_at' => null,
        ]);
    }

    /**
     * Indicate that the event is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Indicate that the event is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'featured' => true,
            'is_featured' => true,
            'featured_until' => now()->addDays(30),
        ]);
    }

    /**
     * Indicate that the event is in the past.
     */
    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_date' => $this->faker->dateTimeBetween('-6 months', '-1 day'),
            'end_date' => $this->faker->dateTimeBetween('-6 months', '-1 day'),
            'status' => 'completed',
        ]);
    }

    /**
     * Set specific ticket types for the event.
     */
    public function withTicketTypes(array $ticketTypes): static
    {
        return $this->state(fn (array $attributes) => [
            'ticket_types' => $ticketTypes,
        ]);
    }

    /**
     * Set a specific currency for the event.
     */
    public function withCurrency(string $currency): static
    {
        return $this->state(fn (array $attributes) => [
            'currency' => $currency,
        ]);
    }
}
