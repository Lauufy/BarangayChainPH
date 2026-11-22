<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TicketMessageEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_message_body_is_encrypted_in_storage_and_readable_in_the_inbox(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $serviceRequest = ServiceRequest::factory()->create();
        $ticket = $serviceRequest->ticket()->create([
            'user_id' => $serviceRequest->user_id,
        ]);
        $plainBody = 'Private details shared in this ticket conversation.';
        $message = $ticket->messages()->create([
            'user_id' => $serviceRequest->user_id,
            'body' => $plainBody,
        ]);
        $storedBody = DB::table('ticket_messages')
            ->where('id', $message->id)
            ->value('body');

        $this->assertNotSame($plainBody, $storedBody);
        $this->assertSame($plainBody, $message->fresh()->body);

        $this->actingAs($admin)
            ->get(route('admin.tickets.show', $ticket))
            ->assertOk()
            ->assertSee($plainBody);
    }

    public function test_migration_encrypts_legacy_bodies_without_encrypting_existing_ciphertext_twice(): void
    {
        $serviceRequest = ServiceRequest::factory()->create();
        $ticket = $serviceRequest->ticket()->create([
            'user_id' => $serviceRequest->user_id,
        ]);
        $legacyBody = 'Legacy conversation that needs encryption.';
        $alreadyEncryptedBody = Crypt::encryptString('Already encrypted conversation.');
        $timestamp = now();
        $legacyId = DB::table('ticket_messages')->insertGetId([
            'ticket_id' => $ticket->id,
            'user_id' => $serviceRequest->user_id,
            'body' => $legacyBody,
            'is_system' => false,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $encryptedId = DB::table('ticket_messages')->insertGetId([
            'ticket_id' => $ticket->id,
            'user_id' => $serviceRequest->user_id,
            'body' => $alreadyEncryptedBody,
            'is_system' => false,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $migration = require base_path('database/migrations/2026_11_22_182942_encrypt_ticket_message_bodies.php');
        $migration->up();

        $storedLegacyBody = DB::table('ticket_messages')->where('id', $legacyId)->value('body');
        $storedEncryptedBody = DB::table('ticket_messages')->where('id', $encryptedId)->value('body');

        $this->assertNotSame($legacyBody, $storedLegacyBody);
        $this->assertSame($legacyBody, Crypt::decryptString($storedLegacyBody));
        $this->assertSame($alreadyEncryptedBody, $storedEncryptedBody);
        $this->assertSame($legacyBody, TicketMessage::findOrFail($legacyId)->body);
    }
}
