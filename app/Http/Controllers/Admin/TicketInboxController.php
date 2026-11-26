<?php

namespace App\Http\Controllers\Admin;

use App\Exports\TicketsExport;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Ticket;
use App\Services\PostTicketMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketInboxController extends Controller
{
    public function __construct(private PostTicketMessage $postMessage) {}

    public function index(Request $request, TicketsExport $export): View
    {
        $perPage = $request->integer('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50], true) ? $perPage : 15;
        $tickets = $export->query($request)->paginate($perPage)->appends($request->except('search'));
        $contactMessages = ContactMessage::query()
            ->withCount('replies')
            ->latest('updated_at')
            ->paginate(15, ['*'], 'inquiries_page');

        return view('admin.tickets.index', compact('tickets', 'contactMessages'));
    }

    public function show(Ticket $ticket): View
    {
        return view('admin.tickets.show', [
            'ticket' => $ticket->load(['serviceRequest', 'user']),
            'messages' => $ticket->messages()->with('user')->oldest()->orderBy('id')->get(),
            'tickets' => Ticket::with(['serviceRequest', 'user', 'latestMessage'])
                ->withMax('messages', 'created_at')
                ->orderByDesc('messages_max_created_at')
                ->orderByDesc('id')
                ->limit(50)
                ->get(),
        ]);
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse|JsonResponse
    {
        abort_unless($ticket->status === Ticket::OPEN, 403, 'This ticket is closed.');

        $this->postMessage->handle($request, $ticket);

        return $request->expectsJson()
            ? response()->json(['url' => route('admin.tickets.show', $ticket)])
            : redirect()->route('admin.tickets.show', $ticket);
    }
}
