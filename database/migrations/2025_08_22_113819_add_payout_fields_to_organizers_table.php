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
        Schema::table('organizers', function (Blueprint $table) {
            // Payout preferences and premium perks
            $table->boolean('absorb_payout_fees')->default(false)->after('commission_rate')
                ->comment('Premium perk - platform absorbs payout fees');
            $table->enum('payout_method', ['mpesa', 'bank'])->default('mpesa')->after('absorb_payout_fees')
                ->comment('Preferred payout method');
            $table->string('mpesa_number', 20)->nullable()->after('payout_method')
                ->comment('M-Pesa number for payouts');
            $table->json('bank_details')->nullable()->after('mpesa_number')
                ->comment('Bank account details for payouts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->dropColumn(['absorb_payout_fees', 'payout_method', 'mpesa_number', 'bank_details']);
        });
    }
};
