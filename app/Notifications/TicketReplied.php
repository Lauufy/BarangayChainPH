<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Notifications\Notification;

class TicketReplied extends Notification
{
    public function __construct(public Ticket $ticket)
    {
    }

    // Saved in the portal (bell and notifications page)
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'status' => 'info',
            'title' => 'New message from the barangay',
            'message' => 'The barangay replied to your request '.$this->ticket->serviceRequest->reference_number.'.',
            'url' => route('citizen.tickets.show', $this->ticket->serviceRequest),
        ];
    }
}