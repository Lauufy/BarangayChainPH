@extends('layouts.citizen')

@section('title', 'Ticket')

@section('content')
    @php
        $list = collect($messages)->values();
        $at = fn($m) => $m->created_at->timezone(config('app.timezone'));
        $dayLabel = fn($d) => $d->isToday() ? 'Today' : ($d->isYesterday() ? 'Yesterday' : $d->format('M j, Y'));
    @endphp

    <div class="w-full space-y-4">
        {{-- Header --}}
        <header class="border-(--gray-200) dark:border-(--gray-700) space-y-2 border-b pb-4">
            <nav class="text-(--gray-500) dark:text-(--gray-400) flex items-center gap-2 text-xs" aria-label="Breadcrumb">
                <a href="{{ route($application ? 'citizen.applications' : 'citizen.help.tickets') }}"
                    class="hover:text-(--blue-700) dark:hover:text-(--blue-300)">{{ $application ? 'Applications' : 'My tickets' }}</a>
                <span aria-hidden="true">›</span>
                <span class="text-(--blue-700) dark:text-(--blue-300) font-semibold">Ticket</span>
            </nav>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-(--blue-900) dark:text-(--blue-300) text-2xl font-bold tracking-tight sm:text-3xl">
                        {{ $ticket->reference() }}</h1>
                    <p class="text-(--gray-600) dark:text-(--gray-300) mt-1 text-sm">{{ $application ? 'Send a message to the barangay about this application.' : 'Your conversation with the barangay.' }}</p>
                </div>

                <div class="flex items-center gap-3">
                    <span @class([
                        'inline-flex items-center gap-2 rounded-md border border-transparent px-4 py-2 text-sm font-semibold capitalize ring-1',
                        'bg-green-50 text-green-800 ring-green-200 dark:bg-green-900/30 dark:text-green-300 dark:ring-green-800' =>
                            $ticket->status === 'open',
                        'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-700 dark:text-slate-200 dark:ring-slate-600' =>
                            $ticket->status !== 'open',
                    ])>
                        <span @class([
                            'h-2 w-2 rounded-full',
                            'bg-green-500' => $ticket->status === 'open',
                            'bg-slate-400' => $ticket->status !== 'open',
                        ]) aria-hidden="true"></span>
                        {{ $ticket->status }}
                    </span>

                    <a href="{{ route($application ? 'citizen.applications' : 'citizen.help.tickets') }}"
                        class="border-(--gray-300) bg-(--white) text-(--gray-700) hover:bg-(--gray-50) focus-visible:ring-(--blue-300) dark:border-(--gray-600) dark:bg-(--gray-800) dark:text-(--gray-200) dark:hover:bg-(--gray-700) inline-flex items-center rounded-md border px-8 py-2 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2">Back</a>
                </div>
            </div>
        </header>

        {{-- Chat --}}
        <section
            class="bg-(--white) ring-(--gray-200) dark:bg-(--gray-800) dark:ring-(--gray-700) overflow-hidden rounded-2xl shadow-sm ring-1">
            <div id="messages"
                class="h-112 bg-(--gray-50) dark:bg-(--gray-900) overflow-y-auto overscroll-contain px-4 py-5 sm:px-6">
                @forelse ($list as $i => $message)
                    @php
                        $mine = $message->user_id === auth()->id();
                        $prev = $list[$i - 1] ?? null;
                        $next = $list[$i + 1] ?? null;
                        $date = $at($message);
                        $newDay = !$prev || !$at($prev)->isSameDay($date);
                        $first = $prev && !$newDay && !$prev->is_system && $prev->user_id === $message->user_id;
                        $last = !(
                            $next &&
                            !$next->is_system &&
                            $next->user_id === $message->user_id &&
                            $at($next)->isSameDay($date)
                        );
                        // Image with no text: show it without a colored bubble
                        $imageOnly = $message->attachment_path && $message->imageContentType() && blank($message->body);
                    @endphp

                    @if ($newDay)
                        <div class="text-(--gray-400) mt-4 flex items-center gap-3 text-[11px] font-medium first:mt-0">
                            <span class="bg-(--gray-200) dark:bg-(--gray-700) h-px flex-1"></span>
                            {{ $dayLabel($date) }}
                            <span class="bg-(--gray-200) dark:bg-(--gray-700) h-px flex-1"></span>
                        </div>
                    @endif

                    @if ($message->is_system)
                        <p class="text-(--gray-400) mt-3 text-center text-xs">{{ $message->body }}</p>
                    @else
                        <div @class([
                            'flex items-end gap-2',
                            'flex-row-reverse' => $mine,
                            'mt-1' => $first,
                            'mt-3' => !$first,
                        ])>
                            @unless ($mine)
                                @if ($last)
                                    <span
                                        class="bg-(--gray-200) text-(--gray-500) dark:bg-(--gray-700) dark:text-(--gray-300) flex h-8 w-8 shrink-0 items-center justify-center rounded-full">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <circle cx="12" cy="8" r="4" />
                                            <path d="M4 21a8 8 0 0 1 16 0" />
                                        </svg>
                                    </span>
                                @else
                                    <span class="w-8 shrink-0"></span>
                                @endif
                            @endunless

                            <div @class([
                                'max-w-[80%] rounded-2xl text-sm',
                                'px-3.5 py-2' => !$imageOnly,
                                'bg-(--blue-600) text-(--white)' => $mine && !$imageOnly,
                                'bg-(--white) text-(--gray-900) ring-1 ring-(--gray-200) dark:bg-(--gray-700) dark:text-(--gray-50) dark:ring-(--gray-600)' => !$mine && !$imageOnly,
                                'text-(--gray-500) dark:text-(--gray-400)' => $imageOnly,
                                'rounded-br-md' => $mine && $last,
                                'rounded-bl-md' => !$mine && $last,
                            ])>
                                @unless ($first)
                                    <x-tickets.message-sender :message="$message" :ticket="$ticket" />
                                @endunless
                                @if (filled($message->body))
                                    <p class="wrap-anywhere whitespace-pre-line">{{ $message->body }}</p>
                                @endif
                                <x-tickets.attachment :message="$message" />
                                <p class="mt-0.5 text-right text-[10px] opacity-60">{{ $date->format('g:i A') }}</p>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="text-(--gray-400) flex h-full items-center justify-center text-sm">No messages yet.</div>
                @endforelse
            </div>

            {{-- Reply (only while the ticket is open) --}}
            @if ($ticket->status === 'open')
                <div class="border-t border-slate-200 p-3 dark:border-slate-700">
                    <x-tickets.composer :action="$application ? route('citizen.tickets.reply', $application) : route('citizen.help.reply', $ticket)" />
                </div>
            @else
                <p
                    class="border-(--gray-200) bg-(--gray-50) text-(--gray-500) dark:border-(--gray-700) dark:bg-(--gray-900) border-t px-4 py-3 text-center text-sm">
                    This ticket is closed. You can no longer send messages.
                </p>
            @endif
        </section>
    </div>

    <script>
        const box = document.getElementById('messages');
        box.scrollTop = box.scrollHeight; // start at the latest message

        // Every 5 seconds, fetch this page again and swap only the message list
        setInterval(async () => {
            if (document.hidden) return; // skip while the tab is in the background

            try {
                const html = await (await fetch(location.href)).text();
                const fresh = new DOMParser().parseFromString(html, 'text/html').getElementById('messages');
                const atBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 60;

                if (fresh && fresh.innerHTML !== box.innerHTML) {
                    box.innerHTML = fresh.innerHTML;
                    if (atBottom) box.scrollTop = box.scrollHeight; // follow new messages only if already at the bottom
                }
            } catch (e) {
                // network problem: try again on the next round
            }
        }, 5000);
    </script>
@endsection