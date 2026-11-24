<?php

namespace Tests\Feature;

use App\Models\IdentityVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffIdentityReviewAccessTest extends TestCase
{
    use RefreshDatabase;

    private function staff(array $permissions): User
    {
        return User::factory()->create([
            'role' => 'staff',
            'permissions' => $permissions,
        ]);
    }

    public function test_staff_with_permission_can_open_identity_review(): void
    {
        $this->actingAs($this->staff(['identity.review']))
            ->get(route('admin.identity-verification.index'))
            ->assertOk();
    }

    public function test_staff_without_permission_cannot_open_identity_review(): void
    {
        $this->actingAs($this->staff([]))
            ->get(route('admin.identity-verification.index'))
            ->assertForbidden();
    }

    public function test_admin_can_still_access_identity_review(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.identity-verification.index'))
            ->assertOk();
    }

    public function test_identity_review_shows_the_ocr_flags_panel(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $verification = IdentityVerification::factory()->create([
            'document_back_key' => 'identity/test-back.jpg',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.identity-verification.show', $verification))
            ->assertOk()
            ->assertSee('Automated Document', false)
            ->assertSeeText('Not processed yet')
            ->assertSee('Back of ID')
            ->assertSee(route('admin.identity-verification.document-back', $verification));
    }

    public function test_staff_cannot_view_or_open_files_for_an_unsubmitted_draft(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $draft = IdentityVerification::factory()->create([
            'status' => IdentityVerification::DRAFT,
            'selfie_path' => 'identity-selfies/draft/selfie.jpg',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.identity-verification.show', $draft))
            ->assertNotFound();

        $this->get(route('admin.identity-verification.document', $draft))
            ->assertNotFound();

        $this->get(route('admin.identity-verification.selfie', $draft))
            ->assertNotFound();

        $this->get(route('admin.identity-verification.document-back', $draft))
            ->assertNotFound();
    }

    public function test_identity_search_query_is_ignored_and_client_search_keeps_the_pager(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (range('A', 'K') as $index => $letter) {
            $user = User::factory()->create([
                'role' => 'citizen',
                'name' => 'Needle Citizen '.$letter,
            ]);

            IdentityVerification::factory()->create([
                'user_id' => $user->id,
                'submitted_at' => now()->subMinutes($index),
            ]);
        }

        $otherUser = User::factory()->create(['role' => 'citizen', 'name' => 'Unrelated Citizen']);
        IdentityVerification::factory()->create(['user_id' => $otherUser->id]);
        $draftUser = User::factory()->create(['role' => 'citizen', 'name' => 'Unsubmitted Draft Citizen']);
        IdentityVerification::factory()->create([
            'user_id' => $draftUser->id,
            'status' => IdentityVerification::DRAFT,
            'submitted_at' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.identity-verification.index', [
            'search' => 'Needle',
            'per_page' => 10,
        ]));

        $response->assertOk()
            ->assertSee('Needle Citizen A')
            ->assertSee('Unrelated Citizen')
            ->assertDontSee('Unsubmitted Draft Citizen')
            ->assertViewHas('verifications', fn ($verifications): bool => $verifications->total() === 12)
            ->assertSee('data-table-key="identity-verifications"', false)
            ->assertSee('placeholder="Search citizen, email, or status"', false)
            ->assertSee('aria-label="Pagination"', false)
            ->assertDontSee('name="search"', false)
            ->assertDontSee('search=')
            ->assertSee('Next page', false);
    }
}
