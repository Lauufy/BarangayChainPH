<?php

namespace Tests\Feature;

use App\Models\CitizenProfile;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\SupabaseStorage;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTicketInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_conversation_renders_when_a_message_sender_was_deleted(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
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

    public function test_ticket_participant_photo_redirects_to_a_temporary_supabase_url(): void
    {
        $citizen = User::factory()->create([
            'role' => 'citizen',
            'is_active' => true,
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
        Storage::fake('supabase');
        $this->mock(SupabaseStorage::class)
            ->shouldReceive('temporaryUrl')
            ->andReturnUsing(
                fn (string $path, DateTimeInterface $expiration): string => 'https://storage.example.test/'.$path
            );

        $this->actingAs($citizen)
            ->get(route('tickets.participants.photo', [
                'ticket' => $ticket,
                'participant' => $citizen,
            ]))
            ->assertRedirect('https://storage.example.test/profile-photos/citizen.png');
    }
}
