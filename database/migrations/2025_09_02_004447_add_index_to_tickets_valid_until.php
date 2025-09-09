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
        Schema::table('tickets', function (Blueprint $table) {
            // Add index for performance on ticket categorization queries
            $table->index('valid_until', 'idx_tickets_valid_until');
            
            // Add composite index for status and validity queries
            $table->index(['status', 'valid_until'], 'idx_tickets_status_valid_until');
            
            // Add composite index for assigned tickets queries
            $table->index(['assigned_to', 'status'], 'idx_tickets_assigned_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('idx_tickets_valid_until');
            $table->dropIndex('idx_tickets_status_valid_until');
            $table->dropIndex('idx_tickets_assigned_status');
        });
    }
};
