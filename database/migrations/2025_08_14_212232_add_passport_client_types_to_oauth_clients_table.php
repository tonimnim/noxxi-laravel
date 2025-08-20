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
        Schema::table('oauth_clients', function (Blueprint $table) {
            // Add missing columns for Passport client types
            if (! Schema::hasColumn('oauth_clients', 'personal_access_client')) {
                $table->boolean('personal_access_client')->default(false);
            }
            if (! Schema::hasColumn('oauth_clients', 'password_client')) {
                $table->boolean('password_client')->default(false);
            }
            if (! Schema::hasColumn('oauth_clients', 'redirect')) {
                $table->string('redirect')->nullable();
            }
            if (! Schema::hasColumn('oauth_clients', 'user_id')) {
                $table->uuid('user_id')->nullable()->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->dropColumn(['personal_access_client', 'password_client', 'redirect', 'user_id']);
        });
    }
};
