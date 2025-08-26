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
        Schema::create('cities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('country');
            $table->string('country_code', 2); // ISO 3166-1 alpha-2
            $table->string('region'); // East Africa, West Africa, etc.
            $table->string('state_province')->nullable(); // For federal states
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('population')->nullable(); // To sort by importance
            $table->boolean('is_capital')->default(false);
            $table->boolean('is_major')->default(true); // Major cities for filtering
            $table->string('timezone')->nullable();
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes for performance
            $table->index('name');
            $table->index('country');
            $table->index('country_code');
            $table->index('region');
            $table->index('slug');
            $table->index(['is_active', 'is_major']);
            $table->index(['latitude', 'longitude']);
        });

        // Add city_id to events table
        Schema::table('events', function (Blueprint $table) {
            $table->uuid('city_id')->nullable()->after('city');
            $table->foreign('city_id')->references('id')->on('cities')->nullOnDelete();
            $table->index('city_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['city_id']);
            $table->dropColumn('city_id');
        });

        Schema::dropIfExists('cities');
    }
};
