<?php

namespace Database\Seeders;

use App\Models\EventCategory;
use Illuminate\Database\Seeder;

class EventCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Parent Categories
        $parentCategories = [
            [
                'name' => 'Events',
                'slug' => 'events',
                'color_hex' => '#3B82F6',
                'display_order' => 1,
                'is_active' => true,
                'is_featured' => true,
                'parent_id' => null,
            ],
            [
                'name' => 'Sports',
                'slug' => 'sports',
                'color_hex' => '#10B981',
                'display_order' => 2,
                'is_active' => true,
                'is_featured' => true,
                'parent_id' => null,
            ],
            [
                'name' => 'Cinema',
                'slug' => 'cinema',
                'color_hex' => '#EF4444',
                'display_order' => 3,
                'is_active' => true,
                'is_featured' => true,
                'parent_id' => null,
            ],
            [
                'name' => 'Experiences',
                'slug' => 'experiences',
                'color_hex' => '#F59E0B',
                'display_order' => 4,
                'is_active' => true,
                'is_featured' => true,
                'parent_id' => null,
            ],
            [
                'name' => 'Stays',
                'slug' => 'stays',
                'color_hex' => '#9C27B0',
                'display_order' => 5,
                'is_active' => true,
                'is_featured' => true,
                'parent_id' => null,
                'description' => 'Accommodation and lodging options',
            ],
        ];

        // Create parent categories first
        foreach ($parentCategories as $category) {
            EventCategory::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }

        // Subcategories
        $subcategories = [
            // Events subcategories
            [
                'name' => 'Concerts',
                'slug' => 'concerts',
                'parent_slug' => 'events',
                'display_order' => 1,
            ],
            [
                'name' => 'Festivals',
                'slug' => 'festivals',
                'parent_slug' => 'events',
                'display_order' => 2,
            ],
            [
                'name' => 'Comedy Shows',
                'slug' => 'comedy-shows',
                'parent_slug' => 'events',
                'display_order' => 3,
            ],
            [
                'name' => 'Theater & Plays',
                'slug' => 'theater-plays',
                'parent_slug' => 'events',
                'display_order' => 4,
            ],
            [
                'name' => 'Conferences & Workshops',
                'slug' => 'conferences-workshops',
                'parent_slug' => 'events',
                'display_order' => 5,
            ],
            
            // Sports subcategories (simplified to 6 key sports)
            [
                'name' => 'Football',
                'slug' => 'football',
                'parent_slug' => 'sports',
                'display_order' => 1,
            ],
            [
                'name' => 'Basketball',
                'slug' => 'basketball',
                'parent_slug' => 'sports',
                'display_order' => 2,
            ],
            [
                'name' => 'Rugby',
                'slug' => 'rugby',
                'parent_slug' => 'sports',
                'display_order' => 3,
            ],
            [
                'name' => 'Motorsports',
                'slug' => 'motorsports',
                'parent_slug' => 'sports',
                'display_order' => 4,
            ],
            [
                'name' => 'Pool',
                'slug' => 'pool',
                'parent_slug' => 'sports',
                'display_order' => 5,
            ],
            [
                'name' => 'Combat',
                'slug' => 'combat',
                'parent_slug' => 'sports',
                'display_order' => 6,
            ],
            
            // Cinema has no subcategories
            
            // Experiences subcategories (simplified to 4 only)
            [
                'name' => 'Nightlife',
                'slug' => 'nightlife',
                'parent_slug' => 'experiences',
                'display_order' => 1,
            ],
            [
                'name' => 'Wellness',
                'slug' => 'wellness',
                'parent_slug' => 'experiences',
                'display_order' => 2,
            ],
            [
                'name' => 'Adventure',
                'slug' => 'adventure',
                'parent_slug' => 'experiences',
                'display_order' => 3,
            ],
            [
                'name' => 'Art Exhibitions',
                'slug' => 'art-exhibitions',
                'parent_slug' => 'experiences',
                'display_order' => 4,
            ],
            
            // Stays subcategories
            [
                'name' => 'Airbnb',
                'slug' => 'airbnb',
                'parent_slug' => 'stays',
                'display_order' => 1,
            ],
            [
                'name' => 'Resorts',
                'slug' => 'resorts',
                'parent_slug' => 'stays',
                'display_order' => 2,
            ],
        ];

        // Create subcategories
        foreach ($subcategories as $subcategory) {
            $parent = EventCategory::where('slug', $subcategory['parent_slug'])->first();
            
            if ($parent) {
                EventCategory::firstOrCreate(
                    ['slug' => $subcategory['slug']],
                    [
                        'name' => $subcategory['name'],
                        'slug' => $subcategory['slug'],
                        'parent_id' => $parent->id,
                        'display_order' => $subcategory['display_order'],
                        'is_active' => true,
                        'is_featured' => false,
                        'color_hex' => $parent->color_hex, // Inherit parent color
                    ]
                );
            }
        }
    }
}