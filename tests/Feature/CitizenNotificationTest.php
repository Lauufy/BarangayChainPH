<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitizenNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_page_displays_the_citizens_notifications(): void
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
        ]);
        $notification = $citizen->notifications()->create([
            'id' => 'a4d1ba9c-1a0b-4d44-a87b-100001234568',
            'type' => 'App\\Notifications\\ServiceRequestApproved',
            'data' => [
                'title' => 'Application approved',
                'message' => 'Your application has been approved.',
                'url' => route('citizen.documents'),
            ],
        ]);

        $response = $this->actingAs($citizen)
            ->get(route('citizen.notifications'));

        $response->assertOk()
            ->assertSee('Application approved')
            ->assertSee('Your application has been approved.')
            ->assertSee('action="'.route('citizen.notifications.read', $notification->id).'"', false)
            ->assertDontSee('Mark as read')
            ->assertDontSee('data-notification-read-form', false);

        $this->post(route('citizen.notifications.read', $notification->id), ['open' => 1])
            ->assertRedirect(route('citizen.documents'));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_marking_one_notification_as_read_does_not_read_other_notifications(): void
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
        ]);
        $notification = $citizen->notifications()->create([
            'id' => 'a4d1ba9c-1a0b-4d44-a87b-100001234567',
            'type' => 'App\\Notifications\\ServiceRequestApproved',
            'data' => ['url' => 'https://barangaychain.test/citizen/applications'],
        ]);
        $otherNotification = $citizen->notifications()->create([
            'id' => 'a4d1ba9c-1a0b-4d44-a87b-100001234569',
            'type' => 'App\\Notifications\\IdentityVerificationReviewed',
            'data' => ['url' => route('citizen.identity-verification')],
        ]);

        $response = $this->actingAs($citizen)
            ->from(route('citizen.notifications'))
            ->post(route('citizen.notifications.read', $notification->id));

        $response->assertRedirect(route('citizen.notifications'));
        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertNull($otherNotification->fresh()->read_at);
    }

    public function test_marking_a_notification_as_read_with_json_returns_the_remaining_unread_count(): void
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
        ]);
        $notification = $citizen->notifications()->create([
            'id' => 'a4d1ba9c-1a0b-4d44-a87b-100001234570',
            'type' => 'App\\Notifications\\ServiceRequestApproved',
            'data' => [],
        ]);
        $citizen->notifications()->create([
            'id' => 'a4d1ba9c-1a0b-4d44-a87b-100001234571',
            'type' => 'App\\Notifications\\IdentityVerificationReviewed',
            'data' => [],
        ]);

        $response = $this->actingAs($citizen)
            ->postJson(route('citizen.notifications.read', $notification->id));

        $response->assertOk()
            ->assertJsonPath('unreadCount', 1);
    }
}
