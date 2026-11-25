<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Support\Facades\Notification as Notifications;
use Illuminate\Notifications\Notification;

class CitizenSubmissionReceived extends Notification
{
    public function __construct(
        public string $title,
        public string $message,
        public string $url,
    ) {}

    public function sendToTicketInbox(): void
    {
        $recipients = User::whereIn('role', ['admin', 'staff'])->get()
            ->filter(fn (User $user) => $user->hasPermission('tickets.reply'));

        Notifications::send($recipients, $this);
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'status' => 'info',
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
