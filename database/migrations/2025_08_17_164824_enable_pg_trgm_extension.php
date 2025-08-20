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
        // Enable pg_trgm extension for similarity searches
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        
        // Add trigram indexes for better search performance
        DB::statement('CREATE INDEX IF NOT EXISTS events_title_trgm_idx ON events USING gin(title gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS events_description_trgm_idx ON events USING gin(description gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS events_city_trgm_idx ON events USING gin(city gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS organizers_business_name_trgm_idx ON organizers USING gin(business_name gin_trgm_ops)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes
        DB::statement('DROP INDEX IF EXISTS events_title_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS events_description_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS events_city_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS organizers_business_name_trgm_idx');
        
        // Note: We don't drop the extension as other features might depend on it
    }
};