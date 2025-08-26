<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Delete Airbnb and Resorts subcategories first
        DB::table('event_categories')
            ->whereIn('slug', ['airbnb', 'resorts'])
            ->delete();

        // Delete Stays parent category
        DB::table('event_categories')
            ->where('slug', 'stays')
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-create Stays category
        $staysId = 'fd56f6d2-d6d6-4d84-b13d-6ca1bb69d306';

        DB::table('event_categories')->insert([
            'id' => $staysId,
            'name' => 'Stays',
            'slug' => 'stays',
            'parent_id' => null,
            'description' => 'Accommodation and lodging options',
            'color_code' => '#8B4513',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Re-create subcategories
        DB::table('event_categories')->insert([
            [
                'id' => '0fb6b663-bb25-4f79-86ee-f38c1f4b8ff7',
                'name' => 'Airbnb',
                'slug' => 'airbnb',
                'parent_id' => $staysId,
                'description' => 'Airbnb stays and experiences',
                'color_code' => '#FF5A5F',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '4aea06e0-4198-4322-99c1-49183d9bfcc9',
                'name' => 'Resorts',
                'slug' => 'resorts',
                'parent_id' => $staysId,
                'description' => 'Resort accommodations',
                'color_code' => '#4682B4',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
};
