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

    public function test_citizen_can_browse_and_search_help_articles(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'citizen']));

        $article = route('citizen.help.article', 'how-to-track-your-permit-request');

        $index = $this->get(route('citizen.help.index'))->assertOk()->assertSee('Need some help?')
            ->assertSee('Quick links')->assertSee('How to track your permit request')->assertDontSee($article);
        foreach (['permits-clearances' => 12, 'certificates' => 8, 'verification-qr' => 6, 'account-identity' => 9] as $slug => $count) {
            $category = route('citizen.help.category', $slug);
            $index->assertSee('href="'.$category.'"', false);
            $this->get($category)->assertOk()->assertViewIs('citizen.help.category')
                ->assertViewHas('articles', fn ($articles) => $articles->count() === $count
                    && $articles->every(fn ($guide) => $guide->category->slug === $slug));
        }
        $this->get(route('citizen.help.index', ['q' => 'TRACK YOUR PERMIT']))
            ->assertOk()->assertSee('How to track your permit request')->assertSee('Pending')->assertDontSee($article)->assertSee('value="TRACK YOUR PERMIT"', false);
        $this->get($article)->assertOk()->assertSee('How to track your permit request');
    }

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
        $this->assertSame(route('admin.tickets.show', $ticket), $admin->unreadNotifications()->sole()->data['url']);
        $message = $ticket->messages()->where('is_system', false)->sole();
        $this->assertNull($ticket->service_request_id);
        Storage::disk('supabase')->assertExists($message->attachment_path);
        $this->get(route('citizen.help.tickets'))->assertOk()->assertSee($ticket->reference());
        $this->get($ticket->citizenUrl())->assertOk()->assertSee('Download details.txt');
        $attachment = route('tickets.attachment', $message);
        $this->get($attachment)->assertDownload('details.txt');

        $this->actingAs($other)->get($attachment)->assertNotFound();
        $this->get($ticket->citizenUrl())->assertNotFound();
        $this->post(route('citizen.help.reply', $ticket), ['body' => 'Hello'])->assertNotFound();

        $this->actingAs($admin)->get(route('admin.contact-messages.index'))->assertOk()->assertSee($ticket->reference());
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

        $message = $ticket->messages()->where('is_system', false)->sole();
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

    public function test_ticket_pages_accept_an_image_and_keep_the_preview_private(): void
    {
        Storage::fake('supabase');
        $owner = User::factory()->create(['role' => 'citizen']);
        $this->actingAs($owner)->get(route('citizen.help.index'))->assertOk()
            ->assertSee(route('citizen.help.create'))->assertSee(route('citizen.help.tickets'));
        $this->get(route('citizen.help.create'))->assertOk()->assertSee('Submit a Help Request')
            ->assertSee($owner->email)->assertSee('Select a reason...');
        $response = $this->post(route('citizen.help.store'), [
            'first_name' => 'Maria',
            'last_name' => 'Dela Cruz',
            'email' => $owner->email,
            'service' => 'Document Verification',
            'body' => 'Please check my permit',
            'attachment' => UploadedFile::fake()->image('photo.png'),
        ], ['Accept' => 'application/json']);
        $ticket = Ticket::sole();
        $response->assertOk()->assertJsonPath('url', $ticket->citizenUrl());
        $preview = route('tickets.attachment', ['message' => $ticket->messages()->where('is_system', false)->sole(), 'preview' => 1]);
        $this->get($ticket->citizenUrl())->assertOk()->assertSee('src="'.e($preview).'"', false)
            ->assertSeeInOrder(['Type of reason: Document Verification', 'Purpose: Please check my permit', 'A reply may take some time.']);
        $this->assertSame("Type of reason: Document Verification\n\nPurpose: Please check my permit", $ticket->messages()->where('is_system', false)->sole()->body);
        $this->post(route('citizen.help.reply', $ticket), ['body' => 'Thank you'])->assertRedirect();
        $this->assertSame(1, $ticket->messages()->where('is_system', true)->count());
        $this->get($preview)->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->actingAs(User::factory()->create(['role' => 'citizen']))->get($preview)->assertNotFound();
    }
}
