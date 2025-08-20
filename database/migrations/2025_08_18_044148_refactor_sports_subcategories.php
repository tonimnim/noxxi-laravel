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
        // Get Sports category ID
        $sportsId = DB::table('event_categories')
            ->where('slug', 'sports')
            ->whereNull('parent_id')
            ->value('id');
        
        // Delete unwanted sports subcategories
        DB::table('event_categories')
            ->where('parent_id', $sportsId)
            ->whereIn('slug', ['athletics-marathons', 'esports'])
            ->delete();
        
        // Add new sports subcategories
        DB::table('event_categories')->insert([
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Pool',
                'slug' => 'pool',
                'description' => 'Billiards and pool tournaments',
                'parent_id' => $sportsId,
                'is_active' => true,
                'display_order' => 5,
                'icon_url' => null,
                'color_hex' => '#10B981',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Combat',
                'slug' => 'combat',
                'description' => 'Boxing, MMA, Wrestling and martial arts',
                'parent_id' => $sportsId,
                'is_active' => true,
                'display_order' => 6,
                'icon_url' => null,
                'color_hex' => '#10B981',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        
        // Update display order for existing sports subcategories
        DB::table('event_categories')
            ->where('slug', 'football')
            ->where('parent_id', $sportsId)
            ->update(['display_order' => 1]);
            
        DB::table('event_categories')
            ->where('slug', 'basketball')
            ->where('parent_id', $sportsId)
            ->update(['display_order' => 2]);
            
        DB::table('event_categories')
            ->where('slug', 'rugby')
            ->where('parent_id', $sportsId)
            ->update(['display_order' => 3]);
            
        DB::table('event_categories')
            ->where('slug', 'motorsports')
            ->where('parent_id', $sportsId)
            ->update(['display_order' => 4]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get Sports category ID
        $sportsId = DB::table('event_categories')
            ->where('slug', 'sports')
            ->whereNull('parent_id')
            ->value('id');
        
        // Delete the new categories
        DB::table('event_categories')
            ->where('parent_id', $sportsId)
            ->whereIn('slug', ['pool', 'combat'])
            ->delete();
        
        // Re-add the deleted categories
        DB::table('event_categories')->insert([
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Athletics & Marathons',
                'slug' => 'athletics-marathons',
                'description' => null,
                'parent_id' => $sportsId,
                'is_active' => true,
                'display_order' => 4,
                'icon_url' => null,
                'color_hex' => '#10B981',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'eSports',
                'slug' => 'esports',
                'description' => null,
                'parent_id' => $sportsId,
                'is_active' => true,
                'display_order' => 6,
                'icon_url' => null,
                'color_hex' => '#10B981',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        
        // Restore original display order
        DB::table('event_categories')
            ->where('slug', 'motorsports')
            ->where('parent_id', $sportsId)
            ->update(['display_order' => 5]);
    }
};
