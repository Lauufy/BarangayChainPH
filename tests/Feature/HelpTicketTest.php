<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HelpTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_file_only_help_ticket_is_private_and_receives_replies(): void
    {
        Storage::fake('supabase');
        $owner = User::factory()->create(['role' => 'citizen']);
        $other = User::factory()->create(['role' => 'citizen']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($owner)->post(route('citizen.help.store'), [
            'attachment' => UploadedFile::fake()->createWithContent('details.txt', 'Test document'),
        ])->assertSessionHasNoErrors();

        $ticket = Ticket::sole();
        $message = $ticket->messages()->sole();
        $this->assertNull($ticket->service_request_id);
        Storage::disk('supabase')->assertExists($message->attachment_path);
        $this->get(route('citizen.help.index'))->assertOk()->assertSee('details.txt');
        $this->get($ticket->citizenUrl())->assertOk()->assertSee('Download details.txt');
        $attachment = route('tickets.attachment', $message);
        $this->get($attachment)->assertDownload('details.txt');

        $this->actingAs($other)->get($attachment)->assertNotFound();
        $this->get($ticket->citizenUrl())->assertNotFound();
        $this->post(route('citizen.help.reply', $ticket), ['body' => 'Hello'])->assertNotFound();

        $this->actingAs($admin)->get(route('admin.tickets.index'))->assertOk()->assertSee($ticket->reference());
        $this->get(route('admin.tickets.show', $ticket))->assertOk()->assertSee('Download details.txt');
        $this->post(route('admin.tickets.reply', $ticket), ['body' => 'Received'])->assertRedirect();
        $this->assertSame($ticket->citizenUrl(), $owner->notifications()->firstOrFail()->data['url']);
        $this->actingAs($owner)->get($ticket->citizenUrl())->assertOk()->assertSee('Received');
    }

    public function test_application_ticket_accepts_an_image_and_caption_only_while_open(): void
    {
        Storage::fake('supabase');
        $owner = User::factory()->create(['role' => 'citizen']);
        $application = ServiceRequest::factory()->create(['user_id' => $owner->id]);
        $ticket = $application->ticket()->create(['user_id' => $owner->id]);
        $url = route('citizen.tickets.reply', $application);

        $this->actingAs($owner)->post($url, [
            'body' => 'Supporting photo',
            'attachment' => UploadedFile::fake()->image('photo.png'),
        ])->assertRedirect(route('citizen.tickets.show', $application));

        $message = $ticket->messages()->sole();
        $this->assertSame('Supporting photo', $message->body);
        $this->get(route('tickets.attachment', $message))->assertDownload('photo.png');

        $ticket->update(['status' => Ticket::CLOSED]);
        $this->post($url, ['attachment' => UploadedFile::fake()->image('another.png')])->assertForbidden();
        $this->assertSame(1, $ticket->messages()->count());
    }

    public function test_invalid_submissions_leave_no_ticket(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'citizen']));
        $this->post(route('citizen.help.store'))->assertSessionHasErrors('body');
        $this->post(route('citizen.help.store'), [
            'attachment' => UploadedFile::fake()->createWithContent('sample.html', '<html></html>'),
        ])->assertSessionHasErrors('attachment');

        $this->assertDatabaseCount('tickets', 0);
    }
}
