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
            // Make platform_fee nullable to allow commission hierarchy
            $table->decimal('platform_fee', 5, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Revert platform_fee to non-nullable with default
            $table->decimal('platform_fee', 5, 2)->default(10)->nullable(false)->change();
        });
    }
};
