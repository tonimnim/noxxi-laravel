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
            // Premium status fields
            $table->enum('status', ['normal', 'premium'])->default('normal')->after('is_verified')
                ->comment('Organizer tier: normal or premium');
            $table->boolean('priority_support')->default(false)->after('absorb_payout_fees')
                ->comment('Premium perk - priority customer support');
            $table->boolean('auto_featured_listings')->default(false)->after('priority_support')
                ->comment('Premium perk - listings automatically featured');
            $table->decimal('monthly_volume', 15, 2)->nullable()->after('total_revenue')
                ->comment('Monthly transaction volume for analytics');
            $table->decimal('total_commission_paid', 15, 2)->default(0)->after('monthly_volume')
                ->comment('Total commission paid to platform');
            $table->timestamp('premium_since')->nullable()->after('verified_at')
                ->comment('Date when organizer became premium');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'priority_support',
                'auto_featured_listings',
                'monthly_volume',
                'total_commission_paid',
                'premium_since',
            ]);
        });
    }
};
