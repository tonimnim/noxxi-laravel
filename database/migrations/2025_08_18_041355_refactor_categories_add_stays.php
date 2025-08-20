<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create Stays parent category
        $staysId = Str::uuid()->toString();
        DB::table('event_categories')->insert([
            'id' => $staysId,
            'name' => 'Stays',
            'slug' => 'stays',
            'description' => 'Accommodation and lodging options',
            'parent_id' => null,
            'is_active' => true,
            'display_order' => 5,
            'icon_url' => null,
            'color_hex' => '#9C27B0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // 2. Get the Experiences category ID
        $experiencesId = DB::table('event_categories')
            ->where('slug', 'experiences')
            ->whereNull('parent_id')
            ->value('id');
        
        // 3. Move Airbnb and Resorts to Stays
        DB::table('event_categories')
            ->whereIn('slug', ['airbnb', 'resorts'])
            ->update([
                'parent_id' => $staysId,
                'updated_at' => now(),
            ]);
        
        // 4. Update display order for Airbnb and Resorts under Stays
        DB::table('event_categories')
            ->where('slug', 'airbnb')
            ->where('parent_id', $staysId)
            ->update(['display_order' => 1]);
            
        DB::table('event_categories')
            ->where('slug', 'resorts')
            ->where('parent_id', $staysId)
            ->update(['display_order' => 2]);
        
        // 5. Delete the extra subcategories from Experiences
        // Keep only: Nightlife & Parties, Wellness, Adventure, Art Exhibitions
        DB::table('event_categories')
            ->where('parent_id', $experiencesId)
            ->whereIn('slug', ['food-drink-tours', 'cultural-tours'])
            ->delete();
        
        // 6. Simplify Nightlife & Parties to just Nightlife
        DB::table('event_categories')
            ->where('slug', 'nightlife-parties')
            ->where('parent_id', $experiencesId)
            ->update([
                'name' => 'Nightlife',
                'slug' => 'nightlife',
                'updated_at' => now(),
            ]);
        
        // 7. Update display order for remaining Experiences subcategories
        DB::table('event_categories')
            ->where('slug', 'nightlife')
            ->where('parent_id', $experiencesId)
            ->update(['display_order' => 1]);
            
        DB::table('event_categories')
            ->where('slug', 'wellness')
            ->where('parent_id', $experiencesId)
            ->update(['display_order' => 2]);
            
        DB::table('event_categories')
            ->where('slug', 'adventure')
            ->where('parent_id', $experiencesId)
            ->update(['display_order' => 3]);
            
        DB::table('event_categories')
            ->where('slug', 'art-exhibitions')
            ->where('parent_id', $experiencesId)
            ->update(['display_order' => 4]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get IDs
        $staysId = DB::table('event_categories')
            ->where('slug', 'stays')
            ->whereNull('parent_id')
            ->value('id');
            
        $experiencesId = DB::table('event_categories')
            ->where('slug', 'experiences')
            ->whereNull('parent_id')
            ->value('id');
        
        // Move Airbnb and Resorts back to Experiences
        DB::table('event_categories')
            ->whereIn('slug', ['airbnb', 'resorts'])
            ->where('parent_id', $staysId)
            ->update([
                'parent_id' => $experiencesId,
                'updated_at' => now(),
            ]);
        
        // Update display order
        DB::table('event_categories')
            ->where('slug', 'airbnb')
            ->where('parent_id', $experiencesId)
            ->update(['display_order' => 7]);
            
        DB::table('event_categories')
            ->where('slug', 'resorts')
            ->where('parent_id', $experiencesId)
            ->update(['display_order' => 8]);
        
        // Restore Nightlife & Parties name
        DB::table('event_categories')
            ->where('slug', 'nightlife')
            ->where('parent_id', $experiencesId)
            ->update([
                'name' => 'Nightlife & Parties',
                'slug' => 'nightlife-parties',
                'updated_at' => now(),
            ]);
        
        // Re-create deleted categories
        DB::table('event_categories')->insert([
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Food & Drink Tours',
                'slug' => 'food-drink-tours',
                'description' => null,
                'parent_id' => $experiencesId,
                'is_active' => true,
                'display_order' => 5,
                'icon_url' => null,
                'color_hex' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Cultural Tours',
                'slug' => 'cultural-tours',
                'description' => null,
                'parent_id' => $experiencesId,
                'is_active' => true,
                'display_order' => 6,
                'icon_url' => null,
                'color_hex' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        
        // Delete Stays category
        DB::table('event_categories')
            ->where('slug', 'stays')
            ->whereNull('parent_id')
            ->delete();
    }
};
