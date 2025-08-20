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
        Schema::table('organizers', function (Blueprint $table) {
            // Add is_verified column for KYC verification status
            $table->boolean('is_verified')->default(false)->after('is_featured');
            
            // Add business_type column
            $table->enum('business_type', ['individual', 'company', 'non_profit'])->default('individual')->after('business_name');
            
            // Add verification date tracking
            $table->timestamp('verified_at')->nullable()->after('is_verified');
            
            // Add indexes for performance
            $table->index('is_verified');
            $table->index('business_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->dropIndex(['is_verified']);
            $table->dropIndex(['business_type']);
            $table->dropColumn(['is_verified', 'business_type', 'verified_at']);
        });
    }
};