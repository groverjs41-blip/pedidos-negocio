<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPreference;
use App\Services\OperationalNotificationPreferenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserNotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    protected OperationalNotificationPreferenceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->service = app(OperationalNotificationPreferenceService::class);
    }

    /**
     * A: User A has ORDER_CREATED in_app ON, sound ON. User B has ORDER_CREATED OFF. Recipients: only A.
     */
    public function test_recipients_filtering_by_user_preferences(): void
    {
        $userA = User::factory()->create(['active' => true]);
        $userB = User::factory()->create(['active' => true]);

        $userA->operationalNotificationPreferences()->create([
            'event_type' => 'ORDER_CREATED',
            'in_app_enabled' => true,
            'sound_enabled' => true,
            'browser_enabled' => false,
        ]);

        $userB->operationalNotificationPreferences()->create([
            'event_type' => 'ORDER_CREATED',
            'in_app_enabled' => false,
            'sound_enabled' => false,
            'browser_enabled' => false,
        ]);

        $recipients = $this->service->getRecipients('ORDER_CREATED');

        $this->assertContains($userA->id, $recipients['targetUserIds']);
        $this->assertContains($userA->id, $recipients['soundUserIds']);
        $this->assertNotContains($userB->id, $recipients['targetUserIds']);
        $this->assertNotContains($userB->id, $recipients['soundUserIds']);
    }

    /**
     * B: User has READY in_app ON, sound OFF. targetUserIds contains user, soundUserIds does NOT contain user.
     */
    public function test_target_user_ids_contains_user_but_sound_user_ids_does_not_when_sound_off(): void
    {
        $user = User::factory()->create(['active' => true]);

        $user->operationalNotificationPreferences()->create([
            'event_type' => 'READY',
            'in_app_enabled' => true,
            'sound_enabled' => false,
            'browser_enabled' => false,
        ]);

        $recipients = $this->service->getRecipients('READY');

        $this->assertContains($user->id, $recipients['targetUserIds']);
        $this->assertNotContains($user->id, $recipients['soundUserIds']);
    }

    /**
     * C: User has READY in_app ON, sound ON. targetUserIds contains user, soundUserIds contains user.
     */
    public function test_both_target_and_sound_user_ids_contain_user_when_sound_on(): void
    {
        $user = User::factory()->create(['active' => true]);

        $user->operationalNotificationPreferences()->create([
            'event_type' => 'READY',
            'in_app_enabled' => true,
            'sound_enabled' => true,
            'browser_enabled' => false,
        ]);

        $recipients = $this->service->getRecipients('READY');

        $this->assertContains($user->id, $recipients['targetUserIds']);
        $this->assertContains($user->id, $recipients['soundUserIds']);
    }

    /**
     * D: Master user preference sound_enabled = false: even if event sound ON, soundUserIds does NOT contain user.
     */
    public function test_master_user_preference_sound_off_overrides_event_sound_on(): void
    {
        $user = User::factory()->create(['active' => true]);

        // Set master personal sound_enabled = false
        UserPreference::create([
            'user_id' => $user->id,
            'sound_enabled' => false,
            'browser_notifications_enabled' => true,
        ]);

        // Event sound_enabled = true
        $user->operationalNotificationPreferences()->create([
            'event_type' => 'READY',
            'in_app_enabled' => true,
            'sound_enabled' => true,
            'browser_enabled' => false,
        ]);

        $recipients = $this->service->getRecipients('READY');

        $this->assertContains($user->id, $recipients['targetUserIds']);
        $this->assertNotContains($user->id, $recipients['soundUserIds']);
    }
}
