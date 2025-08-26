<?php

namespace Tests\Feature;

use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminOrganizerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Organizer $organizer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com',
            'email_verified_at' => now(),
        ]);

        // Create organizer
        $organizerUser = User::factory()->create([
            'role' => 'organizer',
            'email' => 'organizer@test.com',
            'email_verified_at' => now(),
        ]);

        $this->organizer = Organizer::factory()->create([
            'user_id' => $organizerUser->id,
            'business_name' => 'Test Events Ltd',
            'commission_rate' => 8.0,
            'status' => 'normal',
            'total_revenue' => ['KES' => 50000], // This should not be editable
            'total_commission_paid' => 4000, // This should not be editable
        ]);
    }

    public function test_admin_can_access_organizers_list()
    {
        $this->actingAs($this->admin)
            ->get('/admin/organizers')
            ->assertOk()
            ->assertSee('Test Events Ltd');
    }

    public function test_admin_can_edit_organizer_commission_rate()
    {
        $this->actingAs($this->admin)
            ->get("/admin/organizers/{$this->organizer->id}/edit")
            ->assertOk()
            ->assertSee('Commission', false) // Use false to avoid HTML entity encoding issues
            ->assertSee('Financial Summary', false)
            ->assertDontSee('name="total_revenue"') // Should not be an input field
            ->assertDontSee('name="total_commission_paid"') // Should not be an input field
            ->assertSee('Total Revenue:', false) // Should be display-only
            ->assertSee('Total Commission:', false); // Should be display-only
    }

    public function test_admin_can_update_organizer_without_validation_errors()
    {
        $this->actingAs($this->admin);

        // Test that we can update the organizer without validation errors
        // Specifically testing that total_revenue and total_commission_paid 
        // are not required fields and don't cause validation errors
        Livewire::test(\App\Filament\Admin\Resources\OrganizerResource\Pages\EditOrganizer::class, [
            'record' => $this->organizer->id,
        ])
            ->fillForm([
                'business_name' => 'Updated Events Ltd',
                'business_type' => 'company',
                'status' => 'premium', // This will automatically set commission_rate to 1.5
                'is_verified' => true,
                'is_active' => true,
                'payout_frequency' => 'weekly',
                // Note: Not including total_revenue or total_commission_paid
                // These fields should be display-only and not cause validation errors
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        // Verify the update worked
        $this->organizer->refresh();
        $this->assertEquals('Updated Events Ltd', $this->organizer->business_name);
        $this->assertEquals(1.5, $this->organizer->commission_rate); // Premium rate is 1.5%
        $this->assertEquals('premium', $this->organizer->status);
        $this->assertTrue($this->organizer->absorb_payout_fees); // Premium perk
        $this->assertTrue($this->organizer->auto_featured_listings); // Premium perk
    }

    public function test_admin_can_toggle_organizer_premium_status()
    {
        $this->actingAs($this->admin);

        // Start with normal status
        $this->assertEquals('normal', $this->organizer->status);
        $this->assertEquals(8.0, $this->organizer->commission_rate);

        // Toggle to premium from the list page
        Livewire::test(\App\Filament\Admin\Resources\OrganizerResource\Pages\ListOrganizers::class)
            ->callTableAction('togglePremium', $this->organizer)
            ->assertNotified();

        $this->organizer->refresh();
        $this->assertEquals('premium', $this->organizer->status);
        $this->assertEquals(1.5, $this->organizer->commission_rate);
        $this->assertTrue($this->organizer->absorb_payout_fees);
        $this->assertTrue($this->organizer->auto_featured_listings);
    }

    public function test_financial_summary_displays_correctly()
    {
        // Create some transactions
        $this->organizer->transactions()->create([
            'user_id' => $this->organizer->user_id,
            'type' => 'ticket_sale',
            'status' => 'completed',
            'amount' => 10000,
            'commission_amount' => -800, // 8% commission
            'net_amount' => 9200,
            'currency' => 'KES',
            'reference' => 'TEST-001',
            'narration' => 'Test ticket sale',
        ]);

        $this->organizer->transactions()->create([
            'user_id' => $this->organizer->user_id,
            'type' => 'ticket_sale',
            'status' => 'completed',
            'amount' => 5000,
            'commission_amount' => -400, // 8% commission
            'net_amount' => 4600,
            'currency' => 'KES',
            'reference' => 'TEST-002',
            'narration' => 'Test ticket sale',
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/admin/organizers/{$this->organizer->id}/edit");

        $response->assertOk()
            ->assertSee('Financial Summary')
            ->assertSee('Total Revenue:')
            ->assertSee('15,000.00') // Total of both transactions
            ->assertSee('Total Commission:')
            ->assertSee('1,200.00'); // Total commission
    }
}