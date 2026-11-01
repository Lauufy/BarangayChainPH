<?php

namespace App\Notifications;

use App\Models\IdentityVerification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IdentityVerificationReviewed extends Notification
{
    public function __construct(public IdentityVerification $verification)
    {
    }

    // Saved in the portal (bell) and sent by email
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    // The bell / notifications page text (also reused by the email)
    public function toArray(object $notifiable): array
    {
        $approved = $this->verification->isVerified();

        return [
            'status' => $approved ? 'verified' : 'rejected',
            'title' => $approved ? 'Identity verified' : 'Identity verification rejected',
            'message' => $approved
                ? 'Your identity was verified. You can now request barangay services.'
                : 'Reason: '.$this->verification->rejection_reason,
            'url' => route('citizen.identity-verification'),
        ];
    }

    // The email
    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject($data['title'])
            ->greeting('Hello '.$notifiable->name.',')
            ->line($data['message'])
            ->action('Open citizen portal', $data['url']);
    }
}