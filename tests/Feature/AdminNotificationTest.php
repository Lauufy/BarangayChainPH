<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_admin_dashboard_keeps_notifications_unread_without_individual_read_action(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $notification = $admin->notifications()->create([
            'id' => 'a4d1ba9c-1a0b-4d44-a87b-100001234572',
            'type' => 'App\\Notifications\\ServiceRequestApproved',
            'data' => [
                'title' => 'Application approved',
                'message' => 'Your application has been approved.',
                'url' => route('admin.dashboard'),
            ],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Application approved')
            ->assertSee('action="'.route('admin.notifications.read', $notification->id).'"', false)
            ->assertDontSee('Mark as read');
        $this->assertNull($notification->fresh()->read_at);

        $this->post(route('admin.notifications.read', $notification->id), ['open' => 1])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_explicit_admin_mark_as_read_action_only_reads_the_selected_notification(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $notification = $admin->notifications()->create([
            'id' => 'a4d1ba9c-1a0b-4d44-a87b-100001234573',
            'type' => 'App\\Notifications\\ServiceRequestApproved',
            'data' => [],
        ]);
        $otherNotification = $admin->notifications()->create([
            'id' => 'a4d1ba9c-1a0b-4d44-a87b-100001234574',
            'type' => 'App\\Notifications\\IdentityVerificationReviewed',
            'data' => [],
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.dashboard'))
            ->post(route('admin.notifications.read', $notification->id));

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertNull($otherNotification->fresh()->read_at);
    }
}
