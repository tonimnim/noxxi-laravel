<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, remove all STAYS subcategories
        DB::table('event_categories')
            ->whereIn('slug', [
                'hotels', 
                'guesthouses', 
                'lodges-camps', 
                'short-term-rentals', 
                'hostels', 
                'resorts'
            ])
            ->delete();

        // Remove STAYS main category
        DB::table('event_categories')
            ->where('slug', 'stays')
            ->delete();

        // Get the Experiences category ID
        $experiencesCategory = DB::table('event_categories')
            ->where('slug', 'experiences')
            ->whereNull('parent_id')
            ->first();

        if ($experiencesCategory) {
            // Add Airbnb under Experiences
            DB::table('event_categories')->insert([
                'id' => (string) Str::uuid(),
                'name' => 'Airbnb',
                'slug' => 'airbnb',
                'description' => 'Short-term rentals and vacation homes',
                'parent_id' => $experiencesCategory->id,
                'color_hex' => '#F59E0B', // Same as parent (Orange)
                'icon_url' => null,
                'display_order' => 7, // After existing subcategories
                'is_active' => true,
                'metadata' => json_encode([
                    'booking_type' => 'accommodation',
                    'parent_category' => 'experiences'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Add Resorts under Experiences  
            DB::table('event_categories')->insert([
                'id' => (string) Str::uuid(),
                'name' => 'Resorts',
                'slug' => 'resorts',
                'description' => 'Luxury resorts and vacation destinations',
                'parent_id' => $experiencesCategory->id,
                'color_hex' => '#F59E0B', // Same as parent (Orange)
                'icon_url' => null,
                'display_order' => 8,
                'is_active' => true,
                'metadata' => json_encode([
                    'booking_type' => 'accommodation',
                    'parent_category' => 'experiences'
                ]),
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
        // Remove Airbnb and Resorts from Experiences
        DB::table('event_categories')
            ->whereIn('slug', ['airbnb', 'resorts'])
            ->delete();

        // Re-add STAYS category (reverse of removal)
        $staysId = (string) Str::uuid();
        
        DB::table('event_categories')->insert([
            'id' => $staysId,
            'name' => 'Stays',
            'slug' => 'stays',
            'description' => 'Find and book accommodations across Africa',
            'parent_id' => null,
            'color_hex' => '#8B5CF6',
            'icon_url' => null,
            'display_order' => 5,
            'is_active' => true,
            'metadata' => json_encode([
                'booking_type' => 'accommodation',
                'requires_checkin_checkout' => true,
                'supports_room_types' => true
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Re-add STAYS subcategories
        $subcategories = [
            ['name' => 'Hotels', 'slug' => 'hotels', 'order' => 1],
            ['name' => 'Guesthouses', 'slug' => 'guesthouses', 'order' => 2],
            ['name' => 'Lodges & Camps', 'slug' => 'lodges-camps', 'order' => 3],
            ['name' => 'Short-term Rentals', 'slug' => 'short-term-rentals', 'order' => 4],
            ['name' => 'Hostels', 'slug' => 'hostels', 'order' => 5],
            ['name' => 'Resorts', 'slug' => 'resorts', 'order' => 6],
        ];

        foreach ($subcategories as $sub) {
            DB::table('event_categories')->insert([
                'id' => (string) Str::uuid(),
                'name' => $sub['name'],
                'slug' => $sub['slug'],
                'description' => null,
                'parent_id' => $staysId,
                'color_hex' => '#8B5CF6',
                'icon_url' => null,
                'display_order' => $sub['order'],
                'is_active' => true,
                'metadata' => json_encode([
                    'booking_type' => 'accommodation',
                    'parent_category' => 'stays'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};