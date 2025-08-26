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
        Schema::table('transactions', function (Blueprint $table) {
            // Better fee tracking
            $table->decimal('paystack_fee', 10, 2)->nullable()->after('gateway_fee')
                ->comment('Actual Paystack processing fee (1.5% for M-Pesa)');
            $table->decimal('platform_commission', 10, 2)->nullable()->after('paystack_fee')
                ->comment('Platform commission based on event/organizer settings');
            $table->decimal('payout_fee', 10, 2)->nullable()->after('platform_commission')
                ->comment('M-Pesa or Bank transfer fee for payout');

            // Rename gateway_fee to be more specific if needed
            $table->renameColumn('gateway_fee', 'payment_processing_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->renameColumn('payment_processing_fee', 'gateway_fee');
            $table->dropColumn(['paystack_fee', 'platform_commission', 'payout_fee']);
        });
    }
};
