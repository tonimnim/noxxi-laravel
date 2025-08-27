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
        Schema::create('organizers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Business Information
            $table->string('business_name');
            $table->enum('business_type', ['individual', 'company', 'non_profit'])->default('individual');
            $table->text('business_description')->nullable();
            $table->text('business_logo_url')->nullable();

            // Location & Localization
            $table->string('business_country', 3)->default('KE');
            $table->text('business_address')->nullable();
            $table->string('business_timezone')->default('Africa/Nairobi');

            // Payment Configuration
            $table->jsonb('payment_methods')->default('[]');
            $table->string('default_currency', 3)->default('KES');
            
            // Payout Configuration
            $table->enum('payout_method', ['mpesa', 'bank'])->default('mpesa')->comment('Preferred payout method');
            $table->string('mpesa_number', 20)->nullable()->comment('M-Pesa number for payouts');
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->json('bank_details')->nullable()->comment('Additional bank details for payouts');
            $table->enum('payout_frequency', ['weekly', 'bi_weekly', 'monthly', 'manual'])->default('weekly');

            // Statistics
            $table->integer('total_events')->default(0);
            $table->integer('total_tickets_sold')->default(0);
            $table->jsonb('total_revenue')->default('{}');
            $table->decimal('monthly_volume', 15, 2)->nullable()->comment('Monthly transaction volume for analytics');
            $table->decimal('total_commission_paid', 15, 2)->default(0)->comment('Total commission paid to platform');
            $table->decimal('rating', 3, 2)->nullable();
            $table->integer('total_reviews')->default(0);

            // API & Webhooks
            $table->string('api_key')->unique()->nullable();
            $table->text('webhook_url')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->jsonb('webhook_events')->default('["ticket.purchased", "ticket.validated", "event.created", "event.cancelled"]');

            // Settings & Premium Features
            $table->decimal('commission_rate', 5, 2)->nullable()->default(10.00)->comment('Nullable to allow commission hierarchy');
            $table->integer('settlement_period_days')->default(7);
            $table->boolean('auto_approve_events')->default(false);
            $table->boolean('absorb_payout_fees')->default(false)->comment('Premium perk - platform absorbs payout fees');
            $table->boolean('priority_support')->default(false)->comment('Premium perk - priority customer support');
            $table->boolean('auto_featured_listings')->default(false)->comment('Premium perk - listings automatically featured');

            // Status & Verification flags
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_verified')->default(false)->comment('KYC verification status');
            $table->string('verification_status', 20)->default('pending');
            $table->enum('status', ['normal', 'premium'])->default('normal')->comment('Organizer tier: normal or premium');
            $table->boolean('two_factor_enabled')->default(false)->comment('2FA enabled for payout requests');
            $table->text('two_factor_secret')->nullable()->comment('Encrypted 2FA secret');

            // Metadata
            $table->jsonb('metadata')->default('{}');

            // Approval & Date tracking
            $table->timestamp('approved_at')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->comment('Date when KYC verification completed');
            $table->timestamp('premium_since')->nullable()->comment('Date when organizer became premium');
            $table->timestamp('last_payout_at')->nullable()->comment('Last successful payout date');

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('api_key');
            $table->index('business_country');
            $table->index('business_type');
            $table->index('is_active');
            $table->index('is_verified');
            $table->index('verification_status');
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizers');
    }
};
