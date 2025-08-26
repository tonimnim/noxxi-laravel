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
        // Fix transactions table foreign keys
        Schema::table('transactions', function (Blueprint $table) {
            // Drop existing foreign key constraints
            $table->dropForeign('transactions_organizer_id_foreign');
            $table->dropForeign('transactions_user_id_foreign');

            // Re-add with CASCADE on delete
            $table->foreign('organizer_id')
                ->references('id')
                ->on('organizers')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        // Check and fix other tables that might have similar issues
        $tablesToCheck = [
            'events' => ['organizer_id'],
            'bookings' => ['user_id', 'event_id'],
            'tickets' => ['booking_id', 'user_id'],
            'refund_requests' => ['booking_id', 'user_id'],
            'payouts' => ['organizer_id'],
            'activity_logs' => ['user_id', 'organizer_id'],
            'organizer_bank_accounts' => ['organizer_id'],
            'password_reset_tokens' => [], // This uses email, not user_id
            'notifications' => [], // This uses notifiable_id which is polymorphic
        ];

        foreach ($tablesToCheck as $tableName => $foreignKeys) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName, $foreignKeys) {
                    foreach ($foreignKeys as $foreignKey) {
                        $constraintName = $tableName.'_'.$foreignKey.'_foreign';

                        // Check if the constraint exists before trying to drop it
                        $constraintExists = DB::select("
                            SELECT constraint_name 
                            FROM information_schema.table_constraints 
                            WHERE table_name = ? 
                            AND constraint_name = ?
                            AND constraint_type = 'FOREIGN KEY'
                        ", [$tableName, $constraintName]);

                        if (! empty($constraintExists)) {
                            $table->dropForeign($constraintName);

                            // Determine the referenced table
                            $referencedTable = match ($foreignKey) {
                                'organizer_id' => 'organizers',
                                'user_id' => 'users',
                                'event_id' => 'events',
                                'booking_id' => 'bookings',
                                default => null
                            };

                            if ($referencedTable) {
                                $table->foreign($foreignKey)
                                    ->references('id')
                                    ->on($referencedTable)
                                    ->onDelete('cascade');
                            }
                        }
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert transactions table foreign keys back to RESTRICT
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['organizer_id']);
            $table->dropForeign(['user_id']);

            $table->foreign('organizer_id')
                ->references('id')
                ->on('organizers')
                ->onDelete('restrict');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
        });

        // Note: We're not reverting other tables as we don't know their original state
    }
};
