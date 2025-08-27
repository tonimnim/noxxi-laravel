<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations to populate reference data (categories and cities)
     */
    public function up(): void
    {
        $this->populateEventCategories();
        $this->populateAfricanCities();
    }

    /**
     * Populate event categories
     */
    private function populateEventCategories(): void
    {
        // Parent Categories
        $parentCategories = [
            [
                'id' => Str::uuid(),
                'name' => 'Events',
                'slug' => 'events',
                'color_hex' => '#3B82F6',
                'display_order' => 1,
                'is_active' => true,
                'is_featured' => true,
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Sports',
                'slug' => 'sports',
                'color_hex' => '#10B981',
                'display_order' => 2,
                'is_active' => true,
                'is_featured' => true,
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Cinema',
                'slug' => 'cinema',
                'color_hex' => '#EF4444',
                'display_order' => 3,
                'is_active' => true,
                'is_featured' => true,
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Experiences',
                'slug' => 'experiences',
                'color_hex' => '#F59E0B',
                'display_order' => 4,
                'is_active' => true,
                'is_featured' => true,
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Insert parent categories first using DB::table to avoid model events
        foreach ($parentCategories as $category) {
            DB::table('event_categories')->insertOrIgnore($category);
        }

        // Get parent IDs for subcategories
        $parentIds = DB::table('event_categories')
            ->whereNull('parent_id')
            ->pluck('id', 'slug');

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
            // Sports subcategories
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
            // Experiences subcategories
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
        ];

        // Insert subcategories
        foreach ($subcategories as $subcategory) {
            if (isset($parentIds[$subcategory['parent_slug']])) {
                $parentId = $parentIds[$subcategory['parent_slug']];
                
                // Get parent color for inheritance
                $parentColor = DB::table('event_categories')
                    ->where('id', $parentId)
                    ->value('color_hex');

                DB::table('event_categories')->insertOrIgnore([
                    'id' => Str::uuid(),
                    'name' => $subcategory['name'],
                    'slug' => $subcategory['slug'],
                    'parent_id' => $parentId,
                    'display_order' => $subcategory['display_order'],
                    'is_active' => true,
                    'is_featured' => false,
                    'color_hex' => $parentColor,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Populate key African cities for the platform
     */
    private function populateAfricanCities(): void
    {
        $cities = [
            // Kenya - Primary market
            ['name' => 'Nairobi', 'country' => 'Kenya', 'country_code' => 'KE', 'region' => 'East Africa', 'latitude' => -1.2921, 'longitude' => 36.8219, 'population' => 4397073, 'is_capital' => true, 'timezone' => 'Africa/Nairobi'],
            ['name' => 'Mombasa', 'country' => 'Kenya', 'country_code' => 'KE', 'region' => 'East Africa', 'latitude' => -4.0435, 'longitude' => 39.6682, 'population' => 1208333, 'timezone' => 'Africa/Nairobi'],
            ['name' => 'Kisumu', 'country' => 'Kenya', 'country_code' => 'KE', 'region' => 'East Africa', 'latitude' => -0.1022, 'longitude' => 34.7617, 'population' => 610082, 'timezone' => 'Africa/Nairobi'],
            ['name' => 'Nakuru', 'country' => 'Kenya', 'country_code' => 'KE', 'region' => 'East Africa', 'latitude' => -0.3031, 'longitude' => 36.0800, 'population' => 570674, 'timezone' => 'Africa/Nairobi'],
            ['name' => 'Eldoret', 'country' => 'Kenya', 'country_code' => 'KE', 'region' => 'East Africa', 'latitude' => 0.5143, 'longitude' => 35.2698, 'population' => 475716, 'timezone' => 'Africa/Nairobi'],
            
            // Nigeria - Major market
            ['name' => 'Lagos', 'country' => 'Nigeria', 'country_code' => 'NG', 'region' => 'West Africa', 'latitude' => 6.5244, 'longitude' => 3.3792, 'population' => 15400000, 'timezone' => 'Africa/Lagos'],
            ['name' => 'Abuja', 'country' => 'Nigeria', 'country_code' => 'NG', 'region' => 'West Africa', 'latitude' => 9.0765, 'longitude' => 7.3986, 'population' => 3095000, 'is_capital' => true, 'timezone' => 'Africa/Lagos'],
            ['name' => 'Port Harcourt', 'country' => 'Nigeria', 'country_code' => 'NG', 'region' => 'West Africa', 'latitude' => 4.8157, 'longitude' => 7.0498, 'population' => 3171000, 'timezone' => 'Africa/Lagos'],
            ['name' => 'Ibadan', 'country' => 'Nigeria', 'country_code' => 'NG', 'region' => 'West Africa', 'latitude' => 7.3775, 'longitude' => 3.9470, 'population' => 3552000, 'timezone' => 'Africa/Lagos'],
            
            // South Africa - Major market
            ['name' => 'Johannesburg', 'country' => 'South Africa', 'country_code' => 'ZA', 'region' => 'Southern Africa', 'latitude' => -26.2041, 'longitude' => 28.0473, 'population' => 5635127, 'timezone' => 'Africa/Johannesburg'],
            ['name' => 'Cape Town', 'country' => 'South Africa', 'country_code' => 'ZA', 'region' => 'Southern Africa', 'latitude' => -33.9249, 'longitude' => 18.4241, 'population' => 4710000, 'timezone' => 'Africa/Johannesburg'],
            ['name' => 'Durban', 'country' => 'South Africa', 'country_code' => 'ZA', 'region' => 'Southern Africa', 'latitude' => -29.8587, 'longitude' => 31.0218, 'population' => 3120000, 'timezone' => 'Africa/Johannesburg'],
            ['name' => 'Pretoria', 'country' => 'South Africa', 'country_code' => 'ZA', 'region' => 'Southern Africa', 'latitude' => -25.7479, 'longitude' => 28.2293, 'population' => 2472000, 'is_capital' => true, 'timezone' => 'Africa/Johannesburg'],
            
            // Ghana
            ['name' => 'Accra', 'country' => 'Ghana', 'country_code' => 'GH', 'region' => 'West Africa', 'latitude' => 5.6037, 'longitude' => -0.1870, 'population' => 2607000, 'is_capital' => true, 'timezone' => 'Africa/Accra'],
            ['name' => 'Kumasi', 'country' => 'Ghana', 'country_code' => 'GH', 'region' => 'West Africa', 'latitude' => 6.6885, 'longitude' => -1.6244, 'population' => 3630000, 'timezone' => 'Africa/Accra'],
            
            // Egypt
            ['name' => 'Cairo', 'country' => 'Egypt', 'country_code' => 'EG', 'region' => 'North Africa', 'latitude' => 30.0444, 'longitude' => 31.2357, 'population' => 21750000, 'is_capital' => true, 'timezone' => 'Africa/Cairo'],
            ['name' => 'Alexandria', 'country' => 'Egypt', 'country_code' => 'EG', 'region' => 'North Africa', 'latitude' => 31.2001, 'longitude' => 29.9187, 'population' => 5483000, 'timezone' => 'Africa/Cairo'],
            
            // Tanzania
            ['name' => 'Dar es Salaam', 'country' => 'Tanzania', 'country_code' => 'TZ', 'region' => 'East Africa', 'latitude' => -6.7924, 'longitude' => 39.2083, 'population' => 6368000, 'timezone' => 'Africa/Dar_es_Salaam'],
            ['name' => 'Dodoma', 'country' => 'Tanzania', 'country_code' => 'TZ', 'region' => 'East Africa', 'latitude' => -6.1730, 'longitude' => 35.7516, 'population' => 410956, 'is_capital' => true, 'timezone' => 'Africa/Dar_es_Salaam'],
            
            // Uganda
            ['name' => 'Kampala', 'country' => 'Uganda', 'country_code' => 'UG', 'region' => 'East Africa', 'latitude' => 0.3476, 'longitude' => 32.5825, 'population' => 1650800, 'is_capital' => true, 'timezone' => 'Africa/Kampala'],
            
            // Rwanda
            ['name' => 'Kigali', 'country' => 'Rwanda', 'country_code' => 'RW', 'region' => 'East Africa', 'latitude' => -1.9441, 'longitude' => 30.0619, 'population' => 1257000, 'is_capital' => true, 'timezone' => 'Africa/Kigali'],
            
            // Ethiopia
            ['name' => 'Addis Ababa', 'country' => 'Ethiopia', 'country_code' => 'ET', 'region' => 'East Africa', 'latitude' => 9.0250, 'longitude' => 38.7469, 'population' => 5000000, 'is_capital' => true, 'timezone' => 'Africa/Addis_Ababa'],
        ];

        foreach ($cities as $city) {
            DB::table('cities')->insertOrIgnore([
                'id' => Str::uuid(),
                'name' => $city['name'],
                'country' => $city['country'],
                'country_code' => $city['country_code'],
                'region' => $city['region'],
                'state_province' => $city['state_province'] ?? null,
                'latitude' => $city['latitude'] ?? null,
                'longitude' => $city['longitude'] ?? null,
                'population' => $city['population'] ?? null,
                'is_capital' => $city['is_capital'] ?? false,
                'is_major' => true,
                'timezone' => $city['timezone'] ?? null,
                'slug' => Str::slug($city['name'] . '-' . $city['country_code']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete cities
        DB::table('cities')->whereIn('country_code', ['KE', 'NG', 'ZA', 'GH', 'EG', 'TZ', 'UG', 'RW', 'ET'])->delete();
        
        // Delete subcategories first (those with parent_id)
        DB::table('event_categories')->whereNotNull('parent_id')->delete();
        
        // Then delete parent categories by slug
        DB::table('event_categories')->whereIn('slug', [
            'events',
            'sports', 
            'cinema',
            'experiences'
        ])->delete();
    }
};