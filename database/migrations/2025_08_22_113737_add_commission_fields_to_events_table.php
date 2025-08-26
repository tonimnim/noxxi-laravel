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
        Schema::table('events', function (Blueprint $table) {
            // Commission settings per event
            $table->decimal('commission_rate', 5, 2)->nullable()->after('status')
                ->comment('Platform commission percentage for this event (overrides organizer default)');
            $table->enum('commission_type', ['percentage', 'fixed'])->default('percentage')->after('commission_rate')
                ->comment('Whether commission is percentage-based or fixed amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['commission_rate', 'commission_type']);
        });
    }
};
