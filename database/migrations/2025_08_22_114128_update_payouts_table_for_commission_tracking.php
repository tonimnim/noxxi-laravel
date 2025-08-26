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
        Schema::table('payouts', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (! Schema::hasColumn('payouts', 'requested_amount')) {
                $table->decimal('requested_amount', 10, 2)->nullable()->after('organizer_id')
                    ->comment('Amount requested by organizer');
            }
            if (! Schema::hasColumn('payouts', 'platform_commission')) {
                $table->decimal('platform_commission', 10, 2)->nullable()->after('requested_amount')
                    ->comment('Total platform commission deducted');
            }
            if (! Schema::hasColumn('payouts', 'payout_fee')) {
                $table->decimal('payout_fee', 10, 2)->default(0)->after('platform_commission')
                    ->comment('M-Pesa/Bank transfer fee');
            }
            if (! Schema::hasColumn('payouts', 'fee_absorbed')) {
                $table->boolean('fee_absorbed')->default(false)->after('payout_fee')
                    ->comment('Whether platform absorbed the payout fee');
            }
            if (! Schema::hasColumn('payouts', 'payout_method')) {
                $table->enum('payout_method', ['mpesa', 'bank'])->nullable()->after('net_amount')
                    ->comment('Method of payout');
            }
            if (! Schema::hasColumn('payouts', 'payout_details')) {
                $table->json('payout_details')->nullable()->after('payout_method')
                    ->comment('M-Pesa number or bank details used');
            }
            if (! Schema::hasColumn('payouts', 'reference_number')) {
                $table->string('reference_number', 50)->nullable()->unique()->after('organizer_id')
                    ->comment('Unique payout reference');
            }
            if (! Schema::hasColumn('payouts', 'booking_ids')) {
                $table->json('booking_ids')->nullable()
                    ->comment('Array of booking IDs included in this payout');
            }
            if (! Schema::hasColumn('payouts', 'metadata')) {
                $table->json('metadata')->nullable()
                    ->comment('Additional payout metadata');
            }
            if (! Schema::hasColumn('payouts', 'requested_at')) {
                $table->timestamp('requested_at')->nullable();
            }
            if (! Schema::hasColumn('payouts', 'completed_at')) {
                $table->timestamp('completed_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropColumn([
                'requested_amount', 'platform_commission', 'payout_fee',
                'fee_absorbed', 'payout_method', 'payout_details',
                'reference_number', 'booking_ids', 'metadata',
                'requested_at', 'completed_at',
            ]);
        });
    }
};
