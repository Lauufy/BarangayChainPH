<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffIdentityReviewAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_access_identity_review_routes(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
        ]);

        $responses = [
            $this->actingAs($staff)->get('/staff/identity-verification'),
            $this->get('/staff/identity-verification/1'),
            $this->get('/staff/identity-verification/1/document'),
            $this->get('/staff/identity-verification/1/selfie'),
            $this->post('/staff/identity-verification/1/approve'),
            $this->post('/staff/identity-verification/1/reject'),
        ];

        foreach ($responses as $response) {
            $response->assertNotFound();
        }
    }

    public function test_admin_can_still_access_identity_review(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.identity-verification.index'))
            ->assertOk();
    }
}
