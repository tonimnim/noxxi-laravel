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
        // Add STAYS main category
        $staysId = (string) Str::uuid();
        
        DB::table('event_categories')->insert([
            'id' => $staysId,
            'name' => 'Stays',
            'slug' => 'stays',
            'description' => 'Find and book accommodations across Africa',
            'parent_id' => null,
            'color_hex' => '#8B5CF6', // Purple color for Stays
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

        // Add STAYS subcategories
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
                'color_hex' => '#8B5CF6', // Same color as parent
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove STAYS subcategories first
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
    }
};
