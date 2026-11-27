<?php

namespace App\Http\Controllers\Admin;

use App\Exports\TicketsExport;
use App\Http\Controllers\Controller;
use App\Mail\ContactMessageReplyMail;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ContactMessageController extends Controller
{
    public function index(Request $request, TicketsExport $export): View
    {
        $perPage = $request->integer('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50], true) ? $perPage : 15;

        $onInquiries = $request->query('communications_tab') === 'inquiries';
        $search = trim((string) $request->query('search', ''));

        $tickets = $export->query($request)
            ->paginate($perPage)
            ->appends($request->except('search'));

        $contactMessages = ContactMessage::query()
            ->withCount('replies')
            ->when($onInquiries && $search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';

                $query->where(function ($q) use ($like) {
                    $q->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('subject', 'like', $like)
                        ->orWhere('body', 'like', $like);
                });
            })
            ->latest('updated_at')
            ->paginate($perPage, ['*'], 'communications_page')
            ->appends($request->except('communications_page'));

        return view('admin.tickets.index', compact('tickets', 'contactMessages'));
    }

    public function show(ContactMessage $contactMessage): View
    {
        return view('admin.contact-messages.show', [
            'contactMessage' => $contactMessage->load('replies.user'),
        ]);
    }

    public function reply(Request $request, ContactMessage $contactMessage): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        try {
            Mail::to($contactMessage->email)->send(new ContactMessageReplyMail(
                $contactMessage,
                $request->user()->name,
                $data['body'],
                $request->user()->email,
            ));
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return back()->withInput()->with(
                'error',
                'The reply could not be emailed, so it was not saved. Check the mail settings and try again.'
            );
        }

        DB::transaction(function () use ($contactMessage, $request, $data): void {
            $contactMessage->replies()->create([
                'user_id' => $request->user()->id,
                'body' => $data['body'],
            ]);
            $contactMessage->update(['status' => ContactMessage::REPLIED]);
        });

        return redirect()->route('admin.contact-messages.show', $contactMessage)
            ->with('success', 'Reply sent to '.$contactMessage->email.'.');
    }
}