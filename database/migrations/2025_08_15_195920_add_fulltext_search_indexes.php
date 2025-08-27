<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create GIN indexes for full-text search on PostgreSQL
        // These indexes significantly improve search performance for ILIKE queries
        
        // Users table - full_name and email search
        DB::statement('CREATE INDEX IF NOT EXISTS users_fulltext_idx ON users USING gin(to_tsvector(\'english\', coalesce(full_name, \'\') || \' \' || coalesce(email, \'\')))');
        
        // Organizers table - business_name search
        DB::statement('CREATE INDEX IF NOT EXISTS organizers_fulltext_idx ON organizers USING gin(to_tsvector(\'english\', coalesce(business_name, \'\') || \' \' || coalesce(business_type, \'\')))');
        
        // Events table - title, description, venue search
        DB::statement('CREATE INDEX IF NOT EXISTS events_fulltext_idx ON events USING gin(to_tsvector(\'english\', coalesce(title, \'\') || \' \' || coalesce(description, \'\') || \' \' || coalesce(venue_name, \'\') || \' \' || coalesce(city, \'\')))');
        
        // Bookings table - booking_reference search
        DB::statement('CREATE INDEX IF NOT EXISTS bookings_reference_idx ON bookings USING btree(booking_reference)');
        
        // Add trigram indexes for better fuzzy search (requires pg_trgm extension)
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        
        // Trigram indexes for partial matching
        DB::statement('CREATE INDEX IF NOT EXISTS users_full_name_trgm_idx ON users USING gin(full_name gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS users_email_trgm_idx ON users USING gin(email gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS organizers_business_name_trgm_idx ON organizers USING gin(business_name gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS events_title_trgm_idx ON events USING gin(title gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS events_venue_name_trgm_idx ON events USING gin(venue_name gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS events_description_trgm_idx ON events USING gin(description gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS events_city_trgm_idx ON events USING gin(city gin_trgm_ops)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop full-text indexes
        DB::statement('DROP INDEX IF EXISTS users_fulltext_idx');
        DB::statement('DROP INDEX IF EXISTS organizers_fulltext_idx');
        DB::statement('DROP INDEX IF EXISTS events_fulltext_idx');
        DB::statement('DROP INDEX IF EXISTS bookings_reference_idx');
        
        // Drop trigram indexes
        DB::statement('DROP INDEX IF EXISTS users_full_name_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS users_email_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS organizers_business_name_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS events_title_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS events_venue_name_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS events_description_trgm_idx');
        DB::statement('DROP INDEX IF EXISTS events_city_trgm_idx');
    }
};