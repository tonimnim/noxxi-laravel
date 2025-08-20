<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->string('verification_status', 20)->default('pending')->after('is_verified');
            $table->index('verification_status');
        });
        
        // Update existing records
        DB::table('organizers')
            ->where('is_verified', true)
            ->update(['verification_status' => 'verified']);
            
        DB::table('organizers')
            ->where('is_verified', false)
            ->update(['verification_status' => 'pending']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->dropIndex(['verification_status']);
            $table->dropColumn('verification_status');
        });
    }
};
