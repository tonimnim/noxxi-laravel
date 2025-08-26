<?php

namespace Database\Factories;

use App\Models\EventCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventCategory>
 */
class EventCategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EventCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['Events', 'Sports', 'Cinema', 'Experiences'];
        $name = $this->faker->unique()->randomElement($categories);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'parent_id' => null, // Parent category by default
            'color_hex' => $this->faker->hexColor(),
            'display_order' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
            'is_featured' => false,
        ];
    }

    /**
     * Indicate that the category is a subcategory.
     */
    public function subcategory(): static
    {
        return $this->state(function (array $attributes) {
            // Create or get a parent category
            $parent = EventCategory::firstOrCreate(
                ['slug' => 'events'],
                [
                    'name' => 'Events',
                    'slug' => 'events',
                    'parent_id' => null,
                    'color_hex' => '#3B82F6',
                    'display_order' => 1,
                    'is_active' => true,
                    'is_featured' => true,
                ]
            );

            return [
                'parent_id' => $parent->id,
                'is_featured' => false,
            ];
        });
    }

    /**
     * Indicate that the category is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
