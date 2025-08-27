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
            $table->string('reference_number', 50)->nullable()->unique()->comment('Unique payout reference');
            $table->enum('type', ['full', 'partial'])->default('full')->comment('Type of payout');
            
            // Payout amount details
            $table->decimal('requested_amount', 10, 2)->nullable()->comment('Amount requested by organizer');
            $table->decimal('gross_amount', 15, 2)->comment('Total sales amount');
            $table->decimal('platform_commission', 10, 2)->nullable()->comment('Total platform commission deducted');
            $table->decimal('commission_deducted', 10, 2)->comment('Platform commission');
            $table->decimal('commission_amount', 15, 2)->nullable()->comment('Commission amount in currency');
            $table->decimal('payout_fee', 10, 2)->default(0)->comment('M-Pesa/Bank transfer fee');
            $table->boolean('fee_absorbed')->default(false)->comment('Whether platform absorbed the payout fee');
            $table->decimal('fees_deducted', 10, 2)->default(0)->comment('Gateway fees');
            $table->decimal('net_amount', 15, 2)->comment('Amount to pay organizer');
            $table->string('currency', 3)->default('KES');
            $table->enum('payout_method', ['mpesa', 'bank'])->nullable()->comment('Method of payout');
            $table->json('payout_details')->nullable()->comment('M-Pesa number or bank details used');
            
            // Period covered by this payout
            $table->date('period_start');
            $table->date('period_end');
            
            // Transaction count and references
            $table->integer('transaction_count')->default(0);
            $table->json('transaction_ids')->nullable()->comment('Array of transaction IDs included');
            $table->json('booking_ids')->nullable()->comment('Array of booking IDs included in this payout');
            $table->json('metadata')->nullable()->comment('Additional payout metadata');
            
            // Status
            $table->enum('status', [
                'pending',      // Awaiting admin approval
                'on_hold',      // Put on hold by admin
                'approved',     // Approved, ready to process
                'processing',   // Payment being processed
                'paid',         // Successfully paid
                'completed',    // Fully completed
                'failed',       // Payment failed
                'rejected',     // Rejected by admin
                'cancelled',    // Cancelled by admin
            ])->default('pending');
            
            // Payment details
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('bank_reference')->nullable();
            $table->string('transaction_reference')->nullable()->comment('Payment gateway transaction reference');
            
            // Admin management
            $table->uuid('approved_by')->nullable();
            $table->uuid('processed_by')->nullable();
            $table->uuid('rejected_by')->nullable()->comment('User ID who rejected the payout');
            $table->uuid('held_by')->nullable()->comment('User ID who put payout on hold');
            $table->text('admin_notes')->nullable();
            $table->string('failure_reason')->nullable();
            $table->text('rejection_reason')->nullable()->comment('Reason for rejection');
            $table->text('hold_reason')->nullable()->comment('Reason for holding');
            
            // Timestamps
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('held_at')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('organizer_id')->references('id')->on('organizers')->onDelete('restrict');
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('processed_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('rejected_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('held_by')->references('id')->on('users')->nullOnDelete();
            
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