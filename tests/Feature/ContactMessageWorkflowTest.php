<?php

namespace Tests\Feature;

use App\Mail\ContactMessageReplyMail;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class ContactMessageWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_submission_is_saved_for_the_website_inbox(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'permissions' => ['tickets.reply']]);
        $other = User::factory()->create(['role' => 'staff', 'permissions' => []]);

        $response = $this->followingRedirects()
            ->post(route('contact.store'), [
                'first_name' => 'Juan',
                'last_name' => 'Dela Cruz',
                'email' => 'juan@example.test',
                'service' => 'Barangay Clearance',
                'subject' => 'Question about requirements',
                'message' => 'Which documents should I prepare?',
            ]);

        $response->assertOk()
            ->assertSee('Thank you. Your message has been received, and we will reply by email.');

        $this->assertSame(route('admin.contact-messages.show', ContactMessage::sole()), $staff->unreadNotifications()->sole()->data['url']);
        $this->assertFalse($other->notifications()->exists());

        $this->assertDatabaseHas('contact_messages', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@example.test',
            'subject' => 'Question about requirements',
            'body' => 'Which documents should I prepare?',
            'status' => ContactMessage::NEW,
        ]);
    }

    public function test_invalid_contact_email_is_rejected_without_saving_a_message(): void
    {
        $response = $this->from(route('home'))
            ->post(route('contact.store'), [
                'first_name' => 'Juan',
                'last_name' => 'Dela Cruz',
                'email' => 'not-an-email',
                'service' => 'Barangay Clearance',
                'subject' => 'Question about requirements',
                'message' => 'Which documents should I prepare?',
            ]);

        $response->assertSessionHasErrors(['email' => 'The email field must be a valid email address.']);

        $this->assertDatabaseMissing('contact_messages', [
            'email' => 'not-an-email',
        ]);
    }

    public function test_guest_is_redirected_to_login_from_the_website_inbox(): void
    {
        $this->get(route('admin.contact-messages.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_view_a_contact_message_in_the_inbox(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $contactMessage = $this->createContactMessage();

        $this->actingAs($admin)
            ->get(route('admin.tickets.index', ['communications_tab' => 'inquiries']))
            ->assertOk()
            ->assertSee('Communications Management')
            ->assertSee($contactMessage->subject)
            ->assertSee($contactMessage->email)
            ->assertSee($contactMessage->body);
    }

    public function test_staff_with_ticket_reply_permission_can_email_a_reply_to_the_sender(): void
    {
        Mail::fake();
        $staff = User::factory()->create([
            'role' => 'staff',
            'permissions' => ['tickets.reply'],
        ]);
        $contactMessage = $this->createContactMessage();
        $replyBody = 'Please bring one valid ID to the barangay hall.';

        $response = $this->actingAs($staff)
            ->post(route('admin.contact-messages.reply', $contactMessage), [
                'body' => $replyBody,
            ]);

        $response->assertRedirect(route('admin.contact-messages.show', $contactMessage))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('contact_message_replies', [
            'contact_message_id' => $contactMessage->id,
            'user_id' => $staff->id,
            'body' => $replyBody,
        ]);
        $this->assertSame(ContactMessage::REPLIED, $contactMessage->fresh()->status);

        Mail::assertSent(ContactMessageReplyMail::class, function (ContactMessageReplyMail $mail) use ($contactMessage, $replyBody): bool {
            return $mail->hasTo($contactMessage->email) && $mail->reply === $replyBody;
        });
    }

    public function test_staff_without_ticket_reply_permission_cannot_open_contact_inbox(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'permissions' => [],
        ]);

        $this->actingAs($staff)
            ->get(route('admin.contact-messages.index'))
            ->assertForbidden();
    }

    public function test_failed_email_delivery_does_not_save_a_reply_or_mark_the_message_replied(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'permissions' => ['tickets.reply'],
        ]);
        $contactMessage = $this->createContactMessage();
        Mail::shouldReceive('to')
            ->once()
            ->with($contactMessage->email)
            ->andThrow(new TransportException('SMTP is unavailable.'));

        $response = $this->from(route('admin.contact-messages.show', $contactMessage))
            ->actingAs($staff)
            ->post(route('admin.contact-messages.reply', $contactMessage), [
                'body' => 'Please bring one valid ID.',
            ]);

        $response->assertRedirect(route('admin.contact-messages.show', $contactMessage))
            ->assertSessionHas(
                'error',
                'The reply could not be emailed, so it was not saved. Check the mail settings and try again.'
            );

        $this->assertDatabaseCount('contact_message_replies', 0);
        $this->assertSame(ContactMessage::NEW, $contactMessage->fresh()->status);
    }

    public function test_contact_message_is_escaped_in_the_admin_inbox(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $unsafeContent = '<script>alert(1)</script>';
        $contactMessage = $this->createContactMessage([
            'first_name' => $unsafeContent,
            'last_name' => $unsafeContent,
            'subject' => $unsafeContent,
            'body' => $unsafeContent,
        ]);
        $contactMessage->replies()->create([
            'body' => $unsafeContent,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.contact-messages.show', $contactMessage))
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee($unsafeContent, false);
    }

    public function test_reply_email_renders_user_content_as_escaped_html(): void
    {
        $unsafeFirstName = '<script>first()</script>';
        $unsafeSubject = '<script>subject()</script>';
        $unsafeReply = '<script>reply()</script>';
        $contactMessage = $this->createContactMessage([
            'subject' => $unsafeSubject,
            'first_name' => $unsafeFirstName,
        ]);
        $mail = new ContactMessageReplyMail($contactMessage, 'Barangay Staff', $unsafeReply);

        $mail->assertHasSubject('Reply from Barangay San Luis');
        $mail->assertSeeInHtml('Barangay Staff');
        $mail->assertSeeInHtml($unsafeFirstName);
        $mail->assertSeeInHtml($unsafeSubject);
        $mail->assertSeeInHtml($unsafeReply);
    }

    private function createContactMessage(array $attributes = []): ContactMessage
    {
        return ContactMessage::create(array_merge([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@example.test',
            'service' => 'Barangay Clearance',
            'subject' => 'Question about requirements',
            'body' => 'Which documents should I prepare?',
            'status' => ContactMessage::NEW,
        ], $attributes));
    }
}
