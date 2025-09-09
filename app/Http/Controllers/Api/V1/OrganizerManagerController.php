<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\OrganizerManager;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrganizerManagerController extends Controller
{
    use ApiResponse;

    /**
     * List all managers for the authenticated organizer
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->organizer) {
            return $this->forbidden('You must be an organizer to manage scanners');
        }

        $managers = OrganizerManager::where('organizer_id', $user->organizer->id)
            ->with(['user:id,full_name,email,phone_number', 'grantedBy:id,full_name'])
            ->when($request->is_active !== null, function ($query) use ($request) {
                $query->where('is_active', $request->boolean('is_active'));
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        $managersData = collect($managers->items())->map(function ($manager) {
            return [
                'id' => $manager->id,
                'user' => [
                    'id' => $manager->user->id,
                    'name' => $manager->user->full_name,
                    'email' => $manager->user->email,
                    'phone' => $manager->user->phone_number,
                ],
                'permissions' => [
                    'can_scan_tickets' => $manager->can_scan_tickets,
                    'can_validate_entries' => $manager->can_validate_entries,
                ],
                'event_access' => empty($manager->event_ids) ? 'all' : 'specific',
                'event_ids' => $manager->event_ids ?? [],
                'is_active' => $manager->is_active,
                'valid_from' => $manager->valid_from?->toIso8601String(),
                'valid_until' => $manager->valid_until?->toIso8601String(),
                'granted_by' => $manager->grantedBy?->full_name,
                'created_at' => $manager->created_at->toIso8601String(),
            ];
        });

        return $this->success([
            'managers' => $managersData,
            'meta' => [
                'current_page' => $managers->currentPage(),
                'last_page' => $managers->lastPage(),
                'per_page' => $managers->perPage(),
                'total' => $managers->total(),
            ],
        ]);
    }

    /**
     * Add a new manager/scanner
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->organizer) {
            return $this->forbidden('You must be an organizer to add managers');
        }

        $validated = $request->validate([
            'search' => 'required|string', // Email or phone number
            'event_access' => 'required|in:all,specific',
            'event_ids' => 'required_if:event_access,specific|array',
            'event_ids.*' => [
                'uuid',
                Rule::exists('events', 'id')->where('organizer_id', $user->organizer->id),
            ],
            'can_scan_tickets' => 'boolean',
            'can_validate_entries' => 'boolean',
            'valid_from' => 'nullable|date|after_or_equal:today',
            'valid_until' => 'nullable|date|after:valid_from',
            'notes' => 'nullable|string|max:500',
        ]);

        // Search for user by email or phone
        $search = trim($validated['search']);
        $targetUser = User::where('email', $search)
            ->orWhere('phone_number', $search)
            ->first();

        if (!$targetUser) {
            return $this->notFound('No user found with that email or phone number. They must be registered on the platform first.');
        }

        // Check if user is trying to add themselves
        if ($targetUser->id === $user->id) {
            return $this->error('You cannot add yourself as a manager');
        }

        // Check if already a manager
        $existingManager = OrganizerManager::where('organizer_id', $user->organizer->id)
            ->where('user_id', $targetUser->id)
            ->where('is_active', true)
            ->first();

        if ($existingManager) {
            return $this->error('This user is already an active manager for your organization');
        }

        // Create new manager
        $manager = OrganizerManager::create([
            'organizer_id' => $user->organizer->id,
            'user_id' => $targetUser->id,
            'granted_by' => $user->id,
            'can_scan_tickets' => $validated['can_scan_tickets'] ?? true,
            'can_validate_entries' => $validated['can_validate_entries'] ?? true,
            'event_ids' => $validated['event_access'] === 'specific' ? $validated['event_ids'] : null,
            'is_active' => true,
            'valid_from' => $validated['valid_from'] ?? now(),
            'valid_until' => $validated['valid_until'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $manager->load('user:id,full_name,email,phone_number');

        return $this->success([
            'manager' => [
                'id' => $manager->id,
                'user' => [
                    'id' => $manager->user->id,
                    'name' => $manager->user->full_name,
                    'email' => $manager->user->email,
                    'phone' => $manager->user->phone_number,
                ],
                'permissions' => [
                    'can_scan_tickets' => $manager->can_scan_tickets,
                    'can_validate_entries' => $manager->can_validate_entries,
                ],
                'event_access' => empty($manager->event_ids) ? 'all' : 'specific',
                'event_ids' => $manager->event_ids ?? [],
                'is_active' => $manager->is_active,
                'created_at' => $manager->created_at->toIso8601String(),
            ],
        ], 'Manager added successfully');
    }

    /**
     * Update manager permissions
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        
        if (!$user->organizer) {
            return $this->forbidden('You must be an organizer to update managers');
        }

        $manager = OrganizerManager::where('id', $id)
            ->where('organizer_id', $user->organizer->id)
            ->first();

        if (!$manager) {
            return $this->notFound('Manager not found');
        }

        $validated = $request->validate([
            'event_access' => 'in:all,specific',
            'event_ids' => 'required_if:event_access,specific|array',
            'event_ids.*' => [
                'uuid',
                Rule::exists('events', 'id')->where('organizer_id', $user->organizer->id),
            ],
            'can_scan_tickets' => 'boolean',
            'can_validate_entries' => 'boolean',
            'is_active' => 'boolean',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after:valid_from',
            'notes' => 'nullable|string|max:500',
        ]);

        if (isset($validated['event_access'])) {
            $manager->event_ids = $validated['event_access'] === 'specific' 
                ? $validated['event_ids'] 
                : null;
        }

        if (isset($validated['can_scan_tickets'])) {
            $manager->can_scan_tickets = $validated['can_scan_tickets'];
        }

        if (isset($validated['can_validate_entries'])) {
            $manager->can_validate_entries = $validated['can_validate_entries'];
        }

        if (isset($validated['is_active'])) {
            $manager->is_active = $validated['is_active'];
        }

        if (array_key_exists('valid_from', $validated)) {
            $manager->valid_from = $validated['valid_from'];
        }

        if (array_key_exists('valid_until', $validated)) {
            $manager->valid_until = $validated['valid_until'];
        }

        if (array_key_exists('notes', $validated)) {
            $manager->notes = $validated['notes'];
        }

        $manager->save();
        $manager->load('user:id,full_name,email,phone_number');

        return $this->success([
            'manager' => [
                'id' => $manager->id,
                'user' => [
                    'id' => $manager->user->id,
                    'name' => $manager->user->full_name,
                    'email' => $manager->user->email,
                    'phone' => $manager->user->phone_number,
                ],
                'permissions' => [
                    'can_scan_tickets' => $manager->can_scan_tickets,
                    'can_validate_entries' => $manager->can_validate_entries,
                ],
                'event_access' => empty($manager->event_ids) ? 'all' : 'specific',
                'event_ids' => $manager->event_ids ?? [],
                'is_active' => $manager->is_active,
                'valid_from' => $manager->valid_from?->toIso8601String(),
                'valid_until' => $manager->valid_until?->toIso8601String(),
                'created_at' => $manager->created_at->toIso8601String(),
            ],
        ], 'Manager updated successfully');
    }

    /**
     * Remove a manager
     */
    public function destroy($id)
    {
        $user = Auth::user();
        
        if (!$user->organizer) {
            return $this->forbidden('You must be an organizer to remove managers');
        }

        $manager = OrganizerManager::where('id', $id)
            ->where('organizer_id', $user->organizer->id)
            ->first();

        if (!$manager) {
            return $this->notFound('Manager not found');
        }

        $manager->delete();

        return $this->success(null, 'Manager removed successfully');
    }

    /**
     * Get scan activity for managers
     */
    public function scanActivity(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->organizer) {
            return $this->forbidden('You must be an organizer to view scan activity');
        }

        $validated = $request->validate([
            'manager_id' => 'nullable|uuid|exists:organizer_managers,id',
            'event_id' => 'nullable|uuid|exists:events,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = DB::table('tickets')
            ->join('users', 'tickets.used_by', '=', 'users.id')
            ->join('events', 'tickets.event_id', '=', 'events.id')
            ->join('bookings', 'tickets.booking_id', '=', 'bookings.id')
            ->join('users as customers', 'bookings.user_id', '=', 'customers.id')
            ->where('events.organizer_id', $user->organizer->id)
            ->whereNotNull('tickets.used_at');

        if ($validated['manager_id'] ?? null) {
            $manager = OrganizerManager::find($validated['manager_id']);
            if ($manager && $manager->organizer_id === $user->organizer->id) {
                $query->where('tickets.used_by', $manager->user_id);
            }
        }

        if ($validated['event_id'] ?? null) {
            $query->where('tickets.event_id', $validated['event_id']);
        }

        if ($validated['date_from'] ?? null) {
            $query->where('tickets.used_at', '>=', $validated['date_from']);
        }

        if ($validated['date_to'] ?? null) {
            $query->where('tickets.used_at', '<=', $validated['date_to'] . ' 23:59:59');
        }

        $activity = $query->select([
                'tickets.id as ticket_id',
                'tickets.ticket_code',
                'tickets.ticket_type',
                'tickets.used_at',
                'tickets.entry_gate',
                'users.full_name as scanner_name',
                'users.id as scanner_id',
                'events.title as event_title',
                'events.id as event_id',
                'customers.full_name as customer_name',
                'customers.id as customer_id',
            ])
            ->orderBy('tickets.used_at', 'desc')
            ->paginate($request->per_page ?? 50);

        $activityData = collect($activity->items())->map(function ($scan) {
            return [
                'ticket' => [
                    'id' => $scan->ticket_id,
                    'code' => $scan->ticket_code,
                    'type' => $scan->ticket_type,
                ],
                'scanner' => [
                    'id' => $scan->scanner_id,
                    'name' => $scan->scanner_name,
                ],
                'event' => [
                    'id' => $scan->event_id,
                    'title' => $scan->event_title,
                ],
                'customer' => [
                    'id' => $scan->customer_id,
                    'name' => $scan->customer_name,
                ],
                'entry_gate' => $scan->entry_gate,
                'scanned_at' => $scan->used_at,
            ];
        });

        return $this->success([
            'activity' => $activityData,
            'meta' => [
                'current_page' => $activity->currentPage(),
                'last_page' => $activity->lastPage(),
                'per_page' => $activity->perPage(),
                'total' => $activity->total(),
            ],
        ]);
    }
}