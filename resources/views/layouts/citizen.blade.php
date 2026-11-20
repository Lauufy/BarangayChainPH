<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-pt-24 scroll-smooth motion-reduce:scroll-auto">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#173266" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#070b24" media="(prefers-color-scheme: dark)">
    <title>@yield('title', 'Citizen Portal') | Barangay San Luis</title>
    <link rel="icon" type="image/png" href="{{ asset('img/branding/barangaychain-lg1.png') }}">

    {{-- Apply the saved theme before first paint --}}
    <script>
        try {
            const t = localStorage.getItem('theme');
            document.documentElement.classList.toggle('dark', t ? t === 'dark' : matchMedia('(prefers-color-scheme: dark)')
                .matches);
        } catch (e) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body
    class="text-(--blue-700) dark:bg-(--blue-850) bg-(--off-white) dark:text-(--off-white) flex min-h-screen flex-col overflow-x-clip font-sans antialiased transition-colors duration-300">

    <a href="#citizen-page-content"
        class="text-(--blue-700) focus:z-60 sr-only rounded-sm bg-white px-4 py-2 text-sm font-semibold focus:not-sr-only focus:fixed focus:left-4 focus:top-4">
        Skip to main content
    </a>

    {{-- Header --}}
    <header
        class="border-(--blue-700)/10 text-(--blue-700) dark:bg-(--blue-850)/96 bg-white/96 dark:text-(--off-white) sticky top-0 z-50 border-b backdrop-blur-md dark:border-white/10">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-5 py-3.5 sm:px-8">
            <x-partials.logo />

            {{-- Desktop navigation --}}
            <nav aria-label="Main navigation" class="hidden items-center gap-6 lg:flex">
                <a href="{{ route('citizen.dashboard') }}"
                    class="focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) relative whitespace-nowrap rounded-sm py-1 text-sm font-medium transition-colors after:absolute after:inset-x-0 after:-bottom-0.5 after:h-0.5 after:origin-left after:scale-x-0 after:bg-current after:transition-transform hover:after:scale-x-100 aria-[current=page]:font-semibold aria-[current=page]:after:scale-x-100 dark:after:bg-(--celadon-500)"
                    @if (request()->routeIs('citizen.dashboard')) aria-current="page" @endif>Home</a>
                <a href="{{ route('citizen.services') }}"
                    class="focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) relative whitespace-nowrap rounded-sm py-1 text-sm font-medium transition-colors after:absolute after:inset-x-0 after:-bottom-0.5 after:h-0.5 after:origin-left after:scale-x-0 after:bg-current after:transition-transform hover:after:scale-x-100 aria-[current=page]:font-semibold aria-[current=page]:after:scale-x-100 dark:after:bg-(--celadon-500)"
                    @if (request()->routeIs('citizen.services')) aria-current="page" @endif>Services</a>
                <a href="{{ route('citizen.applications') }}"
                    class="focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) relative whitespace-nowrap rounded-sm py-1 text-sm font-medium transition-colors after:absolute after:inset-x-0 after:-bottom-0.5 after:h-0.5 after:origin-left after:scale-x-0 after:bg-current after:transition-transform hover:after:scale-x-100 aria-[current=page]:font-semibold aria-[current=page]:after:scale-x-100 dark:after:bg-(--celadon-500)"
                    @if (request()->routeIs('citizen.applications*')) aria-current="page" @endif>Applications</a>
                <a href="{{ route('citizen.documents') }}"
                    class="focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) relative whitespace-nowrap rounded-sm py-1 text-sm font-medium transition-colors after:absolute after:inset-x-0 after:-bottom-0.5 after:h-0.5 after:origin-left after:scale-x-0 after:bg-current after:transition-transform hover:after:scale-x-100 aria-[current=page]:font-semibold aria-[current=page]:after:scale-x-100 dark:after:bg-(--celadon-500)"
                    @if (request()->routeIs('citizen.documents*')) aria-current="page" @endif>Documents</a>
            </nav>

            <div class="flex shrink-0 items-center gap-1 sm:gap-3">
                <div class="hidden lg:block"><x-partials.theme-toggle /></div>

                {{-- Notification bell (hidden for guests and on print/preview pages) --}}
                @auth
                    @unless (request()->routeIs(
                            'permits.verify',
                            'citizen.applications.show',
                            'citizen.applications.print',
                            'citizen.certificate-of-indigency.preview',
                            'citizen.barangay-clearance.preview',
                            'citizen.certificate-of-residency.preview'))
                        <details id="notification-menu" class="sm:relative">
                            <summary aria-label="Open notifications"
                                class="hover:bg-(--blue-700)/10 dark:hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) relative flex size-11 cursor-pointer list-none items-center justify-center rounded-sm [&::-webkit-details-marker]:hidden">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.6"
                                    viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                                </svg>
                                @if (auth()->user()->unreadNotifications()->count() > 0)
                                    <span id="notification-count"
                                        class="bg-(--red-600) text-(--white) absolute right-0.5 top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full px-1 text-[11px] font-bold leading-none">{{ auth()->user()->unreadNotifications()->count() > 9 ? '9+' : auth()->user()->unreadNotifications()->count() }}</span>
                                @endif
                            </summary>

                            <div
                                class="rounded-xl border border-(--blue-700)/10 bg-white shadow-xl dark:border-white/15 dark:bg-(--blue-850)/97 absolute inset-x-4 top-full z-40 mt-2 sm:inset-x-auto sm:right-0 sm:w-96">
                                <div class="border-(--blue-700)/10 border-b px-4 py-3 dark:border-white/10">
                                    <p class="text-(--blue-700) dark:text-(--off-white) text-sm font-bold">Notifications
                                    </p>
                                    <p id="notification-subtitle"
                                        class="text-(--blue-700)/70 dark:text-(--off-white)/70 mt-0.5 text-xs">
                                        @if (auth()->user()->unreadNotifications()->count() > 0)
                                            You have {{ auth()->user()->unreadNotifications()->count() }} new
                                            {{ str('notification')->plural(auth()->user()->unreadNotifications()->count()) }}.
                                        @else
                                            You're all caught up.
                                        @endif
                                    </p>
                                </div>

                                <div class="max-h-96 overflow-y-auto px-4 py-4">
                                    @forelse (auth()->user()->notifications()->latest()->limit(10)->get() as $n)
                                        <div class="relative pb-5 last:pb-0">
                                            @unless ($loop->last)
                                                <span
                                                    class="bg-(--blue-700)/15 left-4.5 absolute bottom-1 top-10 w-px -translate-x-1/2 dark:bg-white/15"
                                                    aria-hidden="true"></span>
                                            @endunless

                                            <form method="POST" action="{{ route('citizen.notifications.read', $n->id) }}">
                                                @csrf
                                                <button type="submit"
                                                    class="focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) group/item flex w-full items-start gap-3 rounded-sm text-left">

                                                    @switch($n->data['status'] ?? 'info')
                                                        @case('verified')
                                                            <span
                                                                class="bg-[color-mix(in_srgb,var(--green-500)_18%,transparent)] text-(--green-600) dark:text-(--green-300) flex h-9 w-9 shrink-0 items-center justify-center rounded-full">
                                                                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor"
                                                                    stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                                </svg>
                                                            </span>
                                                        @break

                                                        @case('rejected')
                                                            <span
                                                                class="bg-[color-mix(in_srgb,var(--red-500)_15%,transparent)] text-(--red-600) dark:text-(--red-500) flex h-9 w-9 shrink-0 items-center justify-center rounded-full">
                                                                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor"
                                                                    stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                                                </svg>
                                                            </span>
                                                        @break

                                                        @default
                                                            <span
                                                                class="bg-[color-mix(in_srgb,var(--amber-500)_18%,transparent)] text-(--amber-500) flex h-9 w-9 shrink-0 items-center justify-center rounded-full">
                                                                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor"
                                                                    stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                                                                </svg>
                                                            </span>
                                                    @endswitch

                                                    <span class="min-w-0 flex-1">
                                                        <span class="flex items-start justify-between gap-3">
                                                            <span
                                                                class="text-(--blue-700) dark:text-(--off-white) text-[13px] font-semibold group-hover/item:underline">
                                                                {{ $n->data['title'] ?? 'Notification' }}
                                                                @if ($n->read_at === null)
                                                                    <span data-unread-dot
                                                                        class="bg-(--blue-700) dark:bg-(--celadon-500) ml-1 inline-block h-2 w-2 rounded-full align-middle"><span
                                                                            class="sr-only">Unread</span></span>
                                                                @endif
                                                            </span>
                                                            <span
                                                                class="text-(--blue-700)/70 dark:text-(--off-white)/70 shrink-0 whitespace-nowrap text-[11px]">{{ $n->created_at->diffForHumans() }}</span>
                                                        </span>
                                                        <span
                                                            class="text-(--blue-700)/70 dark:text-(--off-white)/70 mt-0.5 block text-xs">{{ $n->data['message'] ?? '' }}</span>
                                                    </span>
                                                </button>
                                            </form>
                                        </div>
                                    @empty
                                        <p
                                            class="text-(--blue-700)/70 dark:text-(--off-white)/70 px-2 py-8 text-center text-sm">
                                            No notifications yet.</p>
                                    @endforelse
                                </div>

                                <div
                                    class="border-(--blue-700)/10 flex items-center justify-between gap-3 border-t px-4 py-3 dark:border-white/10">
                                    @if (auth()->user()->unreadNotifications()->count() > 0)
                                        <form id="mark-all-form" method="POST"
                                            action="{{ route('citizen.notifications.read-all') }}">
                                            @csrf
                                            <button
                                                class="focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) rounded-sm text-sm font-semibold text-(--blue-700) underline underline-offset-4 dark:text-(--celadon-500)">Mark
                                                all as read</button>
                                        </form>
                                    @endif
                                    <a href="{{ route('citizen.notifications') }}"
                                        class="focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) rounded-sm text-sm font-semibold text-(--blue-700) underline underline-offset-4 dark:text-(--celadon-500) ml-auto">View
                                        all</a>
                                </div>
                            </div>
                        </details>
                    @endunless
                @endauth

                {{-- User menu (logged-in users) or login link (guests) --}}
                @auth
                    <details id="user-menu" class="group relative shrink-0">
                        <summary aria-label="Open user menu"
                            class="hover:bg-(--blue-700)/10 dark:hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) flex cursor-pointer list-none items-center gap-3 rounded-sm p-1.5 [&::-webkit-details-marker]:hidden">
                            <span class="hidden text-right lg:block">
                                <span
                                    class="text-(--blue-700) dark:text-(--off-white) block max-w-40 truncate text-sm font-bold">{{ auth()->user()->name }}</span>
                                <span
                                    class="text-(--blue-700)/70 dark:text-(--off-white)/70 block text-xs">Citizen</span>
                            </span>

                            @if (auth()->user()->citizenProfile?->photo_path)
                                <img src="{{ route('citizen.profile.photo.show') }}" alt=""
                                    class="hidden h-10 w-10 rounded-full object-cover lg:block">
                            @else
                                <span
                                    class="bg-(--blue-700) text-(--off-white) dark:bg-(--celadon-500) dark:text-(--blue-850) hidden h-10 w-10 items-center justify-center rounded-full font-bold lg:flex">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            @endif

                            {{-- Mobile menu icon --}}
                            <span class="grid size-8 place-items-center lg:hidden">
                                <svg class="h-6 w-6 group-open:hidden" fill="none" stroke="currentColor"
                                    stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                                <svg class="hidden h-6 w-6 group-open:block" fill="none" stroke="currentColor"
                                    stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                                </svg>
                            </span>
                        </summary>

                        <div
                            class="rounded-xl border border-(--blue-700)/10 bg-white shadow-xl dark:border-white/15 dark:bg-(--blue-850)/97 absolute right-0 z-40 mt-2 w-72 max-w-[calc(100vw-2rem)] p-2">
                            <div class="border-(--blue-700)/10 mb-2 border-b px-3 pb-3 pt-2 dark:border-white/10">
                                <p class="text-(--blue-700) dark:text-(--off-white) truncate font-bold">
                                    {{ auth()->user()->name }}</p>
                                <p class="text-(--blue-700)/70 dark:text-(--off-white)/70 truncate text-xs">
                                    {{ auth()->user()->email }}</p>
                            </div>

                            {{-- Mobile navigation --}}
                            <nav aria-label="Mobile navigation"
                                class="border-(--blue-700)/10 mb-2 flex flex-col gap-1 border-b pb-2 lg:hidden dark:border-white/10">
                                <a href="{{ route('citizen.dashboard') }}"
                                    class="focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) hover:bg-(--blue-700)/10 dark:hover:bg-white/10 block w-full rounded-sm px-3 py-2.5 text-sm font-medium aria-[current=page]:bg-(--blue-700)/10 aria-[current=page]:font-semibold dark:aria-[current=page]:bg-white/10"
                                    @if (request()->routeIs('citizen.dashboard')) aria-current="page" @endif>Home</a>
                                <a href="{{ route('citizen.services') }}"
                                    class="focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) hover:bg-(--blue-700)/10 dark:hover:bg-white/10 block w-full rounded-sm px-3 py-2.5 text-sm font-medium aria-[current=page]:bg-(--blue-700)/10 aria-[current=page]:font-semibold dark:aria-[current=page]:bg-white/10"
                                    @if (request()->routeIs('citizen.services')) aria-current="page" @endif>Services</a>
                                <a href="{{ route('citizen.applications') }}"
                                    class="focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) hover:bg-(--blue-700)/10 dark:hover:bg-white/10 block w-full rounded-sm px-3 py-2.5 text-sm font-medium aria-[current=page]:bg-(--blue-700)/10 aria-[current=page]:font-semibold dark:aria-[current=page]:bg-white/10"
                                    @if (request()->routeIs('citizen.applications*')) aria-current="page" @endif>Applications</a>
                                <a href="{{ route('citizen.documents') }}"
                                    class="focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) hover:bg-(--blue-700)/10 dark:hover:bg-white/10 block w-full rounded-sm px-3 py-2.5 text-sm font-medium aria-[current=page]:bg-(--blue-700)/10 aria-[current=page]:font-semibold dark:aria-[current=page]:bg-white/10"
                                    @if (request()->routeIs('citizen.documents*')) aria-current="page" @endif>Documents</a>
                            </nav>

                            {{-- Theme toggle (phone only; on desktop it sits in the header) --}}
                            <div class="flex items-center justify-between gap-2 px-3 py-1 text-sm lg:hidden">
                                <span>Theme</span>
                                <x-partials.theme-toggle />
                            </div>

                            <a href="{{ route('citizen.identity-verification') }}"
                                class="hover:bg-(--blue-700)/10 dark:hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) flex items-center justify-between gap-2 rounded-sm px-3 py-2.5 text-sm">
                                <span>Identity verification</span>

                                @if (auth()->user()->isIdentityVerified())
                                    <span id="identity-status-badge"
                                        data-identity-status="{{ auth()->user()->identityStatus() }}"
                                        class="bg-[color-mix(in_srgb,var(--green-500)_18%,transparent)] text-(--green-700) dark:text-(--green-300) rounded-full px-2 py-0.5 text-xs font-bold">Verified</span>
                                @elseif (auth()->user()->identityStatus() === 'pending')
                                    <span id="identity-status-badge"
                                        data-identity-status="{{ auth()->user()->identityStatus() }}"
                                        class="bg-[color-mix(in_srgb,var(--amber-500)_20%,transparent)] text-(--amber-500) rounded-full px-2 py-0.5 text-xs font-bold">Pending</span>
                                @else
                                    <span id="identity-status-badge"
                                        data-identity-status="{{ auth()->user()->identityStatus() }}"
                                        class="bg-[color-mix(in_srgb,var(--red-500)_15%,transparent)] text-(--red-600) dark:text-(--red-500) rounded-full px-2 py-0.5 text-xs font-bold">Required</span>
                                @endif
                            </a>
                            <a href="{{ route('citizen.profile') }}"
                                class="hover:bg-(--blue-700)/10 dark:hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) block rounded-sm px-3 py-2.5 text-sm">Account</a>

                            <form method="POST" action="{{ route('logout') }}"
                                class="border-(--blue-700)/10 mt-2 border-t pt-2 dark:border-white/10">
                                @csrf
                                <button
                                    class="text-(--red-600) dark:text-(--red-500) focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) w-full rounded-sm px-3 py-2.5 text-left text-sm font-semibold hover:bg-[color-mix(in_srgb,var(--red-500)_12%,transparent)]">Sign
                                    out</button>
                            </form>
                        </div>
                    </details>
                @else
                    <a href="{{ route('login') }}"
                        class="focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) rounded-sm px-2 py-1.5 text-sm font-semibold hover:underline">Sign
                        in</a>
                @endauth
            </div>
        </div>
    </header>

    @hasSection('flush')
        <main id="citizen-page-content" class="min-h-screen w-full flex-1">
        @else
            <main id="citizen-page-content"
                class="min-h-screen w-full flex-1 mx-auto max-w-7xl px-5 py-8 sm:px-8 sm:py-12">
    @endif
    <x-feedbacks.toast />

    @yield('content')
    </main>

    @hasSection('footer')
        @yield('footer')
    @else
        @include('components.partials.section-footer')
    @endif

    {{-- Reload when the browser restores this page from the back/forward cache --}}
    <script>
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>

    {{-- Shared live-sync poller --}}
    @include('components.partials.live-sync')

    <script>
        const $ = (id) => document.getElementById(id);
        const bell = $('notification-menu');
        const menus = [$('user-menu'), bell].filter(Boolean);

        // ---------- Opening the bell marks everything as read ----------
        bell?.addEventListener('toggle', async () => {
            const form = $('mark-all-form');
            if (!bell.open || !form) return;

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: new FormData(form),
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('Unable to mark notifications as read.');

                $('notification-count')?.remove();
                $('notification-subtitle').textContent = "You're all caught up.";
                document.querySelectorAll('[data-unread-dot]').forEach((d) => d.remove());
                form.remove();
            } catch (e) {
                console.error(e);
            }
        });

        // ---------- Live refresh: only when the server reports a change ----------
        function swap(fresh, current) {
            if (!fresh || !current) return;
            // Do not replace the bell panel while the user is reading it
            if (current.tagName === 'DETAILS' && current.open) return;
            if (current.id === 'citizen-page-content' &&
                current.querySelector('dialog[open], [role="dialog"]:not(.hidden), [data-modal]:not(.hidden)')) return;

            if (fresh.innerHTML !== current.innerHTML) {
                current.innerHTML = fresh.innerHTML;

                if (current.id === 'citizen-page-content') {
                    document.dispatchEvent(new CustomEvent('citizen:content-swapped'));
                }
            }
        }

        function hasUnsavedInput() {
            return [...document.querySelectorAll('form input, form textarea, form select')]
                .some((f) => f.type === 'file' ? f.files.length : f.value !== f.defaultValue);
        }

        @auth
        async function refreshContent() {
            const res = await fetch(location.href, {
                cache: 'no-store',
                headers: {
                    Accept: 'text/html'
                },
                credentials: 'same-origin'
            });

            // Session ended (e.g. signed out in another tab): go to the login page
            if (res.redirected) {
                location.reload();
                return;
            }
            if (!res.ok) throw new Error('Unable to synchronize the citizen page.');

            const fresh = new DOMParser().parseFromString(await res.text(), 'text/html');

            ['citizen-page-content', 'notification-menu'].forEach((id) => swap(fresh.getElementById(id), $(id)));

            const next = fresh.getElementById('identity-status-badge'),
                badge = $('identity-status-badge');
            if (next && badge) {
                badge.className = next.className;
                badge.dataset.identityStatus = next.dataset.identityStatus;
                badge.textContent = next.textContent;
            }
        }

        startLiveSync({
            url: "{{ route('sync.version', ['scope' => 'citizen']) }}",
            version: @json(\App\Support\SyncVersion::current(auth()->id(), 'citizen')),
            refresh: refreshContent,
            hasUnsavedInput,
        });
        @endauth

        // ---------- Outside click closes menus; opening one closes the other ----------
        document.addEventListener('click', (e) => menus.forEach((m) => m.contains(e.target) || m.removeAttribute('open')));
        menus.forEach((m) => m.addEventListener('toggle', () => m.open && menus.forEach((o) => o !== m && o.removeAttribute(
            'open'))));

        // ---------- Escape closes menus ----------
        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape') return;
            menus.filter((m) => m.open).forEach((m) => {
                m.removeAttribute('open');
                m.querySelector('summary').focus();
            });
        });
    </script>
</body>

</html>