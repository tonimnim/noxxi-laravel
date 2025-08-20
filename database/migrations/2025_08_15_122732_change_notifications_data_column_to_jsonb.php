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
        // For PostgreSQL, we need to change the column type from text to jsonb
        // This requires dropping and recreating the column
        
        Schema::table('notifications', function (Blueprint $table) {
            // First, drop the text column
            $table->dropColumn('data');
        });
        
        Schema::table('notifications', function (Blueprint $table) {
            // Then add it back as jsonb
            $table->jsonb('data')->after('notifiable_id');
        });
        
        // Add an index on the data column for better query performance
        DB::statement('CREATE INDEX notifications_data_format_index ON notifications ((data->>\'format\'))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the JSON index
        DB::statement('DROP INDEX IF EXISTS notifications_data_format_index');
        
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('data');
        });
        
        Schema::table('notifications', function (Blueprint $table) {
            $table->text('data')->after('notifiable_id');
        });
    }
};