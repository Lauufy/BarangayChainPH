@extends('layouts.citizen')

@section('title', 'Notifications')

@section('content')
    @php
        // Icon look per status (light + dark)
        $styles = [
            'verified' => [
                'color' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400',
                'icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
            ],
            'welcome' => [
                'color' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400',
                'icon' => 'M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z',
            ],
            'rejected' => [
                'color' => 'bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-400',
                'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
            ],
            'info' => [
                'color' => 'bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400',
                'icon' => 'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0',
            ],
        ];

        $tab = 'rounded-lg px-4 py-1.5 text-sm font-semibold transition';
        $tabActive = 'bg-blue-600 text-white shadow-sm';
        $tabIdle = 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800';
        $pageBtn = 'rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium dark:border-slate-700';
        $pageLink = 'bg-white text-slate-700 transition hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800';
        $pageOff = 'text-slate-400 dark:text-slate-600';
    @endphp

    <div class="mx-auto max-w-4xl space-y-5">
        <header>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50">Notifications</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Reviews, approvals, credit warnings and announcements. Older than 12 months are removed automatically.
            </p>
        </header>

        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <nav class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white p-1 dark:border-slate-700 dark:bg-slate-900"
                aria-label="Filter notifications">
                <a href="{{ route('citizen.notifications') }}"
                    class="{{ $tab }} {{ $filter === 'all' ? $tabActive : $tabIdle }}"
                    @if ($filter === 'all') aria-current="page" @endif>All</a>

                <a href="{{ route('citizen.notifications', ['filter' => 'unread']) }}"
                    class="{{ $tab }} {{ $filter === 'unread' ? $tabActive : $tabIdle }}"
                    @if ($filter === 'unread') aria-current="page" @endif>Unread ({{ $unreadCount }})</a>
            </nav>

            {{-- Always visible; disabled when there is nothing unread --}}
            <form method="POST" action="{{ route('citizen.notifications.read-all') }}">
                @csrf
                <button type="submit" @disabled($unreadCount === 0)
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-900 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:text-slate-400 disabled:hover:bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800 dark:disabled:text-slate-600 dark:disabled:hover:bg-slate-900">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="m4.5 12.75 6 6 9-13.5M1.5 13.5l3 3M9 16.5l9-13.5" />
                    </svg>
                    Mark all as read
                </button>
            </form>
        </div>

        {{-- List --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-2.5 dark:border-slate-700 dark:bg-slate-900">
            @forelse ($notifications as $notification)
                @php
                    $style = $styles[$notification->data['status'] ?? 'info'] ?? $styles['info'];
                    $isUnread = $notification->read_at === null;
                    $title = $notification->data['title'] ?? 'Notification';
                @endphp

                <form method="POST" action="{{ route('citizen.notifications.read', $notification->id) }}"
                    class="{{ $isUnread ? 'bg-indigo-50/70 dark:bg-blue-500/10' : 'bg-slate-100 dark:bg-slate-800' }} {{ $loop->last ? '' : 'mb-2' }} relative flex items-start gap-3 rounded-xl px-4 py-3">
                    @csrf
                    <input type="hidden" name="open" value="1">
                    <span class="{{ $style['color'] }} flex h-9 w-9 shrink-0 items-center justify-center rounded-full">
                        <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.6"
                            viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $style['icon'] }}" />
                        </svg>
                    </span>

                    <div class="min-w-0 flex-1">
                        <button type="submit" class="cursor-pointer text-left after:absolute after:inset-0 text-sm font-semibold text-slate-900 hover:underline dark:text-slate-50">{{ $title }}</button>
                        <p class="mt-0.5 text-sm text-slate-600 dark:text-slate-300">{{ $notification->data['message'] ?? '' }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $notification->created_at->format('d M Y, h:i A') }}</p>
                    </div>

                    @if ($isUnread)
                        <span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-blue-600 dark:bg-blue-400" role="img"
                            aria-label="Unread"></span>
                    @endif
                </form>
            @empty
                <div class="py-12 text-center">
                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                        {{ $filter === 'unread' ? "You're all caught up" : 'No notifications yet' }}
                    </p>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ $filter === 'unread' ? 'You have no unread notifications.' : 'When something happens, you will see it here.' }}
                    </p>
                </div>
            @endforelse
        </section>

        {{-- Pagination --}}
        @if ($notifications->total() > 0)
            <div class="flex items-center justify-between gap-3">
                @if ($notifications->onFirstPage())
                    <span class="{{ $pageBtn }} {{ $pageOff }}">Previous</span>
                @else
                    <a href="{{ $notifications->previousPageUrl() }}" class="{{ $pageBtn }} {{ $pageLink }}">Previous</a>
                @endif

                <span class="text-sm text-slate-500 dark:text-slate-400">
                    {{ $notifications->firstItem() }}–{{ $notifications->lastItem() }} of {{ $notifications->total() }}
                </span>

                @if ($notifications->hasMorePages())
                    <a href="{{ $notifications->nextPageUrl() }}" class="{{ $pageBtn }} {{ $pageLink }}">Next</a>
                @else
                    <span class="{{ $pageBtn }} {{ $pageOff }}">Next</span>
                @endif
            </div>
        @endif
    </div>
@endsection