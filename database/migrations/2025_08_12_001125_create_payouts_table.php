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
        Schema::create('payouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organizer_id');
            
            // Payout amount details
            $table->decimal('gross_amount', 15, 2)->comment('Total sales amount');
            $table->decimal('commission_deducted', 10, 2)->comment('Platform commission');
            $table->decimal('fees_deducted', 10, 2)->default(0)->comment('Gateway fees');
            $table->decimal('net_amount', 15, 2)->comment('Amount to pay organizer');
            $table->string('currency', 3)->default('KES');
            
            // Period covered by this payout
            $table->date('period_start');
            $table->date('period_end');
            
            // Transaction count and references
            $table->integer('transaction_count')->default(0);
            $table->json('transaction_ids')->nullable()->comment('Array of transaction IDs included');
            
            // Status
            $table->enum('status', [
                'pending',      // Awaiting admin approval
                'approved',     // Approved, ready to process
                'processing',   // Payment being processed
                'paid',         // Successfully paid
                'failed',       // Payment failed
                'cancelled',    // Cancelled by admin
            ])->default('pending');
            
            // Payment details
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('bank_reference')->nullable();
            
            // Admin management
            $table->uuid('approved_by')->nullable();
            $table->uuid('processed_by')->nullable();
            $table->text('admin_notes')->nullable();
            $table->string('failure_reason')->nullable();
            
            // Timestamps
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('organizer_id')->references('id')->on('organizers')->onDelete('restrict');
            $table->foreign('approved_by')->references('id')->on('users');
            $table->foreign('processed_by')->references('id')->on('users');
            
            // Indexes
            $table->index('organizer_id');
            $table->index('status');
            $table->index(['period_start', 'period_end']);
            $table->index('created_at');
            
            // Unique constraint to prevent duplicate payouts for same period
            $table->unique(['organizer_id', 'period_start', 'period_end'], 'unique_payout_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};