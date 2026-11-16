@extends('layouts.admin')

@section('title', 'Ticket')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        {{-- Return to the ticket inbox without changing the route or ticket context. --}}
        <a href="{{ route('admin.tickets.index') }}"
           class="inline-flex items-center gap-2 text-sm font-semibold text-[#17325c] hover:text-[#10244a]">
            <span aria-hidden="true">←</span>
            Back to tickets
        </a>

        <div class="admin-card p-6 mb-1">
            {{-- Identify the request and expose its existing details page. --}}
            <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-5">
                <x-admin.page-header label="Ticket" :title="$ticket->serviceRequest->reference_number"
                    :text="$ticket->serviceRequest->applicant_name.' · '.$ticket->serviceRequest->user->email">
                    <a href="{{ route('admin.permits.show', $ticket->serviceRequest) }}"
                       class="inline-flex rounded-sm border border-[#17325c] px-3 py-1.5 text-xs font-semibold text-[#17325c] hover:bg-slate-50">
                        View request
                    </a>
                </x-admin.page-header>
            </div>

            {{-- Render the chronological ticket conversation and retain its polling target. --}}
            <div id="messages" class="mt-5 max-h-[60vh] space-y-3 overflow-y-auto">
                @foreach ($messages as $message)
                    @if ($message->is_system)
                        {{-- Automatic message from the system --}}
                        <p class="rounded-sm bg-slate-50 px-3 py-2 text-center text-xs text-slate-500">
                            {{ $message->body }}
                        </p>
                    @else
                        <div @class(['flex', 'justify-end' => $message->user_id !== $ticket->user_id])>
                            <div @class([
                                'max-w-[80%] rounded-sm px-4 py-2.5 text-sm',
                                'bg-slate-100 text-slate-900' => $message->user_id === $ticket->user_id,
                                'bg-[#17325c] text-white' => $message->user_id !== $ticket->user_id,
                            ])>
                                <x-tickets.message-sender :message="$message" :ticket="$ticket" />
                                <p class="mt-1 whitespace-pre-line">{{ $message->body }}</p>
                                <p class="mt-1 text-[11px] opacity-70">
                                    {{ $message->created_at->timezone(config('app.timezone'))->format('M j, g:i A') }}
                                </p>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Let staff reply while open and show a closed-state message otherwise. --}}
        @if ($ticket->status === 'open')
            <form method="POST" action="{{ route('admin.tickets.reply', $ticket) }}" class="admin-card space-y-3 p-5">
                @csrf
                <x-form.input name="body" label="Reply" type="textarea" :light="true" raised
                    required maxlength="1000" rows="3" placeholder="Write a reply to the citizen" />
                <x-feedbacks.form-errors />
                <x-form.button type="submit" color="#17325c" hover-color="#10244a">
                    Send reply
                </x-form.button>
            </form>
        @else
            <p class="rounded-sm bg-slate-50 px-4 py-3 text-center text-sm text-slate-500">
                This ticket is closed.
            </p>
        @endif
    </div>

    <script>
        const box = document.getElementById('messages');
        box.scrollTop = box.scrollHeight;

        // Every 5 seconds, fetch this page again and swap only the message list
        setInterval(async () => {
            if (document.hidden) return;

            try {
                const html = await (await fetch(location.href)).text();
                const fresh = new DOMParser().parseFromString(html, 'text/html').getElementById('messages');
                const atBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 60;

                if (fresh && fresh.innerHTML !== box.innerHTML) {
                    box.innerHTML = fresh.innerHTML;
                    if (atBottom) box.scrollTop = box.scrollHeight;
                }
            } catch (e) {
                // network problem: try again on the next round
            }
        }, 5000);
    </script>
@endsection