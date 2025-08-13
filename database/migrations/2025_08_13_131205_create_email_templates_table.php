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
        Schema::create('email_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique(); // e.g., 'booking_confirmed', 'event_reminder'
            $table->string('subject');
            $table->text('body');
            $table->string('greeting')->nullable();
            $table->text('footer')->nullable();
            $table->json('variables'); // Available placeholders for this template
            $table->json('settings')->nullable(); // Additional settings
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            
            $table->index('name');
            $table->index('is_active');
        });
        
        // Insert default templates
        DB::table('email_templates')->insert([
            [
                'id' => Str::uuid(),
                'name' => 'booking_confirmed',
                'subject' => 'Booking Confirmed - {event_name}',
                'greeting' => 'Hello {customer_name}!',
                'body' => "Thank you for purchasing tickets for {event_name}.\n\nEvent Details:\nDate: {event_date}\nVenue: {venue_name}\nBooking Reference: {booking_reference}\nTotal Amount: {total_amount}\nNumber of Tickets: {ticket_count}\n\nYour tickets are available in the Noxxi app. Please open the app to view and manage your tickets.",
                'footer' => 'Thank you for using Noxxi!',
                'variables' => json_encode([
                    '{customer_name}' => 'Customer full name',
                    '{event_name}' => 'Event/listing title',
                    '{event_date}' => 'Event date and time',
                    '{venue_name}' => 'Venue name',
                    '{venue_address}' => 'Venue address',
                    '{booking_reference}' => 'Booking reference code',
                    '{total_amount}' => 'Total amount with currency',
                    '{ticket_count}' => 'Number of tickets',
                    '{organizer_name}' => 'Organizer name',
                ]),
                'settings' => json_encode(['show_app_reminder' => true]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'event_reminder',
                'subject' => 'Reminder: {event_name} is tomorrow!',
                'greeting' => 'Hello {customer_name}!',
                'body' => "This is a friendly reminder that {event_name} is happening tomorrow.\n\nEvent Details:\nDate: {event_date}\nVenue: {venue_name}\nAddress: {venue_address}\n\nPlease ensure you have the Noxxi app installed to access your tickets.",
                'footer' => 'See you there!',
                'variables' => json_encode([
                    '{customer_name}' => 'Customer full name',
                    '{event_name}' => 'Event/listing title',
                    '{event_date}' => 'Event date and time',
                    '{venue_name}' => 'Venue name',
                    '{venue_address}' => 'Venue address',
                    '{hours_until}' => 'Hours until event',
                ]),
                'settings' => json_encode(['send_hours_before' => 24]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};