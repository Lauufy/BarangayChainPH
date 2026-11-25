<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\Ticket;
use App\Services\PostTicketMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function __construct(private PostTicketMessage $postMessage) {}

    public function index(Request $request): View
    {
        $tickets = Ticket::where('user_id', $request->user()->id)
            ->whereNull('service_request_id')->with('latestMessage')->latest('updated_at')->paginate(15);

        return view('citizen.tickets.help', compact('tickets'));
    }

    public function store(Request $request): RedirectResponse
    {
        $ticket = new Ticket(['user_id' => $request->user()->id, 'status' => Ticket::OPEN]);
        $this->postMessage->handle($request, $ticket);

        return redirect()->to($ticket->citizenUrl());
    }

    public function showHelp(Request $request, Ticket $ticket): View
    {
        $this->authorizeHelpTicket($request, $ticket);

        return $this->conversation($ticket);
    }

    public function replyHelp(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeHelpTicket($request, $ticket);

        return $this->sendReply($request, $ticket);
    }

    private function authorizeHelpTicket(Request $request, Ticket $ticket): void
    {
        abort_unless($ticket->user_id === $request->user()->id && $ticket->service_request_id === null, 404);
    }

    public function show(Request $request, ServiceRequest $serviceRequest): View
    {
        $ticket = $this->ownedTicket($request, $serviceRequest);

        return $this->conversation($ticket);
    }

    private function conversation(Ticket $ticket): View
    {
        return view('citizen.tickets.show', [
            'application' => $ticket->serviceRequest,
            'ticket' => $ticket,
            'messages' => $ticket->messages()
                ->with(['user.citizenProfile', 'user.employeeProfile'])
                ->oldest()
                ->get(),
        ]);
    }

    public function reply(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $ticket = $this->ownedTicket($request, $serviceRequest);

        return $this->sendReply($request, $ticket);
    }

    private function sendReply(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->status === Ticket::OPEN, 403, 'This ticket is closed.');

        $this->postMessage->handle($request, $ticket);

        return redirect()->to($ticket->citizenUrl());
    }

    // Only the owner can open the ticket (same idea as authorizeOwner)
    private function ownedTicket(Request $request, ServiceRequest $serviceRequest): Ticket
    {
        $ticket = $serviceRequest->ticket()->firstOrFail();

        abort_unless($ticket->user_id === $request->user()->id, 404);

        return $ticket;
    }
}