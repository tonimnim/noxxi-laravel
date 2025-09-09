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
            $table->jsonb('category_metadata')->default('{}')->comment('Category-specific data (cinema ratings, sports teams, etc.)');
            
            // Venue Information
            $table->string('venue_name');
            $table->text('venue_address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('city', 100);
            
            // Date & Time
            $table->timestamp('event_date')->nullable()->comment('Null for ongoing services');
            $table->timestamp('end_date')->nullable();
            $table->enum('listing_type', ['event', 'service', 'recurring'])->default('event')->comment('Type of listing');
            
            // Ticketing
            $table->jsonb('ticket_types')->default('[]');
            $table->jsonb('ticket_sales_config')->default('{"max_tickets_per_order": 10, "show_remaining_tickets": true, "enable_waiting_list": false}');
            $table->integer('capacity');
            $table->integer('tickets_sold')->default(0);
            $table->decimal('min_price', 10, 2)->nullable();
            $table->decimal('max_price', 10, 2)->nullable();
            $table->string('currency', 3)->default('KES');
            
            // Media
            $table->jsonb('images')->default('[]');
            $table->text('cover_image_url')->nullable();
            $table->jsonb('media')->default('{"images": [], "video_url": null}');
            
            // Categorization & Marketing
            $table->text('tags')->nullable(); // Will store as JSON array
            $table->text('seo_keywords')->nullable(); // Will store as JSON array
            $table->jsonb('marketing')->default('{"tags": [], "seo_description": null, "featured": false, "featured_until": null, "promotional_video": null}');
            
            // Status & Visibility
            $table->enum('status', ['draft', 'published', 'cancelled', 'postponed', 'completed', 'paused'])->default('draft');
            $table->jsonb('draft_data')->nullable()->comment('Auto-save draft data');
            $table->timestamp('draft_saved_at')->nullable();
            $table->boolean('featured')->default(false);
            $table->boolean('is_featured')->default(false)->comment('Additional featured flag');
            $table->timestamp('featured_until')->nullable();
            $table->boolean('requires_approval')->default(false);
            
            // Commission Settings
            $table->decimal('platform_fee', 5, 2)->nullable()->comment('Event-specific platform fee percentage (highest priority)');
            $table->decimal('commission_rate', 5, 2)->nullable()->comment('Platform commission percentage (overrides organizer default)');
            $table->enum('commission_type', ['percentage', 'fixed'])->default('percentage');
            
            // Policies & Restrictions
            $table->integer('age_restriction')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->jsonb('policies')->default('{"age_restriction": null, "terms_conditions": null, "what_included": null, "what_not_included": null, "dress_code": null, "items_to_bring": null, "special_instructions": null}');
            $table->jsonb('listing_settings')->default('{"enable_reviews": true}')->comment('Listing-specific settings');
            
            // Offline Support & Security
            $table->jsonb('offline_mode_data')->nullable();
            $table->string('qr_secret_key')->nullable()->comment('Secret key for QR code generation');
            $table->jsonb('gates_config')->default('{"gates": ["main"], "vip_gates": []}');
            
            // Check-in Control Settings
            $table->boolean('check_in_enabled')->default(true)->comment('Whether check-in is allowed for this event');
            $table->timestamp('check_in_opens_at')->nullable()->comment('When check-in opens (null = always open)');
            $table->timestamp('check_in_closes_at')->nullable()->comment('When check-in closes (null = never closes)');
            $table->boolean('allow_immediate_check_in')->default(true)->comment('Allow check-in without time restrictions');
            
            // Analytics
            $table->integer('view_count')->default(0);
            $table->integer('share_count')->default(0);
            $table->jsonb('analytics')->default('{"page_views": 0, "unique_visitors": 0, "conversion_rate": 0, "avg_time_on_page": 0}');
            
            // Timestamps & Tracking
            $table->timestamp('published_at')->nullable();
            $table->timestamp('first_published_at')->nullable();
            $table->timestamp('last_modified_at')->nullable();
            $table->string('modified_by')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('organizer_id');
            $table->index('category_id');
            $table->index('slug');
            $table->index('status');
            $table->index('event_date');
            $table->index('city');
            $table->index(['featured', 'featured_until']);
            $table->index('platform_fee');
            $table->index('is_featured');
            $table->index(['latitude', 'longitude']);
            $table->index(['status', 'created_at']); // For dashboard queries
            $table->index('created_at'); // For sorting
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
