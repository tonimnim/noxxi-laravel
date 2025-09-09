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
        Schema::table('tickets', function (Blueprint $table) {
            // Add version column for optimistic locking
            if (!Schema::hasColumn('tickets', 'version')) {
                $table->integer('version')->default(1)->after('entry_device');
            }
            
            // Add indexes for performance (check if they don't exist)
            $indexes = DB::select("SELECT indexname FROM pg_indexes WHERE tablename = 'tickets'");
            $existingIndexes = array_column($indexes, 'indexname');
            
            if (!in_array('tickets_ticket_code_index', $existingIndexes)) {
                $table->index('ticket_code'); // Fast lookup by code
            }
            if (!in_array('tickets_event_id_status_index', $existingIndexes)) {
                $table->index(['event_id', 'status']); // Fast stats queries
            }
            if (!in_array('tickets_status_used_at_index', $existingIndexes)) {
                $table->index(['status', 'used_at']); // Fast check-in queries
            }
            if (!in_array('tickets_event_id_entry_gate_index', $existingIndexes)) {
                $table->index(['event_id', 'entry_gate']); // Gate statistics
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['ticket_code']);
            $table->dropIndex(['event_id', 'status']);
            $table->dropIndex(['status', 'used_at']);
            $table->dropIndex(['event_id', 'entry_gate']);
            $table->dropColumn('version');
        });
    }
};
