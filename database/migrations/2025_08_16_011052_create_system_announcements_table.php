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
        Schema::create('system_announcements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('type', ['maintenance', 'update', 'alert', 'info'])->default('info');
            $table->string('title');
            $table->text('message');
            $table->enum('priority', ['critical', 'high', 'medium', 'low'])->default('low');
            $table->boolean('is_active')->default(true);
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            
            // Indexes for optimized queries
            $table->index('is_active');
            $table->index('priority');
            $table->index('scheduled_for');
            $table->index(['is_active', 'scheduled_for', 'expires_at']); // Composite index for active announcements query
            
            // Foreign key
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_announcements');
    }
};
