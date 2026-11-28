@extends('layouts.admin')

@section('title', 'Website Inquiry')

@section('content')
    @php
        // Optional: controller may pass $inquiries (latest first). Falls back to just this ticket.
        $tickets = isset($inquiries) ? $inquiries : collect([$contactMessage]);
        $tz = config('app.timezone');
    @endphp

    <div class="space-y-4">
        @if (session('success'))
            <div role="status" class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div role="alert" class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                {{ session('error') }}
            </div>
        @endif

        <div class="flex min-h-150 overflow-hidden rounded-lg border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900 lg:h-[calc(100vh-10rem)]">

            {{-- ============ LEFT: ticket list ============ --}}
            <aside class="hidden w-80 shrink-0 flex-col border-r border-slate-200 dark:border-slate-800 md:flex">
                <div class="flex items-center justify-between px-4 pt-4">
                    <a href="{{ route('admin.contact-messages.index', ['communications_tab' => 'inquiries']) }}"
                        class="inline-flex items-center gap-2 text-sm font-bold text-slate-900 hover:underline dark:text-slate-100">
                        <span aria-hidden="true">‹</span> Website Inquiries
                    </a>
                    <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                        {{ $tickets->count() }}
                    </span>
                </div>

                <div class="p-4">
                    <label for="ticket-search" class="sr-only">Search tickets</label>
                    <input id="ticket-search" type="search" placeholder="Search tickets"
                        class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </div>

                <ul id="ticket-list" class="flex-1 divide-y divide-slate-100 overflow-y-auto border-t border-slate-100 dark:divide-slate-800 dark:border-slate-800">
                    @foreach ($tickets as $ticket)
                        @php
                            $isActive = $ticket->id === $contactMessage->id;
                            $isOpen = $ticket->replies_count ?? $ticket->replies->count() ? false : true;
                            $initials = strtoupper(mb_substr($ticket->first_name, 0, 1) . mb_substr($ticket->last_name, 0, 1));
                        @endphp
                        <li data-ticket-item
                            data-search="{{ strtolower($ticket->first_name . ' ' . $ticket->last_name . ' ' . $ticket->subject . ' ' . $ticket->body . ' ticket #' . $ticket->id) }}">
                            <a href="{{ route('admin.contact-messages.show', $ticket) }}"
                                @if ($isActive) aria-current="page" @endif
                                class="flex gap-3 px-4 py-3 transition hover:bg-slate-50 dark:hover:bg-slate-800/60 {{ $isActive ? 'bg-slate-100 dark:bg-slate-800' : '' }}">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-600 dark:bg-slate-700 dark:text-slate-200">
                                    {{ $initials }}
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-baseline justify-between gap-2">
                                        <span class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
                                            {{ $ticket->first_name }} {{ $ticket->last_name }}
                                        </span>
                                        <span class="shrink-0 text-xs text-slate-400">{{ $ticket->created_at->diffForHumans(null, true, true) }}</span>
                                    </span>
                                    <span class="block truncate text-xs text-slate-500 dark:text-slate-400">{{ $ticket->subject }}</span>
                                    <span class="mt-1 flex items-center justify-between text-xs">
                                        <span class="text-slate-400">Help ticket #{{ $ticket->id }}</span>
                                        @if ($isOpen)
                                            <span class="inline-flex items-center gap-1 font-medium text-emerald-600 dark:text-emerald-400">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Open
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 font-medium text-slate-500 dark:text-slate-400">
                                                <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> Replied
                                            </span>
                                        @endif
                                    </span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </aside>

            {{-- ============ RIGHT: conversation + reply ============ --}}
            <section class="flex min-w-0 flex-1 flex-col">

                {{-- Mobile back link --}}
                <a href="{{ route('admin.contact-messages.index', ['communications_tab' => 'inquiries']) }}"
                    class="border-b border-slate-200 px-4 py-3 text-sm font-semibold text-blue-700 hover:underline dark:border-slate-800 dark:text-blue-300 md:hidden">
                     Back to Communications Management
                </a>

                {{-- Email header fields --}}
                <header class="space-y-2 border-b border-slate-100 px-6 py-4 text-sm dark:border-slate-800">
                    <div class="flex items-center gap-4">
                        <span class="w-14 text-xs font-semibold text-slate-500 dark:text-slate-400">From</span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-800 dark:border-slate-700 dark:text-slate-100">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-700 text-[10px] text-white">
                                {{ strtoupper(mb_substr(auth()->user()->name ?? 'S', 0, 1)) }}
                            </span>
                            {{ auth()->user()->name ?? 'Staff' }}
                        </span>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="w-14 text-xs font-semibold text-slate-500 dark:text-slate-400">To</span>
                        <a class="text-slate-700 underline-offset-2 hover:underline dark:text-slate-300"
                            href="mailto:{{ $contactMessage->email }}">{{ $contactMessage->email }}</a>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="w-14 text-xs font-semibold text-slate-500 dark:text-slate-400">Subject</span>
                        <span class="min-w-0 truncate text-slate-700 dark:text-slate-300">Re: {{ $contactMessage->subject }}</span>
                    </div>
                </header>

                {{-- Thread --}}
                <div id="thread" class="flex-1 space-y-5 overflow-y-auto px-6 py-6">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        {{ $contactMessage->service }}
                    </p>

                    {{-- Original message --}}
                    <article class="max-w-2xl">
                        <div class="mb-1 flex flex-wrap items-baseline justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                                {{ $contactMessage->first_name }} {{ $contactMessage->last_name }}
                            </p>
                            <time class="text-xs text-slate-500 dark:text-slate-400"
                                datetime="{{ $contactMessage->created_at->toIso8601String() }}">
                                {{ $contactMessage->created_at->timezone($tz)->format('M j, Y g:i A') }}
                            </time>
                        </div>
                        <div class="rounded-lg rounded-tl-none border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-800/50">
                            <p class="whitespace-pre-line wrap-break-word text-sm leading-6 text-slate-700 dark:text-slate-300">{{ $contactMessage->body }}</p>
                        </div>
                    </article>

                    {{-- Replies --}}
                    @forelse ($contactMessage->replies as $reply)
                        <article class="ml-auto max-w-2xl">
                            <div class="mb-1 flex flex-wrap items-baseline justify-between gap-2">
                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                                    {{ $reply->user?->name ?? 'Former staff member' }}
                                </p>
                                <time class="text-xs text-slate-500 dark:text-slate-400"
                                    datetime="{{ $reply->created_at->toIso8601String() }}">
                                    Emailed {{ $reply->created_at->timezone($tz)->format('M j, Y g:i A') }}
                                </time>
                            </div>
                            <div class="rounded-lg rounded-tr-none bg-blue-700 p-4 text-white">
                                <p class="whitespace-pre-line wrap-break-word text-sm leading-6">{{ $reply->body }}</p>
                            </div>
                        </article>
                    @empty
                        <p class="rounded-md border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            No replies have been sent.
                        </p>
                    @endforelse
                </div>

                {{-- Composer --}}
                <form method="POST" action="{{ route('admin.contact-messages.reply', $contactMessage) }}"
                    class="border-t border-slate-200 p-4 dark:border-slate-800">
                    @csrf

                    <x-feedbacks.form-errors />

                    <div class="flex items-end gap-3">
                        <div class="flex-1">
                            <label for="body" class="sr-only">Reply by email to {{ $contactMessage->email }}</label>
                            <textarea id="body" name="body" rows="2" maxlength="5000" required
                                class="block max-h-40 w-full resize-y rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-600/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                placeholder="Type your message...">{{ old('body') }}</textarea>
                            @error('body')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" aria-label="Send reply by email" title="Send reply by email"
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-900 text-white transition hover:bg-blue-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M22 2 11 13" />
                                <path d="M22 2 15 22l-4-9-9-4 20-7Z" />
                            </svg>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <script>
        (function () {
            const thread = document.getElementById('thread');
            if (thread) thread.scrollTop = thread.scrollHeight;

            const search = document.getElementById('ticket-search');
            if (!search) return;
            search.addEventListener('input', function () {
                const q = search.value.trim().toLowerCase();
                document.querySelectorAll('[data-ticket-item]').forEach(function (li) {
                    li.hidden = q !== '' && !li.dataset.search.includes(q);
                });
            });
        })();
    </script>
@endsection