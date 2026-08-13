<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserOperationalNotificationPreference;

class OperationalNotificationPreferenceService
{
    public const ALLOWED_EVENTS = [
        'ORDER_CREATED',
        'READY',
        'DELIVERED',
        'CANCELLED',
    ];

    /**
     * Get preference model for a specific user and event type.
     */
    public function getPreference(User $user, string $eventType): ?UserOperationalNotificationPreference
    {
        return $user->operationalNotificationPreferences()
            ->where('event_type', $eventType)
            ->first();
    }

    /**
     * Check if user should receive in-app toast / notification for event.
     */
    public function shouldReceiveInApp(User $user, string $eventType): bool
    {
        if (!$user->isActive()) {
            return false;
        }

        $pref = $this->getPreference($user, $eventType);
        if ($pref) {
            return (bool) $pref->in_app_enabled;
        }

        $defaults = $this->getDefaultsForUserAndEvent($user, $eventType);
        return $defaults['in_app'];
    }

    /**
     * Check if user should play sound for event.
     */
    public function shouldPlaySound(User $user, string $eventType): bool
    {
        // Rule: If in_app_enabled is false, sound is considered false
        if (!$this->shouldReceiveInApp($user, $eventType)) {
            return false;
        }

        // Check user master personal preference (UserPreference)
        $userPref = $user->preference;
        if ($userPref && $userPref->sound_enabled === false) {
            return false;
        }

        $pref = $this->getPreference($user, $eventType);
        if ($pref) {
            return (bool) $pref->sound_enabled;
        }

        $defaults = $this->getDefaultsForUserAndEvent($user, $eventType);
        return $defaults['sound'];
    }

    /**
     * Check if user should send browser notification for event.
     */
    public function shouldSendBrowser(User $user, string $eventType): bool
    {
        // Rule: If in_app_enabled is false, browser notification is considered false
        if (!$this->shouldReceiveInApp($user, $eventType)) {
            return false;
        }

        // Check user master personal preference (UserPreference)
        $userPref = $user->preference;
        if ($userPref && $userPref->browser_notifications_enabled === false) {
            return false;
        }

        $pref = $this->getPreference($user, $eventType);
        if ($pref) {
            return (bool) $pref->browser_enabled;
        }

        $defaults = $this->getDefaultsForUserAndEvent($user, $eventType);
        return $defaults['browser'];
    }

    /**
     * Get target, sound, and browser recipient user IDs for an event type.
     */
    public function getRecipients(string $eventType): array
    {
        $activeUsers = User::where('active', true)
            ->with(['operationalNotificationPreferences', 'preference', 'roles'])
            ->get();

        $targetUserIds = [];
        $soundUserIds = [];
        $browserUserIds = [];

        foreach ($activeUsers as $user) {
            if ($this->shouldReceiveInApp($user, $eventType)) {
                $targetUserIds[] = $user->id;
            }
            if ($this->shouldPlaySound($user, $eventType)) {
                $soundUserIds[] = $user->id;
            }
            if ($this->shouldSendBrowser($user, $eventType)) {
                $browserUserIds[] = $user->id;
            }
        }

        return [
            'targetUserIds' => array_values(array_unique($targetUserIds)),
            'soundUserIds' => array_values(array_unique($soundUserIds)),
            'browserUserIds' => array_values(array_unique($browserUserIds)),
        ];
    }

    /**
     * Ensure default operational notification preferences exist for a user based on roles.
     */
    public function ensureDefaultPreferences(User $user): void
    {
        $eventDefaults = [
            'ORDER_CREATED' => ['admin', 'cocina'],
            'READY' => ['admin', 'reparto', 'pedidos', 'caja'],
            'DELIVERED' => ['admin', 'caja'],
            'CANCELLED' => ['admin', 'cocina', 'reparto'],
        ];

        $userRoles = $user->roles()->pluck('slug')->toArray();

        foreach ($eventDefaults as $eventType => $targetRoles) {
            $exists = $user->operationalNotificationPreferences()
                ->where('event_type', $eventType)
                ->exists();

            if (!$exists) {
                $isDefaultTarget = !empty(array_intersect($userRoles, $targetRoles));
                $inApp = $isDefaultTarget;
                $sound = ($eventType === 'ORDER_CREATED' || $eventType === 'READY') ? $isDefaultTarget : false;

                $user->operationalNotificationPreferences()->create([
                    'event_type' => $eventType,
                    'in_app_enabled' => $inApp,
                    'sound_enabled' => $sound,
                    'browser_enabled' => false,
                ]);
            }
        }
    }

    /**
     * Get default settings for user and event based on user roles when preference row doesn't exist yet.
     */
    private function getDefaultsForUserAndEvent(User $user, string $eventType): array
    {
        $eventDefaults = [
            'ORDER_CREATED' => ['admin', 'cocina'],
            'READY' => ['admin', 'reparto', 'pedidos', 'caja'],
            'DELIVERED' => ['admin', 'caja'],
            'CANCELLED' => ['admin', 'cocina', 'reparto'],
        ];

        $targetRoles = $eventDefaults[$eventType] ?? [];
        $userRoles = $user->relationLoaded('roles')
            ? $user->roles->pluck('slug')->toArray()
            : $user->roles()->pluck('slug')->toArray();

        $isDefaultTarget = !empty(array_intersect($userRoles, $targetRoles));
        $inApp = $isDefaultTarget;
        $sound = ($eventType === 'ORDER_CREATED' || $eventType === 'READY') ? $isDefaultTarget : false;

        return [
            'in_app' => $inApp,
            'sound' => $sound,
            'browser' => false,
        ];
    }
}
