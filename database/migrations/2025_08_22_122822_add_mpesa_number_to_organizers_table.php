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
            if (! Schema::hasColumn('organizers', 'mpesa_number')) {
                $table->string('mpesa_number')->nullable()->after('bank_account_name')
                    ->comment('M-Pesa number for payouts');
            }

            if (! Schema::hasColumn('organizers', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false)->after('is_active')
                    ->comment('2FA enabled for payout requests');
            }

            if (! Schema::hasColumn('organizers', 'two_factor_secret')) {
                $table->text('two_factor_secret')->nullable()->after('two_factor_enabled')
                    ->comment('Encrypted 2FA secret');
            }

            if (! Schema::hasColumn('organizers', 'last_payout_at')) {
                $table->timestamp('last_payout_at')->nullable()->after('total_commission_paid')
                    ->comment('Last successful payout date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $columnsToDelete = [];

            if (Schema::hasColumn('organizers', 'mpesa_number')) {
                $columnsToDelete[] = 'mpesa_number';
            }
            if (Schema::hasColumn('organizers', 'two_factor_enabled')) {
                $columnsToDelete[] = 'two_factor_enabled';
            }
            if (Schema::hasColumn('organizers', 'two_factor_secret')) {
                $columnsToDelete[] = 'two_factor_secret';
            }
            if (Schema::hasColumn('organizers', 'last_payout_at')) {
                $columnsToDelete[] = 'last_payout_at';
            }

            if (! empty($columnsToDelete)) {
                $table->dropColumn($columnsToDelete);
            }
        });
    }
};
