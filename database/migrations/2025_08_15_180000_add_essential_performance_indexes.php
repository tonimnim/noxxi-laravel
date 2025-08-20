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
        // Check and add indexes only if they don't exist
        
        // Transactions table - most critical for dashboard
        if (!$this->indexExists('transactions', 'transactions_status_created_at_index')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index(['status', 'created_at']);
            });
        }
        
        // Bookings table
        if (!$this->indexExists('bookings', 'bookings_status_created_at_index')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->index(['status', 'created_at']);
            });
        }
        
        // Events table
        if (!$this->indexExists('events', 'events_status_created_at_index')) {
            Schema::table('events', function (Blueprint $table) {
                $table->index(['status', 'created_at']);
            });
        }
        
        // Organizers table
        if (!$this->indexExists('organizers', 'organizers_is_verified_index')) {
            Schema::table('organizers', function (Blueprint $table) {
                $table->index('is_verified');
            });
        }
        
        // Users table
        if (!$this->indexExists('users', 'users_last_active_at_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('last_active_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
        });
        
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
        });
        
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
        });
        
        Schema::table('organizers', function (Blueprint $table) {
            $table->dropIndex(['is_verified']);
        });
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['last_active_at']);
        });
    }
    
    /**
     * Check if an index exists
     */
    private function indexExists($table, $indexName): bool
    {
        $indexes = DB::select("SELECT indexname FROM pg_indexes WHERE tablename = ?", [$table]);
        foreach ($indexes as $index) {
            if ($index->indexname === $indexName) {
                return true;
            }
        }
        return false;
    }
};