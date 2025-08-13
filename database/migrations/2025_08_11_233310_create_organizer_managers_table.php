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
        Schema::create('organizer_managers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // References
            $table->uuid('organizer_id');
            $table->uuid('user_id');
            $table->uuid('granted_by');
            
            // Simple permissions for ticket scanning only
            $table->boolean('can_scan_tickets')->default(true);
            $table->boolean('can_validate_entries')->default(true);
            
            // Event scope (null means all organizer's events)
            $table->json('event_ids')->nullable()->comment('Specific events this scanner can access, null = all events');
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Validity period for temporary scanners
            $table->timestamp('valid_from')->useCurrent();
            $table->timestamp('valid_until')->nullable();
            
            // Additional info
            $table->text('notes')->nullable()->comment('Notes about this scanner, e.g., "Gate A Scanner"');
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('organizer_id')->references('id')->on('organizers')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('granted_by')->references('id')->on('users');
            
            // Indexes
            $table->index('organizer_id');
            $table->index('user_id');
            $table->index('is_active');
            $table->index(['valid_from', 'valid_until']);
            
            // Ensure one active scanner permission per user per organizer
            $table->unique(['organizer_id', 'user_id'], 'unique_active_scanner');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizer_managers');
    }
};