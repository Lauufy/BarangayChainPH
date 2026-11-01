{{-- resources/views/citizen/notifications.blade.php --}}
@extends('layouts.citizen')

@section('title', 'Notifications')

@section('content')
    @php
        $unreadCount = auth()->user()->unreadNotifications()->count();

        // Look of each status: icon circle color and icon shape
        $styles = [
            'verified' => [
                'color' => 'bg-emerald-50 text-emerald-500',
                'icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
            ],
            'rejected' => [
                'color' => 'bg-red-50 text-red-500',
                'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
            ],
            'info' => [
                'color' => 'bg-orange-50 text-orange-500',
                'icon' => 'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0',
            ],
        ];
    @endphp

    <div class="mx-auto max-w-3xl space-y-5 sm:space-y-6">
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            {{-- Header --}}
            <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-4 py-4 sm:px-6">
                <div>
                    <h1 class="font-semibold text-slate-900">Notifications</h1>
                    <p class="mt-0.5 text-sm text-slate-500">
                        {{ $unreadCount > 0 ? 'You have '.$unreadCount.' new '.\Illuminate\Support\Str::plural('notification', $unreadCount).'.' : "You're all caught up." }}
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    @if ($unreadCount > 0)
                        <form method="POST" action="{{ route('citizen.notifications.read-all') }}">
                            @csrf
                            <button type="submit" class="text-sm font-semibold text-[#17325c] underline-offset-4 hover:underline">Mark all as read</button>
                        </form>
                    @endif

                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500" aria-hidden="true">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13h5l1 3h6l1-3h5M3 13V6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v7M3 13v5a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-5" />
                        </svg>
                    </span>
                </div>
            </div>

            {{-- Timeline list --}}
            <div class="px-4 py-5 sm:px-6">
                @forelse ($notifications as $notification)
                    @php
                        $style = $styles[$notification->data['status'] ?? 'info'] ?? $styles['info'];
                    @endphp

                    <div class="relative pb-6 last:pb-0">
                        {{-- Line that joins the icons --}}
                        @unless ($loop->last)
                            <span class="absolute bottom-1 left-5 top-11 w-px -translate-x-1/2 bg-slate-200" aria-hidden="true"></span>
                        @endunless

                        <form method="POST" action="{{ route('citizen.notifications.read', $notification->id) }}">
                            @csrf
                            <button type="submit" class="group relative flex w-full items-start gap-4 text-left">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $style['color'] }}">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $style['icon'] }}" />
                                    </svg>
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="flex items-start justify-between gap-3">
                                        <span class="text-[13px] font-semibold text-slate-900 group-hover:underline">
                                            {{ $notification->data['title'] ?? 'Notification' }}
                                            @if ($notification->read_at === null)
                                                <span class="ml-1 inline-block h-2 w-2 rounded-full bg-[#17325c] align-middle" aria-label="Unread"></span>
                                            @endif
                                        </span>
                                        <span class="shrink-0 whitespace-nowrap text-[11px] text-slate-500">{{ $notification->created_at->diffForHumans() }}</span>
                                    </span>
                                    <span class="mt-0.5 block text-xs text-slate-500">{{ $notification->data['message'] ?? '' }}</span>
                                </span>
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="py-10 text-center">
                        <p class="text-sm font-semibold text-slate-900">No notifications yet</p>
                        <p class="mt-1 text-sm text-slate-500">When something happens in your account, you will see it here.</p>
                    </div>
                @endforelse
            </div>
        </section>

        {{ $notifications->links() }}
    </div>
@endsection