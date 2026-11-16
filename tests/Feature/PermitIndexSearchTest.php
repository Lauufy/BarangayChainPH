<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermitIndexSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_permit_search_query_is_ignored_and_client_search_keeps_the_pager(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (range('A', 'K') as $index => $letter) {
            ServiceRequest::factory()->create([
                'applicant_name' => 'Needle Applicant '.$letter,
                'created_at' => now()->subMinutes($index),
                'updated_at' => now()->subMinutes($index),
            ]);
        }

        ServiceRequest::factory()->create(['applicant_name' => 'Unrelated Applicant']);

        $response = $this->actingAs($admin)->get(route('admin.permits.index', [
            'search' => 'Needle',
            'per_page' => 10,
        ]));

        $response->assertOk()
            ->assertSee('Needle Applicant A')
            ->assertSee('Unrelated Applicant')
            ->assertViewHas('requests', fn ($requests): bool => $requests->total() === 12)
            ->assertSee('data-table-key="permits"', false)
            ->assertSee('placeholder="Search applicant, reference, type, or status"', false)
            ->assertSee('Searching this page only.')
            ->assertSee('aria-label="Pagination"', false)
            ->assertDontSee('name="search"', false)
            ->assertDontSee('search=')
            ->assertSee('Next page', false);
    }
}
