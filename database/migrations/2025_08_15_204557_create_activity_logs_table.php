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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Activity details
            $table->string('type', 50)->index(); // 'payment', 'organizer', 'event', 'user', 'system'
            $table->string('action', 50); // 'created', 'updated', 'deleted', 'approved', 'failed', etc.
            $table->string('level', 20)->default('info')->index(); // 'critical', 'important', 'info', 'debug'
            $table->string('title');
            $table->text('description')->nullable();
            
            // Related entities (polymorphic)
            $table->nullableUuidMorphs('subject'); // The entity that performed the action
            $table->nullableUuidMorphs('causer'); // The user who caused the action
            
            // Additional data
            $table->jsonb('properties')->default('{}'); // Extra data like amounts, IDs, etc.
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            // Visual
            $table->string('icon', 50)->nullable(); // Icon to display
            $table->string('color', 20)->default('gray'); // Color for the activity
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['created_at', 'level']); // For filtering by time and importance
            $table->index(['type', 'action']); // For filtering by activity type
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};