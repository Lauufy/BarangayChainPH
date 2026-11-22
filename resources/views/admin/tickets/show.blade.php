@extends('layouts.admin')

@section('title', 'Ticket')

@section('content')
    @php
        $request = $ticket->serviceRequest;
        $isOpen = $ticket->status === 'open';
        $lastDay = null;
        $initialsOf = fn ($name) => Str::of($name ?: '?')->trim()->explode(' ')->filter()->map(fn ($p) => Str::substr($p, 0, 1))->take(2)->join('');
    @endphp

    {{-- Adjust 8rem to the height of your admin top bar and page padding. --}}
    <div
        class="flex h-[calc(100vh-8rem)] min-h-136 overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">

        {{-- LEFT: ticket list. #ticket-list is refreshed by the polling script. --}}
        <aside
            class="hidden w-80 shrink-0 flex-col border-r border-slate-200 dark:border-slate-800 lg:flex xl:w-96">
            <div class="flex h-14 items-center gap-1 px-4">
                <a href="{{ route('admin.tickets.index') }}" aria-label="Back to tickets"
                    class="-ml-2 grid size-8 shrink-0 place-items-center rounded-sm text-slate-500 transition hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-white/5">
                    <svg class="block size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m15 18-6-6 6-6" />
                    </svg>
                </a>
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Tickets</h2>
                <span
                    class="ml-auto rounded-sm bg-slate-100 px-2 py-0.5 text-xs font-medium tabular-nums text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    {{ $tickets->count() }}
                </span>
            </div>

            <div class="px-4 pb-3">
                <label for="ticket-search" class="sr-only">Search tickets</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.3-4.3" />
                    </svg>
                    <input id="ticket-search" type="search" placeholder="Search tickets"
                        class="block w-full rounded-md border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/30">
                </div>
            </div>

            <nav id="ticket-list" aria-label="Tickets"
                class="min-h-0 flex-1 divide-y divide-slate-100 overflow-y-auto border-t border-slate-100 dark:divide-slate-800 dark:border-slate-800">
                @forelse ($tickets as $item)
                    @php
                        $itemRequest = $item->serviceRequest;
                        $itemOpen = $item->status === 'open';
                        $active = $item->is($ticket);
                        $last = $item->latestMessage;
                        $when = ($last?->created_at ?? $item->updated_at)?->timezone(config('app.timezone'));
                    @endphp

                    <a href="{{ route('admin.tickets.show', $item) }}"
                        data-search="{{ Str::lower($itemRequest->reference_number . ' ' . $itemRequest->applicant_name) }}"
                        @if ($active) aria-current="page" @endif
                        @class([
                            'flex items-start gap-3 px-4 py-3 transition',
                            'bg-slate-100 dark:bg-slate-800' => $active,
                            'hover:bg-slate-50 dark:hover:bg-white/5' => !$active,
                        ])>
                        <span
                            class="grid size-10 shrink-0 place-items-center rounded-full bg-slate-200 text-xs font-semibold text-slate-600 dark:bg-slate-700 dark:text-slate-200">
                            {{ $initialsOf($itemRequest->applicant_name) }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
                                    {{ $itemRequest->applicant_name }}
                                </p>
                                <span class="shrink-0 text-[11px] text-slate-400 dark:text-slate-500">
                                    {{ $when?->diffForHumans(null, true, true) }}
                                </span>
                            </div>

                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">
                                {{ $last?->body ?? 'No messages yet' }}
                            </p>

                            <div class="mt-1 flex items-center gap-1.5 text-[11px]">
                                <span class="truncate text-slate-400 dark:text-slate-500">
                                    {{ $itemRequest->reference_number }}
                                </span>
                                <span @class([
                                    'ml-auto inline-flex shrink-0 items-center gap-1 font-medium',
                                    'text-emerald-600 dark:text-emerald-400' => $itemOpen,
                                    'text-slate-400 dark:text-slate-500' => !$itemOpen,
                                ])>
                                    <span @class([
                                        'size-1.5 rounded-full',
                                        'bg-emerald-500' => $itemOpen,
                                        'bg-slate-400' => !$itemOpen,
                                    ])></span>
                                    {{ $itemOpen ? 'Open' : 'Closed' }}
                                </span>
                            </div>
                        </div>
                    </a>
                @empty
                    <p class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">No tickets yet.</p>
                @endforelse

                <p id="ticket-no-match" hidden
                    class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                    No tickets match your search.
                </p>
            </nav>
        </aside>

        {{-- RIGHT: conversation --}}
        <section class="flex min-w-0 flex-1 flex-col">

            {{-- Header --}}
            <div
                class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3.5 dark:border-slate-800 sm:px-5">
                <div class="flex min-w-0 items-center gap-4">
                    <a href="{{ route('admin.tickets.index') }}" aria-label="Back to tickets"
                        class="grid size-8 shrink-0 place-items-center rounded-sm text-slate-500 transition hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-white/5 lg:hidden">
                        ←
                    </a>

                    <span
                        class="grid size-12 shrink-0 place-items-center rounded-full bg-blue-600 text-base font-semibold text-white">
                        {{ $initialsOf($request->applicant_name) }}
                    </span>

                    <div class="min-w-0">
                        <h1 class="truncate text-base font-semibold text-slate-900 dark:text-slate-100">
                            {{ $request->reference_number }}
                        </h1>
                        <p class="truncate text-sm text-slate-500 dark:text-slate-400">
                            {{ $request->applicant_name }} · {{ $request->user->email }}
                            ·
                            <span @class([
                                'inline-flex items-center gap-1.5 font-medium',
                                'text-emerald-600 dark:text-emerald-400' => $isOpen,
                                'text-slate-500 dark:text-slate-400' => !$isOpen,
                            ])>
                                <span @class([
                                    'size-1.5 rounded-full',
                                    'bg-emerald-500' => $isOpen,
                                    'bg-slate-400' => !$isOpen,
                                ])></span>
                                {{ $isOpen ? 'Open' : 'Closed' }}
                            </span>
                        </p>
                    </div>
                </div>

                <a href="{{ route('admin.permits.show', $request) }}"
                    class="inline-flex shrink-0 items-center rounded-sm border border-slate-200 px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-white/5">
                    View request
                </a>
            </div>

            {{-- Messages. #messages is the polling target. --}}
            <div class="relative min-h-0 flex-1">
                <div id="messages" role="log" aria-live="polite" aria-label="Ticket conversation"
                    class="h-full space-y-5 overflow-y-auto scroll-smooth bg-white px-4 py-5 dark:bg-slate-900 sm:px-5">

                    @forelse ($messages as $message)
                        @php
                            $at = $message->created_at->timezone(config('app.timezone'));
                            $day = $at->format('Y-m-d');
                        @endphp

                        {{-- Date separator when the day changes. --}}
                        @if ($day !== $lastDay)
                            @php $lastDay = $day; @endphp
                            <div class="flex items-center gap-3" role="separator">
                                <span class="h-px flex-1 bg-slate-100 dark:bg-slate-800"></span>
                                <span
                                    class="text-[11px] font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">
                                    {{ $at->isToday() ? 'Today' : ($at->isYesterday() ? 'Yesterday' : $at->format('M j, Y')) }}
                                </span>
                                <span class="h-px flex-1 bg-slate-100 dark:bg-slate-800"></span>
                            </div>
                        @endif

                        @if ($message->is_system)
                            {{-- Automatic message from the system --}}
                            <p
                                class="mx-auto w-fit max-w-[90%] rounded-sm bg-slate-100 px-3 py-1 text-center text-xs text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                {{ $message->body }}
                            </p>
                        @else
                            @php
                                $fromCitizen = $message->user_id === $ticket->user_id;
                                $senderName = $fromCitizen
                                    ? $request->applicant_name
                                    : $message->user?->name ?? 'Former account';
                            @endphp

                            <div @class(['flex items-start gap-2.5', 'justify-end' => !$fromCitizen])>
                                @if ($fromCitizen)
                                    <span
                                        class="grid size-8 shrink-0 place-items-center rounded-full bg-slate-200 text-[11px] font-semibold text-slate-600 dark:bg-slate-700 dark:text-slate-200">
                                        {{ $initialsOf($senderName) }}
                                    </span>
                                @endif

                                <div @class([
                                    'flex max-w-[85%] flex-col sm:max-w-[75%]',
                                    'items-end' => !$fromCitizen,
                                ])>
                                    @if ($fromCitizen)
                                        <p class="mb-1 text-xs font-semibold text-slate-900 dark:text-slate-100">
                                            {{ $senderName }}
                                        </p>
                                    @endif

                                    <div @class([
                                        'rounded-lg px-3.5 py-2.5 text-sm',
                                        'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100' => $fromCitizen,
                                        'bg-blue-600 text-white' => !$fromCitizen,
                                    ])>
                                        <p class="wrap-break-word whitespace-pre-line">{{ $message->body }}</p>
                                    </div>

                                    <p class="mt-1 text-[11px] uppercase text-slate-400 dark:text-slate-500">
                                        @unless ($fromCitizen)
                                            {{ $senderName }} ·
                                        @endunless
                                        {{ $at->format('g:i A') }}
                                    </p>
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="grid place-items-center py-10 text-center">
                            <p class="text-sm font-medium text-slate-700 dark:text-slate-200">No messages yet</p>
                            <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">Replies will appear here.</p>
                        </div>
                    @endforelse
                </div>

                {{-- Shown when new messages arrive while the reader has scrolled up. --}}
                <button type="button" id="new-messages" hidden
                    class="absolute bottom-3 left-1/2 -translate-x-1/2 rounded-sm bg-blue-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-lg transition hover:bg-blue-500">
                    New messages ↓
                </button>
            </div>

            {{-- Composer: rounded input + separate send button while open, closed notice otherwise. --}}
            @if ($isOpen)
                <form method="POST" action="{{ route('admin.tickets.reply', $ticket) }}" id="reply-form"
                    class="border-t border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900 sm:p-4">
                    @csrf

                    <div class="flex items-end gap-2">
                        {{-- Input pill --}}
                        <div
                            class="flex min-h-11 min-w-0 flex-1 items-center gap-1 rounded-md bg-slate-100 px-3.5 py-2 transition focus-within:ring-2 focus-within:ring-blue-600/20 dark:bg-slate-800 dark:focus-within:ring-blue-500/30">
                            <label for="body" class="sr-only">Reply</label>
                            <textarea id="body" name="body" rows="1" maxlength="1000" required
                                placeholder="Type your message..."
                                class="block max-h-32 min-h-6 w-full resize-none border-0 bg-transparent p-0 py-0.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0 dark:text-slate-100 dark:placeholder:text-slate-500">{{ old('body') }}</textarea>
                        </div>

                        {{-- Send --}}
                        <button type="submit" id="reply-submit" aria-label="Send reply"
                            class="grid size-11 shrink-0 place-items-center rounded-md bg-slate-900 text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m22 2-7 20-4-9-9-4Z" />
                                <path d="M22 2 11 13" />
                            </svg>
                        </button>
                    </div>

                    {{-- Character counter: only shown when nearing the limit. --}}
                    <p id="body-count" hidden
                        class="mt-1.5 text-right text-[11px] tabular-nums text-slate-400 dark:text-slate-500"></p>

                    <div class="mt-2">
                        <x-feedbacks.form-errors />
                    </div>
                </form>
            @else
                <p
                    class="border-t border-slate-200 bg-slate-50 px-4 py-3 text-center text-sm text-slate-500 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
                    This ticket is closed.
                </p>
            @endif
        </section>
    </div>

    <script>
        (() => {
            const box = document.getElementById('messages');
            const pill = document.getElementById('new-messages');
            const list = document.getElementById('ticket-list');
            const search = document.getElementById('ticket-search');
            const noMatch = document.getElementById('ticket-no-match');

            const isAtBottom = () => box.scrollHeight - box.scrollTop - box.clientHeight < 60;
            const toBottom = (smooth = false) => box.scrollTo({
                top: box.scrollHeight,
                behavior: smooth ? 'smooth' : 'auto'
            });

            // Jump to the latest message on load without animating.
            box.style.scrollBehavior = 'auto';
            toBottom();
            box.style.scrollBehavior = '';

            // Keep the active ticket visible in the list.
            list.querySelector('[aria-current="page"]')?.scrollIntoView({ block: 'nearest' });

            pill.addEventListener('click', () => {
                toBottom(true);
                pill.hidden = true;
            });
            box.addEventListener('scroll', () => {
                if (isAtBottom()) pill.hidden = true;
            });

            // Ticket search: filters the list as you type, and again after each refresh.
            const applyFilter = () => {
                const q = search.value.trim().toLowerCase();
                let shown = 0;
                list.querySelectorAll('[data-search]').forEach((el) => {
                    const match = !q || el.dataset.search.includes(q);
                    el.hidden = !match;
                    if (match) shown++;
                });
                const noMatchEl = document.getElementById('ticket-no-match');
                if (noMatchEl) noMatchEl.hidden = !q || shown > 0;
            };
            search.addEventListener('input', applyFilter);

            // Every 5 seconds, fetch this page again and swap the message list and the ticket list.
            setInterval(async () => {
                if (document.hidden) return;

                try {
                    const html = await (await fetch(location.href, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })).text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');

                    const freshList = doc.getElementById('ticket-list');
                    if (freshList && freshList.innerHTML !== list.innerHTML) {
                        const listScroll = list.scrollTop;
                        list.innerHTML = freshList.innerHTML;
                        list.scrollTop = listScroll;
                        applyFilter();
                    }

                    const fresh = doc.getElementById('messages');
                    if (!fresh || fresh.innerHTML === box.innerHTML) return;

                    const stick = isAtBottom();
                    const previous = box.scrollTop;
                    box.innerHTML = fresh.innerHTML;

                    if (stick) {
                        toBottom();
                    } else {
                        box.scrollTop = previous; // keep the reader's place
                        pill.hidden = false;
                    }
                } catch (e) {
                    // network problem: try again on the next round
                }
            }, 5000);

            // Composer: auto-grow, counter near the limit, Enter to send, prevent double submit.
            const form = document.getElementById('reply-form');
            if (!form) return;

            const body = document.getElementById('body');
            const count = document.getElementById('body-count');

            const update = () => {
                // Auto-grow up to the max-h-32 cap.
                body.style.height = 'auto';
                body.style.height = body.scrollHeight + 'px';

                // Only show the counter when nearing the limit.
                const len = body.value.length;
                count.hidden = len < body.maxLength * 0.8;
                count.textContent = len + ' / ' + body.maxLength;
            };
            body.addEventListener('input', update);
            update();

            // Enter sends, Shift+Enter adds a new line.
            body.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
                    e.preventDefault();
                    form.requestSubmit();
                }
            });

            form.addEventListener('submit', () => {
                const btn = document.getElementById('reply-submit');
                if (btn) btn.disabled = true;
            });
        })();
    </script>
@endsection