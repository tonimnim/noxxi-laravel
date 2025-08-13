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
        Schema::create('events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organizer_id');
            $table->foreign('organizer_id')->references('id')->on('organizers')->onDelete('cascade');
            
            // Basic Information
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->uuid('category_id');
            $table->foreign('category_id')->references('id')->on('event_categories');
            
            // Venue Information
            $table->string('venue_name');
            $table->text('venue_address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('city', 100);
            
            // Date & Time
            $table->timestamp('event_date');
            $table->timestamp('end_date')->nullable();
            
            // Ticketing
            $table->jsonb('ticket_types')->default('[]');
            $table->integer('capacity');
            $table->integer('tickets_sold')->default(0);
            $table->decimal('min_price', 10, 2)->nullable();
            $table->decimal('max_price', 10, 2)->nullable();
            $table->string('currency', 3)->default('KES');
            
            // Media
            $table->jsonb('images')->default('[]');
            $table->text('cover_image_url')->nullable();
            
            // Categorization
            $table->text('tags')->nullable(); // Will store as JSON array
            $table->text('seo_keywords')->nullable(); // Will store as JSON array
            
            // Status & Visibility
            $table->enum('status', ['draft', 'published', 'cancelled', 'postponed', 'completed', 'paused'])->default('draft');
            $table->boolean('featured')->default(false);
            $table->timestamp('featured_until')->nullable();
            $table->boolean('requires_approval')->default(false);
            
            // Policies & Restrictions
            $table->integer('age_restriction')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->text('refund_policy')->nullable();
            
            // Offline Support
            $table->jsonb('offline_mode_data')->nullable();
            
            // Analytics
            $table->integer('view_count')->default(0);
            $table->integer('share_count')->default(0);
            
            // Timestamps
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('organizer_id');
            $table->index('category_id');
            $table->index('slug');
            $table->index('status');
            $table->index('event_date');
            $table->index('city');
            $table->index(['featured', 'featured_until']);
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
