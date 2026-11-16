<?php

namespace Tests\Feature;

use App\Models\CitizenProfile;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\SupabaseStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Covers staff ticket access, including signed participant photo URLs. */
class AdminTicketInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_conversation_renders_when_a_message_sender_was_deleted(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $citizen = User::factory()->create(['role' => 'citizen']);
        $sender = User::factory()->create(['role' => 'staff']);
        $serviceRequest = ServiceRequest::create([
            'user_id' => $citizen->id,
            'reference_number' => 'BRGY-20261021-0001',
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => $citizen->name,
            'address' => '1 Main Street',
            'age' => 30,
            'purpose' => 'Employment',
            'status' => ServiceRequest::PENDING,
        ]);
        $ticket = $serviceRequest->ticket()->create([
            'user_id' => $citizen->id,
        ]);
        $ticket->messages()->create([
            'user_id' => $sender->id,
            'body' => 'Your request is being reviewed.',
        ]);

        $sender->delete();

        $response = $this->actingAs($admin)
            ->get(route('admin.tickets.show', $ticket));

        $response->assertOk()
            ->assertSee('Your request is being reviewed.')
            ->assertSee('Former account');
    }

    public function test_ticket_search_query_is_ignored_and_client_search_keeps_the_pager(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (range('A', 'K') as $index => $letter) {
            $serviceRequest = ServiceRequest::factory()->create([
                'applicant_name' => 'Ticket Citizen '.$letter,
            ]);
            $ticket = $serviceRequest->ticket()->create(['user_id' => $serviceRequest->user_id]);
            $ticket->messages()->create([
                'user_id' => $serviceRequest->user_id,
                'body' => 'Needle support message',
            ]);
            $ticket->forceFill(['updated_at' => now()->subMinutes($index)])->save();
        }

        $otherRequest = ServiceRequest::factory()->create(['applicant_name' => 'Unrelated Citizen']);
        $otherTicket = $otherRequest->ticket()->create(['user_id' => $otherRequest->user_id]);
        $otherTicket->messages()->create([
            'user_id' => $otherRequest->user_id,
            'body' => 'An unrelated support message',
        ]);
        $otherTicket->forceFill(['updated_at' => now()->addMinute()])->save();

        $response = $this->actingAs($admin)->get(route('admin.tickets.index', [
            'search' => 'Needle',
            'per_page' => 10,
        ]));

        $response->assertOk()
            ->assertSee('Ticket Citizen A')
            ->assertSee('Unrelated Citizen')
            ->assertViewHas('tickets', fn ($tickets): bool => $tickets->total() === 12)
            ->assertSee('data-table-key="ticket-messages"', false)
            ->assertSee('placeholder="Search citizen, reference, or message"', false)
            ->assertSee('')
            ->assertSee('aria-label="Pagination"', false)
            ->assertDontSee('name="search"', false)
            ->assertDontSee('search=')
            ->assertSee('Next page', false);
    }

    public function test_ticket_participant_photo_redirects_to_a_temporary_supabase_url(): void
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
        ]);
        CitizenProfile::create([
            'user_id' => $citizen->id,
            'phone' => '09171234567',
            'date_of_birth' => '1990-01-01',
            'address' => '1 Main Street, San Luis',
            'barangay' => 'San Luis',
            'photo_path' => 'profile-photos/citizen.png',
        ]);
        $serviceRequest = ServiceRequest::create([
            'user_id' => $citizen->id,
            'reference_number' => 'BRGY-20261021-0002',
            'service_type' => ServiceRequest::BARANGAY_CLEARANCE,
            'applicant_name' => $citizen->name,
            'address' => '1 Main Street',
            'age' => 30,
            'purpose' => 'Employment',
            'status' => ServiceRequest::PENDING,
        ]);
        $ticket = $serviceRequest->ticket()->create([
            'user_id' => $citizen->id,
        ]);
        $this->mock(SupabaseStorage::class)
            ->shouldReceive('temporaryUrl')
            ->once()
            ->andReturn('https://storage.example/profile.jpg');

        $this->actingAs($citizen)
            ->get(route('tickets.participants.photo', [
                'ticket' => $ticket,
                'participant' => $citizen,
            ]))
            ->assertRedirect('https://storage.example/profile.jpg');
    }
}
