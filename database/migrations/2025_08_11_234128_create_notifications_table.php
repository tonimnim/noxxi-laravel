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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->uuidMorphs('notifiable'); // Using UUIDs for polymorphic relation
            $table->jsonb('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            // Additional indexes for performance
            $table->index('type');
            $table->index('read_at');
            $table->index('created_at');
        });
        
        // Add JSON index for better query performance on data column
        \Illuminate\Support\Facades\DB::statement('CREATE INDEX notifications_data_format_index ON notifications ((data->>\'format\'))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement('DROP INDEX IF EXISTS notifications_data_format_index');
        Schema::dropIfExists('notifications');
    }
};
