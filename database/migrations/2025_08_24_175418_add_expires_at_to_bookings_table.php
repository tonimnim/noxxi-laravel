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
        // Check if expires_at column already exists
        if (! Schema::hasColumn('bookings', 'expires_at')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable()->after('created_at')
                    ->comment('Time when pending booking expires if not paid');
                $table->index('expires_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
            $table->dropColumn('expires_at');
        });
    }
};
