<?php

namespace Tests\Feature\Api\V1;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\OrganizerManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ManagerManifestAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Passport is already installed in the main database, no need to reinstall
    }

    public function test_organizer_can_access_their_event_manifest()
    {
        // Create organizer and their event
        $organizer = Organizer::factory()->create();
        $user = $organizer->user;
        $event = Event::factory()->create(['organizer_id' => $organizer->id]);

        // Act as organizer
        Passport::actingAs($user);

        // Request manifest
        $response = $this->getJson("/api/v1/events/{$event->id}/manifest");

        // Assert success
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'event' => ['id', 'title', 'venue', 'date'],
                'manifest',
                'expires_at'
            ])
            ->assertJson([
                'success' => true,
                'event' => [
                    'id' => $event->id,
                    'title' => $event->title,
                ]
            ]);
    }

    public function test_manager_with_scanning_permission_can_access_manifest()
    {
        // Create organizer and their event
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->create(['organizer_id' => $organizer->id]);

        // Create a manager user
        $managerUser = User::factory()->create();

        // Grant scanning permissions to the manager
        OrganizerManager::create([
            'organizer_id' => $organizer->id,
            'user_id' => $managerUser->id,
            'granted_by' => $organizer->user->id,
            'can_scan_tickets' => true,
            'can_validate_entries' => true,
            'event_ids' => [], // Empty means access to all organizer's events
            'is_active' => true,
        ]);

        // Act as manager
        Passport::actingAs($managerUser);

        // Request manifest
        $response = $this->getJson("/api/v1/events/{$event->id}/manifest");

        // Assert success
        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'event' => ['id', 'title', 'venue', 'date'],
                'manifest',
                'expires_at'
            ])
            ->assertJson([
                'success' => true,
                'event' => [
                    'id' => $event->id,
                ]
            ]);
    }

    public function test_manager_with_specific_event_permission_can_access_manifest()
    {
        // Create organizer and their events
        $organizer = Organizer::factory()->create();
        $event1 = Event::factory()->create(['organizer_id' => $organizer->id]);
        $event2 = Event::factory()->create(['organizer_id' => $organizer->id]);

        // Create a manager user
        $managerUser = User::factory()->create();

        // Grant scanning permissions only for event1
        OrganizerManager::create([
            'organizer_id' => $organizer->id,
            'user_id' => $managerUser->id,
            'granted_by' => $organizer->user->id,
            'can_scan_tickets' => true,
            'can_validate_entries' => true,
            'event_ids' => [$event1->id], // Only access to event1
            'is_active' => true,
        ]);

        // Act as manager
        Passport::actingAs($managerUser);

        // Request manifest for event1 (should succeed)
        $response = $this->getJson("/api/v1/events/{$event1->id}/manifest");
        $response->assertOk()
            ->assertJson(['success' => true]);

        // Request manifest for event2 (should fail)
        $response = $this->getJson("/api/v1/events/{$event2->id}/manifest");
        $response->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'You do not have permission to access this manifest'
            ]);
    }

    public function test_manager_without_scanning_permission_cannot_access_manifest()
    {
        // Create organizer and their event
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->create(['organizer_id' => $organizer->id]);

        // Create a manager user
        $managerUser = User::factory()->create();

        // Grant permissions WITHOUT scanning ability
        OrganizerManager::create([
            'organizer_id' => $organizer->id,
            'user_id' => $managerUser->id,
            'granted_by' => $organizer->user->id,
            'can_scan_tickets' => false, // No scanning permission
            'can_validate_entries' => false,
            'event_ids' => [],
            'is_active' => true,
        ]);

        // Act as manager
        Passport::actingAs($managerUser);

        // Request manifest
        $response = $this->getJson("/api/v1/events/{$event->id}/manifest");

        // Assert forbidden
        $response->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'You do not have permission to access this manifest'
            ]);
    }

    public function test_inactive_manager_cannot_access_manifest()
    {
        // Create organizer and their event
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->create(['organizer_id' => $organizer->id]);

        // Create a manager user
        $managerUser = User::factory()->create();

        // Grant inactive scanning permissions
        OrganizerManager::create([
            'organizer_id' => $organizer->id,
            'user_id' => $managerUser->id,
            'granted_by' => $organizer->user->id,
            'can_scan_tickets' => true,
            'can_validate_entries' => true,
            'event_ids' => [],
            'is_active' => false, // Inactive permission
        ]);

        // Act as manager
        Passport::actingAs($managerUser);

        // Request manifest
        $response = $this->getJson("/api/v1/events/{$event->id}/manifest");

        // Assert forbidden
        $response->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'You do not have permission to access this manifest'
            ]);
    }

    public function test_expired_manager_permission_cannot_access_manifest()
    {
        // Create organizer and their event
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->create(['organizer_id' => $organizer->id]);

        // Create a manager user
        $managerUser = User::factory()->create();

        // Grant expired scanning permissions
        OrganizerManager::create([
            'organizer_id' => $organizer->id,
            'user_id' => $managerUser->id,
            'granted_by' => $organizer->user->id,
            'can_scan_tickets' => true,
            'can_validate_entries' => true,
            'event_ids' => [],
            'is_active' => true,
            'valid_until' => now()->subDay(), // Expired yesterday
        ]);

        // Act as manager
        Passport::actingAs($managerUser);

        // Request manifest
        $response = $this->getJson("/api/v1/events/{$event->id}/manifest");

        // Assert forbidden
        $response->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'You do not have permission to access this manifest'
            ]);
    }

    public function test_regular_user_cannot_access_manifest()
    {
        // Create event
        $event = Event::factory()->create();

        // Create regular user (not organizer, not manager)
        $regularUser = User::factory()->create();

        // Act as regular user
        Passport::actingAs($regularUser);

        // Request manifest
        $response = $this->getJson("/api/v1/events/{$event->id}/manifest");

        // Assert forbidden
        $response->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'You do not have permission to access this manifest'
            ]);
    }

    public function test_unauthenticated_user_cannot_access_manifest()
    {
        // Create event
        $event = Event::factory()->create();

        // Request manifest without authentication
        $response = $this->getJson("/api/v1/events/{$event->id}/manifest");

        // Assert unauthorized
        $response->assertUnauthorized();
    }
}