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
        Schema::create('pending_check_ins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');
            $table->uuid('event_id');
            $table->uuid('checked_by'); // User who scanned
            $table->string('gate_id', 50)->nullable();
            $table->string('device_id', 100)->nullable();
            $table->timestamp('scanned_at'); // When scanned offline
            $table->integer('retry_count')->default(0);
            $table->timestamp('last_retry_at')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->string('batch_id', 100)->nullable(); // For batch processing
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['status', 'created_at']); // Process pending in order
            $table->index('ticket_id');
            $table->index('batch_id');
            $table->index(['event_id', 'status']);
            
            // Foreign keys
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('checked_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_check_ins');
    }
};
