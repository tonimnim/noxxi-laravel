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
        Schema::create('event_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            
            // Visual elements
            $table->text('icon_url')->nullable();
            $table->text('banner_url')->nullable();
            $table->string('color_hex', 7)->nullable();
            
            // Hierarchy
            $table->uuid('parent_id')->nullable();
            $table->integer('display_order')->default(0);
            
            // Description
            $table->text('description')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            
            // Metadata
            $table->jsonb('metadata')->default('{}');
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index('slug');
            $table->index('parent_id');
            $table->index('is_active');
            $table->index('display_order');
        });
        
        // Add self-referencing foreign key after table creation
        Schema::table('event_categories', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('event_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_categories');
    }
};
