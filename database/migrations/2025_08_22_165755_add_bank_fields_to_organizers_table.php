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
            if (! Schema::hasColumn('organizers', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('mpesa_number');
            }
            if (! Schema::hasColumn('organizers', 'bank_account_number')) {
                $table->string('bank_account_number')->nullable()->after('bank_name');
            }
            if (! Schema::hasColumn('organizers', 'bank_account_name')) {
                $table->string('bank_account_name')->nullable()->after('bank_account_number');
            }
            if (! Schema::hasColumn('organizers', 'payout_frequency')) {
                $table->enum('payout_frequency', ['weekly', 'bi_weekly', 'monthly', 'manual'])
                    ->default('weekly')
                    ->after('bank_account_name');
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

            if (Schema::hasColumn('organizers', 'bank_name')) {
                $columnsToDelete[] = 'bank_name';
            }
            if (Schema::hasColumn('organizers', 'bank_account_number')) {
                $columnsToDelete[] = 'bank_account_number';
            }
            if (Schema::hasColumn('organizers', 'bank_account_name')) {
                $columnsToDelete[] = 'bank_account_name';
            }
            if (Schema::hasColumn('organizers', 'payout_frequency')) {
                $columnsToDelete[] = 'payout_frequency';
            }

            if (! empty($columnsToDelete)) {
                $table->dropColumn($columnsToDelete);
            }
        });
    }
};
