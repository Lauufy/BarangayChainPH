<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitizenNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_marking_a_notification_as_read_redirects_to_its_destination(): void
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
        ]);
        $notification = $citizen->notifications()->create([
            'id' => 'a4d1ba9c-1a0b-4d44-a87b-100001234567',
            'type' => 'App\\Notifications\\ServiceRequestApproved',
            'data' => ['url' => 'https://barangaychain.test/citizen/applications'],
        ]);

        $response = $this->actingAs($citizen)
            ->post(route('citizen.notifications.read', $notification->id));

        $response->assertRedirect('/citizen/applications');
        $this->assertNotNull($notification->fresh()->read_at);
    }
}
