<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_account_cannot_authenticate_through_google(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'is_active' => false,
        ]);
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-user-id',
            'email' => $user->email,
        ]));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
        $this->assertNull($user->fresh()->google_id);
    }

    public function test_active_account_can_authenticate_through_google(): void
    {
        $user = User::factory()->create([
            'email' => 'active@example.com',
            'is_active' => true,
        ]);
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-user-id',
            'email' => $user->email,
        ]));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('citizen.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-user-id', $user->fresh()->google_id);
    }
}
