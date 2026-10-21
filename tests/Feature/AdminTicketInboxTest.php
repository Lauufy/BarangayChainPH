<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
