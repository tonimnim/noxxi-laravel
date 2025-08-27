<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Enable UUID extension for PostgreSQL
        DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
        
        Schema::create('users', function (Blueprint $table) {
            // Use UUID as primary key
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            
            // Core identity fields
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('phone_number', 20)->unique();
            $table->string('password');
            
            // Role-based access control
            $table->enum('role', ['admin', 'organizer', 'user'])->default('user');
            
            // Profile fields
            $table->string('avatar_url')->nullable();
            $table->string('country_code', 3)->default('KE');
            $table->string('city', 100)->nullable();
            
            // Preferences and settings (JSONB for PostgreSQL)
            $table->jsonb('notification_preferences')->default('{"email": true, "sms": true, "push": true}');
            $table->jsonb('metadata')->default('{}');
            
            // Status flags
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            
            // Activity tracking
            $table->timestamp('last_active_at')->nullable();
            
            // Security fields for login tracking
            $table->integer('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->text('login_history')->nullable(); // JSON array of recent login attempts
            
            // Laravel auth requirements
            $table->rememberToken();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes for performance
            $table->index('email');
            $table->index('phone_number');
            $table->index('role');
            $table->index('is_active');
            $table->index('last_active_at');
            $table->index(['full_name', 'email']); // Composite index for search
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
