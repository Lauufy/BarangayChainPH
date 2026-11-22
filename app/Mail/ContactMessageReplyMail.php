<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ContactMessage $contactMessage,
        public string $staffName,
        public string $reply,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reply from Barangay San Luis',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact-message-reply',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
