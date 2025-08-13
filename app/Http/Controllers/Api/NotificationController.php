<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 15);
        
        $notifications = $user->notifications()
            ->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $notifications,
            'unread_count' => $this->notificationService->getUnreadCount($user),
        ]);
    }

    /**
     * Get unread notifications for the authenticated user.
     */
    public function unread(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $notifications = $user->unreadNotifications()
            ->latest()
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $notifications,
            'count' => $notifications->count(),
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $success = $this->notificationService->markAsRead($user, $id);
        
        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found or already read',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
            'unread_count' => $this->notificationService->getUnreadCount($user),
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = $this->notificationService->markAllAsRead($user);
        
        return response()->json([
            'success' => true,
            'message' => "Marked {$count} notifications as read",
            'unread_count' => 0,
        ]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $notification = $user->notifications()->find($id);
        
        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }
        
        $notification->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Notification deleted',
            'unread_count' => $this->notificationService->getUnreadCount($user),
        ]);
    }

    /**
     * Get notification preferences.
     */
    public function preferences(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'success' => true,
            'data' => [
                'email_notifications' => $user->notification_preferences['email'] ?? true,
                'push_notifications' => $user->notification_preferences['push'] ?? true,
                'sms_notifications' => $user->notification_preferences['sms'] ?? false,
                'event_reminders' => $user->notification_preferences['event_reminders'] ?? true,
                'booking_updates' => $user->notification_preferences['booking_updates'] ?? true,
                'promotional' => $user->notification_preferences['promotional'] ?? false,
            ],
        ]);
    }

    /**
     * Update notification preferences.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'event_reminders' => 'boolean',
            'booking_updates' => 'boolean',
            'promotional' => 'boolean',
        ]);
        
        $user = $request->user();
        $preferences = $user->notification_preferences ?? [];
        
        foreach ($validated as $key => $value) {
            $preferences[str_replace('_notifications', '', $key)] = $value;
        }
        
        $user->update(['notification_preferences' => $preferences]);
        
        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated',
            'data' => $preferences,
        ]);
    }
}