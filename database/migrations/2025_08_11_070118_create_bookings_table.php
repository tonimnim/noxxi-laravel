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
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('booking_reference', 20)->unique();
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('event_id');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('restrict');

            // Booking details
            $table->integer('quantity')->default(1)->check('quantity > 0');
            $table->integer('ticket_quantity')->nullable(); // For backward compatibility
            $table->decimal('subtotal', 10, 2);
            $table->decimal('service_fee', 10, 2)->default(0);
            $table->decimal('payment_fee', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 3)->default('KES');

            // Contact info
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 20);

            // Status tracking
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'expired', 'refunded'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'processing', 'paid', 'failed', 'refunded', 'partial_refund'])->default('unpaid');
            $table->enum('payment_method', ['mpesa', 'card', 'bank_transfer', 'paypal', 'stripe', 'cash'])->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->jsonb('payment_provider_data')->default('{}');

            // Promo codes and discounts
            $table->string('promo_code', 50)->nullable();
            $table->jsonb('promo_details')->default('{}');

            // Additional booking info
            $table->jsonb('booking_metadata')->default('{}');
            $table->jsonb('ticket_types'); // Selected ticket types and quantities

            // Session tracking
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('booking_source')->default('web'); // web, mobile_app, api, admin

            // Timestamps
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('booking_reference');
            $table->index('user_id');
            $table->index('event_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index(['created_at']);
            $table->index('expires_at');
            $table->index('payment_reference');
            $table->index(['status', 'created_at']); // For dashboard queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
