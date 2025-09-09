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
        Schema::table('bookings', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['event_id']);
            
            // Re-add with cascade delete
            $table->foreign('event_id')
                ->references('id')
                ->on('events')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Drop the cascade foreign key
            $table->dropForeign(['event_id']);
            
            // Re-add with restrict delete (original behavior)
            $table->foreign('event_id')
                ->references('id')
                ->on('events')
                ->onDelete('restrict');
        });
    }
};
