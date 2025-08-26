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
            // Make commission_rate nullable to allow commission hierarchy
            $table->decimal('commission_rate', 5, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            // Revert commission_rate to non-nullable with default
            $table->decimal('commission_rate', 5, 2)->default(10)->nullable(false)->change();
        });
    }
};
