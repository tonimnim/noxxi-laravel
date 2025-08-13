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
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('booking_id');
            $table->uuid('user_id');
            
            // Refund details
            $table->text('reason');
            $table->decimal('requested_amount', 10, 2);
            $table->decimal('approved_amount', 10, 2)->nullable();
            $table->string('currency', 3)->default('KES');
            
            // Status
            $table->enum('status', [
                'pending',      // Customer submitted, awaiting review
                'reviewing',    // Admin/organizer reviewing
                'approved',     // Approved, ready to process
                'rejected',     // Request rejected
                'processed',    // Refund completed
                'cancelled',    // Customer cancelled request
            ])->default('pending');
            
            // Processing details
            $table->uuid('reviewed_by')->nullable();
            $table->uuid('processed_by')->nullable();
            $table->text('review_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Reference to actual refund transaction
            $table->uuid('transaction_id')->nullable();
            
            // Customer communication
            $table->text('customer_message')->nullable();
            $table->text('admin_response')->nullable();
            
            // Timestamps
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('reviewed_by')->references('id')->on('users');
            $table->foreign('processed_by')->references('id')->on('users');
            $table->foreign('transaction_id')->references('id')->on('transactions');
            
            // Indexes
            $table->index('booking_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
    }
};