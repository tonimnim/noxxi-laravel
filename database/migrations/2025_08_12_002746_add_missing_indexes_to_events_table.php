<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Add is_active field for better filtering
            $table->boolean('is_active')->default(true)->after('status');
            
            // Add composite indexes for common queries
            $table->index(['organizer_id', 'status', 'event_date'], 'idx_organizer_events');
            $table->index(['status', 'event_date'], 'idx_upcoming_events');
            $table->index(['city', 'status', 'event_date'], 'idx_city_events');
            $table->index('is_active');
            
            // Index for price range queries
            $table->index(['min_price', 'max_price'], 'idx_price_range');
        });
        
        // Add missing index on tickets table
        Schema::table('tickets', function (Blueprint $table) {
            $table->index('ticket_hash', 'idx_ticket_hash');
            $table->index(['event_id', 'status'], 'idx_event_tickets');
        });
        
        // Add composite index for bookings
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'created_at'], 'idx_user_bookings');
            $table->index(['event_id', 'status'], 'idx_event_bookings');
        });
        
        // Add index for transactions
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['booking_id', 'type'], 'idx_booking_transactions');
            $table->index('processed_at', 'idx_processed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('is_active');
            $table->dropIndex('idx_organizer_events');
            $table->dropIndex('idx_upcoming_events');
            $table->dropIndex('idx_city_events');
            $table->dropIndex('idx_price_range');
        });
        
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('idx_ticket_hash');
            $table->dropIndex('idx_event_tickets');
        });
        
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('idx_user_bookings');
            $table->dropIndex('idx_event_bookings');
        });
        
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_booking_transactions');
            $table->dropIndex('idx_processed_at');
        });
    }
};