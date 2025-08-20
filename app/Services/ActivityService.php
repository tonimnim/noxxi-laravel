<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityService
{
    /**
     * Log an activity
     */
    public static function log(
        string $type,
        string $action,
        string $title,
        ?string $description = null,
        ?Model $subject = null,
        ?Model $causer = null,
        array $properties = [],
        string $level = ActivityLog::LEVEL_INFO
    ): ActivityLog {
        $activity = ActivityLog::create([
            'type' => $type,
            'action' => $action,
            'level' => $level,
            'title' => $title,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'causer_type' => $causer ? get_class($causer) : (Auth::check() ? get_class(Auth::user()) : null),
            'causer_id' => $causer?->id ?? Auth::id(),
            'properties' => $properties,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);

        return $activity;
    }

    /**
     * Log a payment activity
     */
    public static function logPayment(string $action, string $title, $amount = null, array $properties = []): ActivityLog
    {
        $level = $action === ActivityLog::ACTION_FAILED ? ActivityLog::LEVEL_CRITICAL : ActivityLog::LEVEL_IMPORTANT;
        
        if ($amount) {
            $properties['amount'] = $amount;
        }

        return self::log(
            ActivityLog::TYPE_PAYMENT,
            $action,
            $title,
            null,
            null,
            null,
            $properties,
            $level
        );
    }

    /**
     * Log an organizer activity
     */
    public static function logOrganizer(string $action, $organizer, string $title): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_ORGANIZER,
            $action,
            $title,
            null,
            $organizer,
            null,
            ['business_name' => $organizer->business_name],
            ActivityLog::LEVEL_IMPORTANT
        );
    }

    /**
     * Log an event activity
     */
    public static function logEvent(string $action, $event, string $title): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_EVENT,
            $action,
            $title,
            null,
            $event,
            null,
            ['event_title' => $event->title],
            ActivityLog::LEVEL_INFO
        );
    }

    /**
     * Log a user activity
     */
    public static function logUser(string $action, $user, string $title): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_USER,
            $action,
            $title,
            null,
            $user,
            null,
            ['user_name' => $user->full_name ?? $user->email],
            ActivityLog::LEVEL_INFO
        );
    }

    /**
     * Log a system activity
     */
    public static function logSystem(string $action, string $title, array $properties = []): ActivityLog
    {
        return self::log(
            ActivityLog::TYPE_SYSTEM,
            $action,
            $title,
            null,
            null,
            null,
            $properties,
            ActivityLog::LEVEL_INFO
        );
    }
}