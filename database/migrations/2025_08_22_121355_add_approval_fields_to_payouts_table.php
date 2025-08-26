<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            // Rejection tracking fields (approved_at and approved_by already exist)
            if (! Schema::hasColumn('payouts', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('approved_by');
            }
            if (! Schema::hasColumn('payouts', 'rejected_by')) {
                $table->uuid('rejected_by')->nullable()->after('rejected_at')
                    ->comment('User ID who rejected the payout');
            }

            // Completion tracking field (completed_at already exists)
            if (! Schema::hasColumn('payouts', 'transaction_reference')) {
                $table->string('transaction_reference')->nullable()->after('completed_at')
                    ->comment('Payment gateway transaction reference');
            }

            // Additional fields for better tracking
            if (! Schema::hasColumn('payouts', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_by')
                    ->comment('Reason for rejection');
            }

            if (! Schema::hasColumn('payouts', 'type')) {
                $table->enum('type', ['full', 'partial'])->default('full')->after('organizer_id')
                    ->comment('Type of payout');
            }

            if (! Schema::hasColumn('payouts', 'commission_amount')) {
                $table->decimal('commission_amount', 15, 2)->nullable()->after('commission_deducted')
                    ->comment('Commission amount in KES');
            }

            // Add foreign key constraint for rejected_by if column was created
            if (Schema::hasColumn('payouts', 'rejected_by')) {
                $table->foreign('rejected_by')->references('id')->on('users')->nullOnDelete();
            }

            // Update status enum to include new statuses (rejected)
            DB::statement('ALTER TABLE payouts DROP CONSTRAINT IF EXISTS payouts_status_check');
            DB::statement('ALTER TABLE payouts ALTER COLUMN status TYPE varchar(255) USING status::varchar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            // Drop foreign key if it exists
            if (Schema::hasColumn('payouts', 'rejected_by')) {
                $table->dropForeign(['rejected_by']);
            }

            // Drop columns that were added
            $columnsToDelete = [];
            if (Schema::hasColumn('payouts', 'rejected_at')) {
                $columnsToDelete[] = 'rejected_at';
            }
            if (Schema::hasColumn('payouts', 'rejected_by')) {
                $columnsToDelete[] = 'rejected_by';
            }
            if (Schema::hasColumn('payouts', 'transaction_reference')) {
                $columnsToDelete[] = 'transaction_reference';
            }
            if (Schema::hasColumn('payouts', 'rejection_reason')) {
                $columnsToDelete[] = 'rejection_reason';
            }
            if (Schema::hasColumn('payouts', 'type')) {
                $columnsToDelete[] = 'type';
            }
            if (Schema::hasColumn('payouts', 'commission_amount')) {
                $columnsToDelete[] = 'commission_amount';
            }

            if (! empty($columnsToDelete)) {
                $table->dropColumn($columnsToDelete);
            }
        });
    }
};
