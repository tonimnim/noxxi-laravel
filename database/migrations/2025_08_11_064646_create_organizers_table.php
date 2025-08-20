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
            $table->text('business_description')->nullable();
            $table->text('business_logo_url')->nullable();

            // Location & Localization
            $table->string('business_country', 3)->default('KE');
            $table->text('business_address')->nullable();
            $table->string('business_timezone')->default('Africa/Nairobi');

            // Payment Configuration
            $table->jsonb('payment_methods')->default('[]');
            $table->string('default_currency', 3)->default('KES');

            // Statistics
            $table->integer('total_events')->default(0);
            $table->integer('total_tickets_sold')->default(0);
            $table->jsonb('total_revenue')->default('{}');
            $table->decimal('rating', 3, 2)->nullable();
            $table->integer('total_reviews')->default(0);

            // API & Webhooks
            $table->string('api_key')->unique()->nullable();
            $table->text('webhook_url')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->jsonb('webhook_events')->default('["ticket.purchased", "ticket.validated", "event.created", "event.cancelled"]');

            // Settings
            $table->decimal('commission_rate', 5, 2)->default(10.00);
            $table->integer('settlement_period_days')->default(7);
            $table->boolean('auto_approve_events')->default(false);

            // Status flags
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);

            // Metadata
            $table->jsonb('metadata')->default('{}');

            // Approval tracking
            $table->timestamp('approved_at')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('api_key');
            $table->index('business_country');
            $table->index('is_active');
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
