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
        // First, add the new columns only if they don't exist
        Schema::table('payouts', function (Blueprint $table) {
            if (!Schema::hasColumn('payouts', 'hold_reason')) {
                $table->text('hold_reason')->nullable()->after('failure_reason');
            }
            
            if (!Schema::hasColumn('payouts', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('failure_reason');
            }
            
            if (!Schema::hasColumn('payouts', 'held_by')) {
                $table->uuid('held_by')->nullable()->after('processed_by');
                $table->foreign('held_by')->references('id')->on('users');
            }
            
            if (!Schema::hasColumn('payouts', 'rejected_by')) {
                $table->uuid('rejected_by')->nullable()->after('processed_by');
                $table->foreign('rejected_by')->references('id')->on('users');
            }
            
            if (!Schema::hasColumn('payouts', 'held_at')) {
                $table->timestamp('held_at')->nullable()->after('paid_at');
            }
            
            if (!Schema::hasColumn('payouts', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('paid_at');
            }
        });
        
        // Update the status enum to include 'on_hold' and 'rejected' (for PostgreSQL)
        DB::statement("ALTER TABLE payouts DROP CONSTRAINT IF EXISTS payouts_status_check");
        DB::statement("ALTER TABLE payouts ADD CONSTRAINT payouts_status_check CHECK (status IN ('pending', 'on_hold', 'approved', 'processing', 'paid', 'completed', 'failed', 'rejected', 'cancelled'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the enum values (for PostgreSQL)
        DB::statement("ALTER TABLE payouts DROP CONSTRAINT IF EXISTS payouts_status_check");
        DB::statement("ALTER TABLE payouts ADD CONSTRAINT payouts_status_check CHECK (status IN ('pending', 'approved', 'processing', 'paid', 'failed', 'cancelled'))");
        
        // Drop the columns if they exist
        Schema::table('payouts', function (Blueprint $table) {
            if (Schema::hasColumn('payouts', 'held_by')) {
                $table->dropForeign(['held_by']);
                $table->dropColumn('held_by');
            }
            if (Schema::hasColumn('payouts', 'rejected_by')) {
                $table->dropForeign(['rejected_by']);
                $table->dropColumn('rejected_by');
            }
            if (Schema::hasColumn('payouts', 'hold_reason')) {
                $table->dropColumn('hold_reason');
            }
            if (Schema::hasColumn('payouts', 'held_at')) {
                $table->dropColumn('held_at');
            }
            if (Schema::hasColumn('payouts', 'rejected_at')) {
                $table->dropColumn('rejected_at');
            }
        });
    }
};