<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    use ApiResponse;

    /**
     * Get user settings
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Extract settings from user model
        $settings = [
            'notifications' => [
                'email' => $user->notification_preferences['email'] ?? true,
                'sms' => $user->notification_preferences['sms'] ?? false,
                'push' => $user->notification_preferences['push'] ?? true,
                'event_reminders' => $user->notification_preferences['event_reminders'] ?? true,
                'booking_updates' => $user->notification_preferences['booking_updates'] ?? true,
                'promotional' => $user->notification_preferences['promotional'] ?? false,
            ],
            'preferences' => [
                'language' => $user->metadata['language'] ?? 'en',
                'currency' => $user->metadata['currency'] ?? 'KES',
                'timezone' => $user->metadata['timezone'] ?? 'Africa/Nairobi',
                'date_format' => $user->metadata['date_format'] ?? 'DD/MM/YYYY',
                'time_format' => $user->metadata['time_format'] ?? '24h',
            ],
            'privacy' => [
                'profile_visibility' => $user->metadata['profile_visibility'] ?? 'public',
                'show_attendance' => $user->metadata['show_attendance'] ?? true,
                'allow_messages' => $user->metadata['allow_messages'] ?? true,
                'data_sharing' => $user->metadata['data_sharing'] ?? false,
            ],
            'security' => [
                'two_factor_enabled' => $user->metadata['two_factor_enabled'] ?? false,
                'login_alerts' => $user->metadata['login_alerts'] ?? true,
                'session_timeout' => $user->metadata['session_timeout'] ?? 30, // minutes
            ],
        ];
        
        return $this->success($settings);
    }

    /**
     * Update user settings
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            // Notification settings
            'notifications' => 'sometimes|array',
            'notifications.email' => 'boolean',
            'notifications.sms' => 'boolean',
            'notifications.push' => 'boolean',
            'notifications.event_reminders' => 'boolean',
            'notifications.booking_updates' => 'boolean',
            'notifications.promotional' => 'boolean',
            
            // App preferences
            'preferences' => 'sometimes|array',
            'preferences.language' => 'string|in:en,sw,fr,ar',
            'preferences.currency' => 'string|in:KES,NGN,ZAR,GHS,UGX,TZS,EGP,USD',
            'preferences.timezone' => 'string|timezone',
            'preferences.date_format' => 'string|in:DD/MM/YYYY,MM/DD/YYYY,YYYY-MM-DD',
            'preferences.time_format' => 'string|in:12h,24h',
            
            // Privacy settings
            'privacy' => 'sometimes|array',
            'privacy.profile_visibility' => 'string|in:public,private,friends',
            'privacy.show_attendance' => 'boolean',
            'privacy.allow_messages' => 'boolean',
            'privacy.data_sharing' => 'boolean',
            
            // Security settings
            'security' => 'sometimes|array',
            'security.two_factor_enabled' => 'boolean',
            'security.login_alerts' => 'boolean',
            'security.session_timeout' => 'integer|min:5|max:1440', // 5 min to 24 hours
        ]);
        
        $user = $request->user();
        
        // Update notification preferences
        if (isset($validated['notifications'])) {
            $notificationPrefs = $user->notification_preferences ?? [];
            foreach ($validated['notifications'] as $key => $value) {
                $notificationPrefs[$key] = $value;
            }
            $user->notification_preferences = $notificationPrefs;
        }
        
        // Update other settings in metadata
        $metadata = $user->metadata ?? [];
        
        if (isset($validated['preferences'])) {
            foreach ($validated['preferences'] as $key => $value) {
                $metadata[$key] = $value;
            }
        }
        
        if (isset($validated['privacy'])) {
            foreach ($validated['privacy'] as $key => $value) {
                $metadata[$key] = $value;
            }
        }
        
        if (isset($validated['security'])) {
            foreach ($validated['security'] as $key => $value) {
                $metadata[$key] = $value;
            }
        }
        
        $user->metadata = $metadata;
        $user->save();
        
        return $this->success([
            'message' => 'Settings updated successfully',
            'settings' => $this->index($request)->getData()->data,
        ]);
    }

    /**
     * Reset settings to default
     */
    public function reset(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|in:notifications,preferences,privacy,security,all',
        ]);
        
        $user = $request->user();
        
        if ($validated['category'] === 'all' || $validated['category'] === 'notifications') {
            $user->notification_preferences = [
                'email' => true,
                'sms' => false,
                'push' => true,
                'event_reminders' => true,
                'booking_updates' => true,
                'promotional' => false,
            ];
        }
        
        $metadata = $user->metadata ?? [];
        
        if ($validated['category'] === 'all' || $validated['category'] === 'preferences') {
            $metadata['language'] = 'en';
            $metadata['currency'] = 'KES';
            $metadata['timezone'] = 'Africa/Nairobi';
            $metadata['date_format'] = 'DD/MM/YYYY';
            $metadata['time_format'] = '24h';
        }
        
        if ($validated['category'] === 'all' || $validated['category'] === 'privacy') {
            $metadata['profile_visibility'] = 'public';
            $metadata['show_attendance'] = true;
            $metadata['allow_messages'] = true;
            $metadata['data_sharing'] = false;
        }
        
        if ($validated['category'] === 'all' || $validated['category'] === 'security') {
            $metadata['two_factor_enabled'] = false;
            $metadata['login_alerts'] = true;
            $metadata['session_timeout'] = 30;
        }
        
        $user->metadata = $metadata;
        $user->save();
        
        return $this->success([
            'message' => 'Settings reset to default',
            'settings' => $this->index($request)->getData()->data,
        ]);
    }
}