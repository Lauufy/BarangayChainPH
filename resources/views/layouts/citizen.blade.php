<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Citizen Portal') | Barangay San Luis</title>
    <link rel="icon" type="image/png" href="{{ asset('img/branding/barangaychain-lg2.png') }}">

    {{-- Apply the saved theme before first paint --}}
    <script>
        try {
            const t = localStorage.getItem('theme');
            document.documentElement.classList.toggle('dark', t ? t === 'dark' : matchMedia('(prefers-color-scheme: dark)').matches);
        } catch (e) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="flex min-h-screen flex-col bg-(--gray-50) font-sans text-(--gray-700) antialiased transition-colors duration-300 dark:bg-(--black-700) dark:text-(--gray-200)">
    @php
        $user = auth()->user();
        $photo = $user->citizenProfile?->photo_path;

        $hideBell = request()->routeIs('permits.verify', 'citizen.applications.show', 'citizen.applications.print', 'citizen.certificate-of-indigency.preview', 'citizen.barangay-clearance.preview', 'citizen.certificate-of-residency.preview');
        $notifications = $hideBell ? collect() : $user->notifications()->latest()->limit(10)->get();
        $unread = $hideBell ? 0 : $user->unreadNotifications()->count();

        // route, label, active pattern
        $navLinks = collect([
            ['citizen.dashboard', 'Home', 'citizen.dashboard'],
            ['citizen.services', 'Services', 'citizen.services'],
            ['citizen.applications', 'Applications', 'citizen.applications*'],
            ['citizen.documents', 'Documents', 'citizen.documents*'],
        ])->map(fn($l) => ['route' => $l[0], 'text' => $l[1], 'active' => request()->routeIs($l[2])]);

        [$badgeText, $badgeClass] = match (true) {
            $user->isIdentityVerified() => ['Verified', 'bg-[color-mix(in_srgb,var(--green-500)_18%,transparent)] text-(--green-700) dark:text-(--green-300)'],
            $user->identityStatus() === 'pending' => ['Pending', 'bg-[color-mix(in_srgb,var(--amber-500)_20%,transparent)] text-(--amber-500)'],
            default => ['Required', 'bg-[color-mix(in_srgb,var(--red-500)_15%,transparent)] text-(--red-600) dark:text-(--red-500)'],
        };

        // status => [colors, icon path]
        $notifStyles = [
            'verified' => ['bg-[color-mix(in_srgb,var(--green-500)_18%,transparent)] text-(--green-600) dark:text-(--green-300)', 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
            'rejected' => ['bg-[color-mix(in_srgb,var(--red-500)_15%,transparent)] text-(--red-600) dark:text-(--red-500)', 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'],
            'info' => ['bg-[color-mix(in_srgb,var(--amber-500)_18%,transparent)] text-(--amber-500)', 'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0'],
        ];

        // Shared class strings
        $ink = 'text-(--gray-900) dark:text-(--gray-50)';
        $muted = 'text-(--gray-500) dark:text-(--gray-400)';
        $line = 'border-(--gray-200) dark:border-(--gray-700)';
        $hover = 'hover:bg-(--gray-100) dark:hover:bg-(--gray-700)';
        $link = 'text-sm font-semibold text-(--blue-700) underline dark:text-(--blue-300)';
        $panel = 'rounded-xl border border-(--gray-200) bg-(--white) shadow-xl dark:border-(--gray-700) dark:bg-(--gray-800)';
        $navLink = 'relative inline-flex items-center whitespace-nowrap py-3 text-sm font-medium text-(--gray-600) transition-colors after:absolute after:inset-x-0 after:bottom-0 after:h-0.5 after:origin-left after:scale-x-0 after:bg-(--green-600) after:transition-transform hover:text-(--blue-700) hover:after:scale-x-100 focus-visible:text-(--blue-700) focus-visible:after:scale-x-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--blue-600) aria-[current=page]:font-semibold aria-[current=page]:text-(--blue-700) aria-[current=page]:after:scale-x-100 dark:text-(--gray-300) dark:hover:text-(--blue-300) dark:focus-visible:text-(--blue-300) dark:aria-[current=page]:text-(--blue-300)';
    @endphp

    {{-- Government bar --}}
    <div class="bg-(--blue-900) text-xs text-(--white)">
        <div class="mx-auto flex h-9 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
            <span class="truncate text-(--blue-100)">Official Barangay Website</span>
            <span class="hidden text-(--blue-200) sm:block">Republic of the Philippines | Antipolo City, Rizal</span>
        </div>
    </div>

    {{-- Header --}}
    <header class="{{ $line }} sticky top-0 z-30 border-b bg-(--white) dark:bg-(--gray-900)">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <x-partials.logo />

            {{-- Desktop navigation --}}
            <nav aria-label="Main navigation" class="hidden items-center gap-5 lg:flex">
                @foreach ($navLinks as $item)
                    <a href="{{ route($item['route']) }}" class="{{ $navLink }}" @if ($item['active']) aria-current="page" @endif>{{ $item['text'] }}</a>
                @endforeach
            </nav>

            <div class="flex shrink-0 items-center gap-1 sm:gap-2">
                <div class="hidden lg:block"><x-partials.theme-toggle /></div>

                {{-- Notification bell --}}
                @unless ($hideBell)
                    <details id="notification-menu" class="sm:relative">
                        <summary aria-label="Open notifications"
                            class="{{ $hover }} relative flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-full text-(--gray-600) dark:text-(--gray-300) [&::-webkit-details-marker]:hidden">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $notifStyles['info'][1] }}" />
                            </svg>
                            @if ($unread > 0)
                                <span id="notification-count" class="absolute right-0 top-0 flex h-5 min-w-5 items-center justify-center rounded-full bg-(--red-600) px-1 text-[11px] font-bold leading-none text-(--white)">{{ $unread > 9 ? '9+' : $unread }}</span>
                            @endif
                        </summary>

                        <div class="{{ $panel }} absolute inset-x-4 top-full z-40 mt-2 sm:inset-x-auto sm:right-0 sm:w-96">
                            <div class="{{ $line }} border-b px-4 py-3">
                                <p class="{{ $ink }} text-sm font-bold">Notifications</p>
                                <p id="notification-subtitle" class="{{ $muted }} mt-0.5 text-xs">
                                    {{ $unread > 0 ? "You have {$unread} new " . str('notification')->plural($unread) . '.' : "You're all caught up." }}
                                </p>
                            </div>

                            <div class="max-h-96 overflow-y-auto px-4 py-4">
                                @forelse ($notifications as $n)
                                    @php [$color, $icon] = $notifStyles[$n->data['status'] ?? 'info'] ?? $notifStyles['info']; @endphp
                                    <div class="relative pb-5 last:pb-0">
                                        @unless ($loop->last)
                                            <span class="bg-(--gray-200) dark:bg-(--gray-700) left-4.5 absolute bottom-1 top-10 w-px -translate-x-1/2" aria-hidden="true"></span>
                                        @endunless

                                        <form method="POST" action="{{ route('citizen.notifications.read', $n->id) }}">
                                            @csrf
                                            <button type="submit" class="group/item flex w-full items-start gap-3 text-left">
                                                <span class="{{ $color }} flex h-9 w-9 shrink-0 items-center justify-center rounded-full">
                                                    <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                                                    </svg>
                                                </span>
                                                <span class="min-w-0 flex-1">
                                                    <span class="flex items-start justify-between gap-3">
                                                        <span class="{{ $ink }} text-[13px] font-semibold group-hover/item:underline">
                                                            {{ $n->data['title'] ?? 'Notification' }}
                                                            @if ($n->read_at === null)
                                                                <span data-unread-dot class="bg-(--blue-600) dark:bg-(--blue-300) ml-1 inline-block h-2 w-2 rounded-full align-middle"><span class="sr-only">Unread</span></span>
                                                            @endif
                                                        </span>
                                                        <span class="{{ $muted }} shrink-0 whitespace-nowrap text-[11px]">{{ $n->created_at->diffForHumans() }}</span>
                                                    </span>
                                                    <span class="{{ $muted }} mt-0.5 block text-xs">{{ $n->data['message'] ?? '' }}</span>
                                                </span>
                                            </button>
                                        </form>
                                    </div>
                                @empty
                                    <p class="{{ $muted }} px-2 py-8 text-center text-sm">No notifications yet.</p>
                                @endforelse
                            </div>

                            <div class="{{ $line }} flex items-center justify-between gap-3 border-t px-4 py-3">
                                @if ($unread > 0)
                                    <form id="mark-all-form" method="POST" action="{{ route('citizen.notifications.read-all') }}">
                                        @csrf
                                        <button class="{{ $link }}">Mark all as read</button>
                                    </form>
                                @endif
                                <a href="{{ route('citizen.notifications') }}" class="{{ $link }} ml-auto">View all</a>
                            </div>
                        </div>
                    </details>
                @endunless

                {{-- User menu --}}
                <details id="user-menu" class="group relative shrink-0">
                    <summary aria-label="Open user menu" class="{{ $hover }} flex cursor-pointer list-none items-center gap-3 rounded-lg p-1.5 [&::-webkit-details-marker]:hidden">
                        <span class="hidden text-right lg:block">
                            <span class="{{ $ink }} block max-w-40 truncate text-sm font-bold">{{ $user->name }}</span>
                            <span class="{{ $muted }} block text-xs">Citizen</span>
                        </span>

                        @if ($photo)
                            <img src="{{ route('citizen.profile.photo.show') }}" alt="" class="hidden h-10 w-10 rounded-full object-cover lg:block">
                        @else
                            <span class="hidden h-10 w-10 items-center justify-center rounded-full bg-(--blue-600) font-bold text-(--white) lg:flex">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                        @endif

                        {{-- Mobile menu icon --}}
                        <span class="text-(--blue-700) dark:text-(--blue-300) flex h-10 w-10 items-center justify-center rounded-md bg-(--blue-50) dark:bg-(--gray-800) lg:hidden">
                            <svg class="h-6 w-6 group-open:hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                            <svg class="hidden h-6 w-6 group-open:block" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" /></svg>
                        </span>
                    </summary>

                    <div class="{{ $panel }} absolute right-0 z-40 mt-2 w-72 max-w-[calc(100vw-2rem)] p-2">
                        <div class="{{ $line }} mb-2 border-b px-3 pb-3 pt-2">
                            <p class="{{ $ink }} truncate font-bold">{{ $user->name }}</p>
                            <p class="{{ $muted }} truncate text-xs">{{ $user->email }}</p>
                        </div>

                        {{-- Mobile navigation --}}
                        <nav aria-label="Mobile navigation" class="{{ $line }} mb-2 flex flex-col gap-1 border-b pb-2 lg:hidden">
                            @foreach ($navLinks as $item)
                                <a href="{{ route($item['route']) }}" class="{{ $navLink }} w-full px-3 py-2.5" @if ($item['active']) aria-current="page" @endif>{{ $item['text'] }}</a>
                            @endforeach
                        </nav>

                        {{-- Theme toggle (phone only; on desktop it sits in the header) --}}
                        <div class="flex items-center justify-between gap-2 px-3 py-1 text-sm lg:hidden">
                            <span>Theme</span>
                            <x-partials.theme-toggle />
                        </div>

                        <a href="{{ route('citizen.identity-verification') }}" class="{{ $hover }} flex items-center justify-between gap-2 rounded-md px-3 py-2.5 text-sm">
                            <span>Identity verification</span>
                            <span id="identity-status-badge" data-identity-status="{{ $user->identityStatus() }}" class="{{ $badgeClass }} rounded-full px-2 py-0.5 text-xs font-bold">{{ $badgeText }}</span>
                        </a>
                        <a href="{{ route('citizen.profile') }}" class="{{ $hover }} block rounded-md px-3 py-2.5 text-sm">My profile</a>

                        <form method="POST" action="{{ route('logout') }}" class="{{ $line }} mt-2 border-t pt-2">
                            @csrf
                            <button class="text-(--red-600) dark:text-(--red-500) w-full rounded-md px-3 py-2.5 text-left text-sm font-semibold hover:bg-[color-mix(in_srgb,var(--red-500)_12%,transparent)]">Sign out</button>
                        </form>
                    </div>
                </details>
            </div>
        </div>
    </header>

    <main id="citizen-page-content" @class([
        'min-h-screen w-full flex-1',
        'mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8' => !View::hasSection('flush'),
    ])>
        @yield('content')
    </main>

    <x-partials.c-footer />

    <script>
        const $ = (id) => document.getElementById(id);
        const bell = $('notification-menu');
        const menus = [$('user-menu'), bell].filter(Boolean);

        // Opening the bell marks everything as read
        bell?.addEventListener('toggle', async () => {
            const form = $('mark-all-form');
            if (!bell.open || !form) return;
            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: new FormData(form),
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('Unable to mark notifications as read.');
                $('notification-count')?.remove();
                $('notification-subtitle').textContent = "You're all caught up.";
                document.querySelectorAll('[data-unread-dot]').forEach((d) => d.remove());
                form.remove();
            } catch (e) { console.error(e); }
        });

        // Refresh page content every 5s without a full reload
        setInterval(async () => {
            const editing = document.activeElement?.matches('input, textarea, select, [contenteditable="true"]');
            const dirty = [...document.querySelectorAll('form input, form textarea, form select')]
                .some((f) => f.type === 'file' ? f.files.length : f.value !== f.defaultValue);
            if (document.hidden || editing || dirty) return;

            try {
                const res = await fetch(location.href, { cache: 'no-store', headers: { Accept: 'text/html' }, credentials: 'same-origin' });
                if (!res.ok) throw new Error('Unable to synchronize the citizen page.');
                const fresh = new DOMParser().parseFromString(await res.text(), 'text/html');

                ['citizen-page-content', 'notification-menu'].forEach((id) => {
                    const next = fresh.getElementById(id), cur = $(id);
                    if (next && cur && next.innerHTML !== cur.innerHTML) cur.innerHTML = next.innerHTML;
                });

                const next = fresh.getElementById('identity-status-badge'), badge = $('identity-status-badge');
                if (next && badge) {
                    badge.className = next.className;
                    badge.dataset.identityStatus = next.dataset.identityStatus;
                    badge.textContent = next.textContent;
                }
            } catch (e) { console.error(e); }
        }, 5000);

        // Outside click closes menus; opening one closes the other
        document.addEventListener('click', (e) => menus.forEach((m) => m.contains(e.target) || m.removeAttribute('open')));
        menus.forEach((m) => m.addEventListener('toggle', () => m.open && menus.forEach((o) => o !== m && o.removeAttribute('open'))));

        // Escape closes menus
        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape') return;
            menus.filter((m) => m.open).forEach((m) => { m.removeAttribute('open'); m.querySelector('summary').focus(); });
        });
    </script>
</body>

</html>