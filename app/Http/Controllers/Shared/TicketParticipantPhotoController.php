<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use App\Services\SupabaseStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TicketParticipantPhotoController extends Controller
{
    public function __construct(private SupabaseStorage $supabaseStorage) {}

    public function show(Request $request, Ticket $ticket, User $participant): RedirectResponse
    {
        $viewer = $request->user();

        // Staff need the ticket permission (admins always pass).
        abort_unless(! $viewer->isStaff() || $viewer->hasPermission('tickets.reply'), 403);

        // A citizen can only see photos inside their own ticket.
        if ($viewer->isCitizen()) {
            abort_unless($ticket->user_id === $viewer->id, 404);
        }

        // The photo owner must be the ticket owner or someone who wrote in this ticket.
        abort_unless(
            $ticket->user_id === $participant->id
                || $ticket->messages()->where('user_id', $participant->id)->exists(),
            404
        );

        $participant->loadMissing(['citizenProfile', 'employeeProfile']);

        $profile = $participant->isCitizen()
            ? $participant->citizenProfile
            : $participant->employeeProfile;

        $path = $profile?->photo_path;

        abort_unless($path, 404);

        return redirect()->away(
            $this->supabaseStorage->temporaryUrl($path, now()->addMinutes(5)),
            302,
            ['Cache-Control' => 'private, max-age=60']
        );
    }
}
