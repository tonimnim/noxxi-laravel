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
        Schema::create('tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('booking_id');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->uuid('event_id');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('restrict');
            
            // Ticket identification
            $table->string('ticket_code', 20)->unique();
            $table->text('qr_code')->nullable();
            $table->string('ticket_hash');
            
            // Ticket details
            $table->string('ticket_type', 100);
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('KES');
            $table->string('seat_number', 50)->nullable();
            $table->string('seat_section', 50)->nullable();
            
            // Holder information
            $table->string('holder_name');
            $table->string('holder_email');
            $table->string('holder_phone', 20)->nullable();
            $table->uuid('assigned_to')->nullable();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            
            // Status tracking
            $table->enum('status', ['valid', 'used', 'cancelled', 'transferred', 'expired'])->default('valid');
            
            // Scanning/validation tracking
            $table->timestamp('used_at')->nullable();
            $table->uuid('used_by')->nullable();
            $table->foreign('used_by')->references('id')->on('users')->nullOnDelete();
            $table->string('entry_gate', 50)->nullable();
            $table->string('device_fingerprint')->nullable();
            
            // Transfer tracking
            $table->uuid('transferred_from')->nullable();
            $table->foreign('transferred_from')->references('id')->on('users')->nullOnDelete();
            $table->uuid('transferred_to')->nullable();
            $table->foreign('transferred_to')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('transferred_at')->nullable();
            $table->text('transfer_reason')->nullable();
            
            // Special requirements
            $table->text('special_requirements')->nullable();
            $table->text('notes')->nullable();
            
            // Validity period
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            
            // Offline support
            $table->jsonb('offline_validation_data')->nullable();
            
            // Metadata
            $table->jsonb('metadata')->default('{}');
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index('booking_id');
            $table->index('event_id');
            $table->index('ticket_code');
            $table->index('status');
            $table->index('assigned_to');
            $table->index('transferred_to');
            $table->index('used_at');
            $table->index(['valid_from', 'valid_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
