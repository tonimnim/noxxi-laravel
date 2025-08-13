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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Transaction type
            $table->enum('type', [
                'ticket_sale',    // Customer purchases tickets
                'refund',         // Refund to customer
                'payout',         // Payment to organizer
                'commission',     // Platform commission
                'fee',            // Payment gateway fees
                'withdrawal',     // Organizer withdrawal
            ]);
            
            // Related entities
            $table->uuid('booking_id')->nullable();
            $table->uuid('organizer_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->uuid('payout_id')->nullable();
            
            // Financial details
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('KES');
            $table->decimal('commission_amount', 10, 2)->nullable();
            $table->decimal('gateway_fee', 10, 2)->nullable();
            $table->decimal('net_amount', 15, 2)->nullable();
            
            // Payment gateway information
            $table->enum('payment_gateway', [
                'paystack',       // For card payments, Apple Pay
                'mpesa',          // M-Pesa Daraja direct
                'crypto',         // Future crypto integration
                'bank_transfer',  // Bank transfers
                'cash',           // Cash payments
                'free',           // Free events
            ])->nullable();
            
            $table->string('payment_method', 50)->nullable(); // card, apple_pay, mpesa, crypto_token, etc.
            $table->string('payment_reference')->nullable()->index();
            $table->string('gateway_reference')->nullable(); // Gateway's transaction ID
            
            // Status tracking
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'cancelled',
                'reversed',
            ])->default('pending');
            
            // Additional tracking
            $table->string('failure_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            
            // Metadata for flexible data storage
            $table->json('metadata')->nullable();
            // Can store: card_last4, mpesa_phone, crypto_wallet, etc.
            
            // M-Pesa specific fields (stored in metadata)
            // - mpesa_receipt_number
            // - mpesa_phone_number
            // - mpesa_transaction_date
            
            // Crypto placeholder fields (stored in metadata)
            // - wallet_address
            // - token_amount
            // - blockchain_tx_hash
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->foreign('organizer_id')->references('id')->on('organizers')->onDelete('restrict');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            
            // Indexes
            $table->index('type');
            $table->index('status');
            $table->index('payment_gateway');
            $table->index('created_at');
            $table->index(['organizer_id', 'type', 'status']);
            $table->index(['user_id', 'type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};