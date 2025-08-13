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
        Schema::table('events', function (Blueprint $table) {
            // Media Management (max 3 images + optional video)
            $table->jsonb('media')->default('{"images": [], "video_url": null}')->after('cover_image_url');
            
            // Comprehensive Policies
            $table->jsonb('policies')->default('{"age_restriction": null, "refund_policy": null, "terms_conditions": null, "what_included": null, "what_not_included": null, "dress_code": null, "items_to_bring": null, "special_instructions": null}')->after('refund_policy');
            
            // Marketing & SEO
            $table->jsonb('marketing')->default('{"tags": [], "seo_description": null, "featured": false, "featured_until": null, "promotional_video": null}')->after('seo_keywords');
            
            // Category-specific metadata
            $table->jsonb('category_metadata')->default('{}')->after('category_id');
            // Cinema: rating, language, subtitles, screen_type
            // Sports: teams, tournament, seating_sections
            // Experiences: duration, group_size_limit, skill_level
            
            // QR Code Security
            $table->string('qr_secret_key')->nullable()->after('offline_mode_data');
            $table->jsonb('gates_config')->default('{"gates": ["main"], "vip_gates": []}')->after('qr_secret_key');
            
            // Ticket Management Enhancement
            $table->jsonb('ticket_sales_config')->default('{"max_tickets_per_order": 10, "show_remaining_tickets": true, "enable_waiting_list": false}')->after('ticket_types');
            
            // Event Status Tracking
            $table->timestamp('first_published_at')->nullable()->after('published_at');
            $table->timestamp('last_modified_at')->nullable()->after('first_published_at');
            $table->string('modified_by')->nullable()->after('last_modified_at');
            
            // Analytics & Tracking
            $table->jsonb('analytics')->default('{"page_views": 0, "unique_visitors": 0, "conversion_rate": 0, "avg_time_on_page": 0}')->after('share_count');
            
            // Auto-save drafts
            $table->jsonb('draft_data')->nullable()->after('status');
            $table->timestamp('draft_saved_at')->nullable()->after('draft_data');
            
            // Listing Settings
            $table->jsonb('listing_settings')->default('{"allow_guests": true, "require_approval": false, "enable_comments": false, "enable_reviews": true}')->after('requires_approval');
            
            // Financial Settings
            $table->decimal('organizer_fee', 5, 2)->default(0)->after('currency');
            $table->decimal('platform_fee', 5, 2)->default(10)->after('organizer_fee');
            
            // Add indexes for performance
            $table->index('qr_secret_key');
            $table->index('first_published_at');
            $table->fullText(['title', 'description'], 'events_fulltext_search');
        });
        
        // Update the ticket_types column to have proper structure
        DB::statement("
            UPDATE events 
            SET ticket_types = '[]'::jsonb 
            WHERE ticket_types IS NULL OR ticket_types = '[]'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'media',
                'policies',
                'marketing',
                'category_metadata',
                'qr_secret_key',
                'gates_config',
                'ticket_sales_config',
                'first_published_at',
                'last_modified_at',
                'modified_by',
                'analytics',
                'draft_data',
                'draft_saved_at',
                'listing_settings',
                'organizer_fee',
                'platform_fee'
            ]);
            
            $table->dropIndex('events_qr_secret_key_index');
            $table->dropIndex('events_first_published_at_index');
            $table->dropIndex('events_fulltext_search');
        });
    }
};