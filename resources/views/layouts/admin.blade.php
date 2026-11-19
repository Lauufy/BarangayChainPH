@php
    $user = auth()->user();
    $admin = $user->isAdmin();
    $can = fn($permission) => $user->hasPermission($permission);

    $notifications = $user->notifications()->latest()->limit(10)->get();
    $unread = $user->unreadNotifications()->count();

    // Notification status => [icon box classes, icon path]
    $notificationStyles = [
        'verified' => [
            'bg-[color-mix(in_srgb,var(--green-500)_16%,transparent)] text-(--green-600) dark:text-(--green-300)',
            'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20ZM9 12l2 2 4-4',
        ],
        'rejected' => [
            'bg-[color-mix(in_srgb,var(--red-500)_14%,transparent)] text-(--red-600) dark:text-(--red-500)',
            'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20ZM15 9l-6 6M9 9l6 6',
        ],
        'info' => [
            'bg-[color-mix(in_srgb,var(--amber-500)_16%,transparent)] text-(--amber-500)',
            'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20ZM12 16v-4M12 8h.01',
        ],
    ];

    // Sidebar sections. Solid icons: class "cut" = cut-out shape, "cutl" = cut-out line.
    $nav = [
        'Overview' => [
            [
                'href' => '/admin/dashboard',
                'match' => 'admin/dashboard',
                'label' => 'Dashboard',
                'icon' => '<path d="M12 2a10 10 0 1 0 10 10H12z"/><path d="M14 2.2V10h7.8A10 10 0 0 0 14 2.2z"/>',
                'show' => true,
            ],
        ],
        'Management' => [
            [
                'href' => '/admin/users',
                'match' => 'admin/users*',
                'label' => 'Users & Roles',
                'icon' =>
                    '<circle cx="9" cy="8" r="4"/><path d="M2 20a7 7 0 0 1 14 0z"/><circle cx="17" cy="9" r="3"/><path d="M17.5 14a4.5 4.5 0 0 1 4.5 4.5V20h-4.2a8.6 8.6 0 0 0-2-5 4.6 4.6 0 0 1 1.7-1z"/>',
                'show' => $admin,
            ],
            [
                'href' => '/admin/identity-verification',
                'match' => 'admin/identity-verification*',
                'label' => 'Identity Verification',
                'icon' =>
                    '<rect x="2" y="5" width="20" height="14" rx="3"/><circle class="cut" cx="8.5" cy="11" r="2"/><path class="cut" d="M5.5 16.5a3 3 0 0 1 6 0z"/><path class="cutl" d="M14.5 10h4M14.5 14h4"/>',
                'show' => $can('identity.review'),
            ],
            [
                'href' => '/admin/permits',
                'match' => 'admin/permits*',
                'label' => 'Permit Management',
                'icon' =>
                    '<rect x="4" y="4" width="16" height="18" rx="3"/><rect x="8" y="2" width="8" height="4" rx="1.5"/><path class="cutl" d="m9 14 2 2 4-4"/>',
                'show' => $can('permits.review'),
            ],
        ],
        'Documents & Verification' => [
            [
                'href' => '/admin/document-authentication',
                'match' => 'admin/document-authentication*',
                'label' => 'Document Authentication',
                'icon' =>
                    '<path d="M12 2 4 5v6c0 5 3.4 9.3 8 11 4.6-1.7 8-6 8-11V5z"/><path class="cutl" d="m8.5 12 2.5 2.5 4.5-4.5"/>',
                'show' => $can('documents.authenticate'),
            ],
        ],
        'Blockchain' => [
            [
                'href' => '/admin/blockchain-ledger',
                'match' => 'admin/blockchain-ledger*',
                'label' => 'Blockchain Ledger',
                'icon' => '<path d="M12 2 3 7v10l9 5 9-5V7z"/><path class="cutl" d="m3 7 9 5 9-5M12 12v10"/>',
                'show' => $can('blockchain.view'),
            ],
            [
                'href' => '/admin/smart-contracts',
                'match' => 'admin/smart-contracts*',
                'label' => 'Smart Contract Management',
                'icon' =>
                    '<path d="M6 2h8l6 6v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"/><path class="cutl" d="m10 12-2 2.5 2 2.5M14 12l2 2.5-2 2.5"/>',
                'show' => $can('contracts.view'),
            ],
        ],
        'Communication' => [
            [
                'href' => '/admin/tickets',
                'match' => 'admin/tickets*',
                'label' => 'Ticket Messages',
                'icon' =>
                    '<path d="M5 3h14a3 3 0 0 1 3 3v9a3 3 0 0 1-3 3H10l-5 4v-4a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3z"/><circle class="cut" cx="8" cy="10.5" r="1.2"/><circle class="cut" cx="12" cy="10.5" r="1.2"/><circle class="cut" cx="16" cy="10.5" r="1.2"/>',
                'show' => $can('tickets.reply'),
            ],
        ],
        'Insights & Security' => [
            [
                'href' => '/admin/reports',
                'match' => 'admin/reports*',
                'label' => 'Reports & Analytics',
                'icon' =>
                    '<rect x="3" y="3" width="18" height="18" rx="3"/><path class="cutl" d="M8 17v-4M12 17V8M16 17v-6"/>',
                'show' => $can('reports.view'),
            ],
            [
                'href' => '/admin/audit-logs',
                'match' => 'admin/audit-logs*',
                'label' => 'Audit Logs',
                'icon' => '<circle cx="12" cy="12" r="10"/><path class="cutl" d="M12 7v5l3.5 2"/>',
                'show' => $can('audit.view'),
            ],
        ],
    ];

    // Shared class strings
    $ink = 'text-(--gray-900) dark:text-(--gray-50)';
    $muted = 'text-(--gray-500) dark:text-(--gray-400)';
    $line = 'border-(--gray-200) dark:border-white/10';
    $link = 'text-xs font-semibold text-(--blue-700) hover:underline dark:text-(--blue-300)';

    $hoverSoft = 'hover:bg-(--gray-100) hover:text-(--gray-900) dark:hover:bg-white/10 dark:hover:text-(--white)';

    $menuButton =
        'list-none cursor-pointer rounded-lg outline-none transition-colors duration-200 focus-visible:bg-(--gray-100) dark:focus-visible:bg-white/10 [&::-webkit-details-marker]:hidden ' .
        $hoverSoft;

    $menuPanel =
        'absolute right-0 z-50 mt-3 max-w-[calc(100vw-2rem)] rounded-xl border border-(--gray-200) bg-(--white) shadow-xl shadow-black/10 dark:border-white/10 dark:bg-(--gray-800)';

    // Outline icon attributes for header and menu icons
    $svg =
        'fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - BarangayChain PH</title>
    <link rel="icon" type="image/png" href="{{ asset('img/branding/barangaychain-lg2.png') }}">

    {{-- Apply the saved theme and sidebar state before first paint (no flicker) --}}
    <script>
        try {
            const theme = localStorage.getItem('theme');
            const dark = theme ? theme === 'dark' : matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', dark);

            if (localStorage.getItem('admin-sidebar') === 'collapsed') {
                document.documentElement.dataset.sidebar = 'collapsed';
            }
        } catch (e) {}
    </script>

    <style>
        :root {
            --sb-width: 19rem;
            --sb-bg: #070b24;
        }

        html[data-sidebar="collapsed"] {
            --sb-width: 5rem;
        }

        .admin-sidebar {
            width: var(--sb-width);
            background: var(--sb-bg);
            transition: width .25s ease;
            scrollbar-width: none;
        }

        .admin-sidebar::-webkit-scrollbar {
            width: 0;
        }

        .admin-main {
            transition: margin-left .25s ease;
        }

        @media (min-width: 768px) {
            .admin-main {
                margin-left: var(--sb-width);
            }
        }

        /* Solid sidebar icons: cut-outs use the sidebar color */
        .sb-icon .cut {
            fill: var(--sb-bg);
        }

        .sb-icon .cutl {
            fill: none;
            stroke: var(--sb-bg);
            stroke-width: 1.9;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* Logo icon: hidden when expanded, shown when collapsed */
        .sb-logo {
            display: none;
        }

        html[data-sidebar="collapsed"] .sb-logo {
            display: block;
        }

        /* Collapsed icon rail */
        html[data-sidebar="collapsed"] .sb-label {
            display: none;
        }

        html[data-sidebar="collapsed"] .sb-head {
            flex-direction: column;
            justify-content: center;
            gap: .75rem;
            padding-inline: 0;
        }

        html[data-sidebar="collapsed"] .sb-link {
            justify-content: center;
            gap: 0;
            padding-inline: 0;
        }

        html[data-sidebar="collapsed"] .sb-section-label {
            font-size: 0;
            line-height: 0;
            height: 1px;
            padding: 0;
            margin: 0 .75rem 1rem;
            background: rgba(255, 255, 255, .08);
        }

        html[data-sidebar="collapsed"] .sb-toggle-icon {
            transform: scaleX(-1);
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body
    class="bg-(--gray-100) text-(--gray-800) dark:bg-(--black-700) dark:text-(--gray-200) h-screen overflow-hidden font-sans antialiased transition-colors duration-300">

    <x-feedbacks.t-confirmation />

    <div class="flex h-screen overflow-hidden">

        {{-- ========== Sidebar ========== --}}
        <aside
            class="admin-sidebar text-(--white) fixed inset-y-0 left-0 z-50 hidden overflow-y-auto overflow-x-hidden border-r border-white/5 md:block">

            {{-- Brand + toggle --}}
            <div class="sb-head flex min-h-20 items-center justify-between gap-2 border-b border-white/5 px-5 py-4">

                <a href="/admin/dashboard" class="flex min-w-0 items-center outline-none">
                    {{-- Icon only: visible when collapsed --}}
                    <img src="{{ asset('img/branding/barangaychain-lg1.png') }}" alt="BarangayChain PH"
                        class="sb-logo h-10 w-10 shrink-0 object-contain">

                    {{-- Full logo: visible when expanded --}}
                    <img src="{{ asset('img/branding/brgychainlogo.png') }}" alt="BarangayChain PH"
                        class="sb-label h-10 w-auto shrink-0 object-contain brightness-0 invert">
                </a>

                <button type="button" id="admin-sidebar-toggle" aria-label="Toggle sidebar" aria-expanded="true"
                    title="Toggle sidebar"
                    class="hover:text-(--white) focus-visible:text-(--white) flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-[#8b93b8] outline-none transition-colors duration-200 hover:bg-white/5 focus-visible:bg-white/5">
                    <svg class="sb-toggle-icon h-5 w-5 transition-transform" viewBox="0 0 24 24"
                        {!! $svg !!}>
                        <rect x="3" y="3" width="18" height="18" rx="2" />
                        <path d="M9 3v18" />
                    </svg>
                </button>
            </div>

            {{-- Links --}}
            <nav class="space-y-5 px-3 py-5">
                @foreach ($nav as $section => $links)
                    @php $links = collect($links)->where('show', true); @endphp
                    @continue($links->isEmpty())

                    <div>
                        <p
                            class="sb-section-label mb-2 px-3 text-[11px] font-medium uppercase tracking-wider text-[#8b93b8]">
                            {{ $section }}</p>
                        <div class="space-y-0.5">
                            @foreach ($links as $item)
                                <x-admin.nav-link :href="$item['href']" :match="$item['match']">
                                    <x-slot:icon>{!! $item['icon'] !!}</x-slot:icon>
                                    {{ $item['label'] }}
                                </x-admin.nav-link>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>
        </aside>

        {{-- ========== Main area ========== --}}
        <div class="admin-main flex h-screen min-w-0 flex-1 flex-col overflow-hidden">

            {{-- Header --}}
            <header
                class="{{ $line }} bg-(--white) dark:bg-(--blue-850) relative z-40 flex h-20 shrink-0 items-center justify-between border-b px-3 sm:px-6">

                <div class="min-w-0">
                    <x-partials.live-date class="hidden lg:flex" timezone="Asia/Manila" />
                </div>

                <div class="flex shrink-0 items-center gap-1 sm:gap-2">

                    <x-partials.theme-toggle />

                    {{-- Notifications --}}
                    <details id="admin-notification-menu" class="relative">
                        <summary aria-label="Notifications"
                            class="{{ $menuButton }} {{ $muted }} relative flex p-2.5">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" {!! $svg !!}>
                                <path d="M10.268 21a2 2 0 0 0 3.464 0" />
                                <path
                                    d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326" />
                            </svg>
                            @if ($unread)
                                <span id="admin-notification-count"
                                    class="bg-(--red-600) text-(--white) ring-(--white) dark:ring-(--gray-900) absolute right-0.5 top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full px-1 font-mono text-[10px] font-bold leading-none ring-2">
                                    {{ $unread > 9 ? '9+' : $unread }}
                                </span>
                            @endif
                        </summary>

                        <div class="{{ $menuPanel }} w-96 overflow-hidden">
                            <div class="{{ $line }} border-b px-5 py-4">
                                <p class="{{ $ink }} text-sm font-semibold">Notifications</p>
                                <p id="admin-notification-subtitle" class="{{ $muted }} mt-0.5 text-xs">
                                    {{ $unread ? "You have {$unread} new " . \Illuminate\Support\Str::plural('notification', $unread) . '.' : "You're all caught up." }}
                                </p>
                            </div>

                            <div class="max-h-96 overflow-y-auto px-5 py-4">
                                @forelse ($notifications as $notification)
                                    @php
                                        $status = $notification->data['status'] ?? 'info';
                                        [$iconBox, $iconPath] =
                                            $notificationStyles[$status] ?? $notificationStyles['info'];
                                    @endphp

                                    <div class="relative flex gap-3 pb-5 last:pb-0">
                                        @unless ($loop->last)
                                            <span
                                                class="left-4.5 bg-(--gray-200) absolute bottom-1 top-10 w-px -translate-x-1/2 dark:bg-white/10"
                                                aria-hidden="true"></span>
                                        @endunless

                                        <span
                                            class="{{ $iconBox }} relative flex h-9 w-9 shrink-0 items-center justify-center rounded-lg">
                                            <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" {!! $svg !!}>
                                                <path d="{{ $iconPath }}" />
                                            </svg>
                                        </span>

                                        <form method="POST"
                                            action="{{ route('admin.notifications.read', $notification->id) }}"
                                            class="min-w-0 flex-1">
                                            @csrf
                                            <button type="submit" class="group w-full text-left">
                                                <span class="flex items-start justify-between gap-3">
                                                    <span
                                                        class="{{ $ink }} text-[13px] font-semibold group-hover:underline">
                                                        {{ $notification->data['title'] ?? 'Notification' }}
                                                        @unless ($notification->read_at)
                                                            <span data-admin-unread-dot
                                                                class="bg-(--blue-600) dark:bg-(--blue-300) ml-1 inline-block h-2 w-2 rounded-full align-middle">
                                                                <span class="sr-only">Unread</span>
                                                            </span>
                                                        @endunless
                                                    </span>
                                                    <span
                                                        class="{{ $muted }} shrink-0 whitespace-nowrap font-mono text-[11px]">{{ $notification->created_at->diffForHumans() }}</span>
                                                </span>
                                                <span
                                                    class="{{ $muted }} mt-0.5 block text-xs">{{ $notification->data['message'] ?? '' }}</span>
                                            </button>
                                        </form>
                                    </div>
                                @empty
                                    <p class="{{ $muted }} px-2 py-8 text-center text-sm">No notifications yet.
                                    </p>
                                @endforelse
                            </div>

                            <div
                                class="{{ $line }} bg-(--gray-50) dark:bg-(--gray-900) flex items-center justify-between border-t px-5 py-3">
                                @if ($unread)
                                    <form id="admin-mark-all-form" method="POST"
                                        action="{{ route('admin.notifications.read-all') }}">
                                        @csrf
                                        <button class="{{ $link }}">Mark all as read</button>
                                    </form>
                                @endif
                                <a href="{{ route('admin.notifications.index') }}"
                                    class="{{ $link }} ml-auto">View all</a>
                            </div>
                        </div>
                    </details>

                    <div class="{{ $line }} h-8 border-l"></div>

                    {{-- Profile menu --}}
                    <details id="admin-user-menu" class="group/user relative">
                        <summary class="{{ $menuButton }} flex items-center gap-3 py-1.5 pl-1.5 pr-3 sm:pr-4">
                            @if ($user->employeeProfile?->photo_path)
                                <img src="{{ route('admin.profile.photo.show') }}?v={{ $user->updated_at?->timestamp }}"
                                    alt=""
                                    class="ring-(--gray-200) h-9 w-9 rounded-full object-cover ring-2 dark:ring-white/10">
                            @else
                                <span
                                    class="text-(--white) bg-(--gray-800) flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </span>
                            @endif

                            <span class="hidden text-left sm:block">
                                <span
                                    class="{{ $ink }} block max-w-40 truncate text-sm font-semibold leading-tight">{{ $user->name }}</span>
                                <span
                                    class="{{ $muted }} block font-mono text-[10px] font-semibold uppercase tracking-wide">{{ $admin ? 'Admin' : 'Staff' }}</span>
                            </span>

                            <svg class="{{ $muted }} hidden h-4 w-4 transition-transform group-open/user:rotate-180 sm:block"
                                viewBox="0 0 24 24" {!! $svg !!}>
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </summary>

                        <div class="{{ $menuPanel }} w-72 p-2">
                            <div class="{{ $line }} mb-2 border-b px-3 pb-3 pt-2">
                                <p class="{{ $ink }} truncate font-semibold">{{ $user->name }}</p>
                                <p class="{{ $muted }} truncate font-mono text-xs">{{ $user->email }}</p>
                            </div>

                            <a href="{{ route('admin.profile') }}"
                                class="{{ $hoverSoft }} text-(--gray-700) dark:text-(--gray-200) flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-200">
                                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" {!! $svg !!}>
                                    <circle cx="12" cy="8" r="5" />
                                    <path d="M20 21a8 8 0 0 0-16 0" />
                                </svg>
                                <span>My profile</span>
                            </a>

                            <form method="POST" action="{{ route('logout') }}"
                                class="{{ $line }} mt-2 border-t pt-2">
                                @csrf
                                <button type="submit"
                                    class="text-(--red-600) hover:bg-(--red-500)/10 dark:text-(--red-500) flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-semibold transition-colors duration-200">
                                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" {!! $svg !!}>
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                        <path d="m16 17 5-5-5-5" />
                                        <path d="M21 12H9" />
                                    </svg>
                                    <span>Sign out</span>
                                </button>
                            </form>
                        </div>
                    </details>
                </div>
            </header>

            {{-- Page content --}}
            <main id="admin-page-content"
                class="min-h-0 min-w-0 flex-1 overflow-y-auto px-4 pb-6 pt-6 sm:px-8 sm:pb-8">
                @yield('content')
            </main>
        </div>
    </div>

    <x-feedbacks.toast />

    {{-- Reload when the browser restores this page from the back/forward cache --}}
    <script>
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>

    <script>
        const root = document.documentElement;
        const $ = (id) => document.getElementById(id);
        const notificationMenu = $('admin-notification-menu');
        const userMenu = $('admin-user-menu');
        const sidebarToggle = $('admin-sidebar-toggle');

        // ---------- Sidebar (collapsed state is remembered) ----------
        function setSidebar(collapsed) {
            if (collapsed) {
                root.setAttribute('data-sidebar', 'collapsed');
            } else {
                root.removeAttribute('data-sidebar');
            }

            sidebarToggle?.setAttribute('aria-expanded', String(!collapsed));

            // Show the label as a tooltip when only the icon is visible
            document.querySelectorAll('.sb-link').forEach((link) => {
                if (collapsed) {
                    link.title = link.textContent.trim();
                } else {
                    link.removeAttribute('title');
                }
            });
        }

        setSidebar(root.dataset.sidebar === 'collapsed');

        sidebarToggle?.addEventListener('click', () => {
            const collapsed = root.dataset.sidebar !== 'collapsed';
            setSidebar(collapsed);
            try {
                localStorage.setItem('admin-sidebar', collapsed ? 'collapsed' : 'expanded');
            } catch (e) {}
        });

        // ---------- Opening the bell marks everything as read ----------
        notificationMenu?.addEventListener('toggle', async () => {
            const form = $('admin-mark-all-form');
            const badge = $('admin-notification-count');
            if (!notificationMenu.open || !form || !badge) return;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    },
                    body: new FormData(form),
                    credentials: 'same-origin',
                });
                if (!response.ok) throw new Error('Could not mark notifications as read.');

                badge.remove();
                form.remove();
                $('admin-notification-subtitle').textContent = "You're all caught up.";
                document.querySelectorAll('[data-admin-unread-dot]').forEach((dot) => dot.remove());
            } catch (error) {
                console.error(error);
            }
        });

        // ---------- Refresh page content every 5s without a full reload ----------
        // Comparable HTML of an element (chart canvases ignore their runtime size attributes)
        function signature(element) {
            const clone = element.cloneNode(true);
            clone.querySelectorAll('canvas').forEach((canvas) => {
                canvas.removeAttribute('style');
                canvas.removeAttribute('width');
                canvas.removeAttribute('height');
            });
            return clone.innerHTML;
        }

        // Replace the current element's content when the fresh copy differs
        function swap(fresh, current) {
            if (!fresh || !current || current.open) return;
            if (current.id === 'admin-page-content' &&
                current.querySelector('dialog[open], [role="dialog"]:not(.hidden), [data-modal]:not(.hidden)')) return;
            if (signature(fresh) !== signature(current)) {
                current.innerHTML = fresh.innerHTML;

                if (current.id === 'admin-page-content') {
                    document.dispatchEvent(new CustomEvent('admin:content-swapped'));
                }
            }
        }

        function hasUnsavedInput() {
            const fields = document.querySelectorAll(
                '#admin-page-content input:not([type=hidden]), #admin-page-content textarea');
            return [...fields].some((field) =>
                field.type === 'file' ? field.files.length > 0 : field.value !== field.defaultValue);
        }

        setInterval(async () => {
            if (document.hidden) return;
            if (document.activeElement?.matches('input, textarea, select, [contenteditable="true"]')) return;
            if (hasUnsavedInput()) return;

            try {
                const response = await fetch(location.href, {
                    cache: 'no-store',
                    headers: {
                        'Accept': 'text/html'
                    },
                    credentials: 'same-origin',
                });

                // Session ended (e.g. signed out in another tab): go to the login page
                if (response.redirected) {
                    location.reload();
                    return;
                }
                if (!response.ok) throw new Error('Could not refresh the page.');

                const fresh = new DOMParser().parseFromString(await response.text(), 'text/html');
                ['admin-page-content', 'admin-notification-menu', 'admin-user-menu']
                .forEach((id) => swap(fresh.getElementById(id), $(id)));
            } catch (error) {
                console.error(error);
            }
        }, 5000);

        // ---------- Close menus on outside click or Escape ----------
        const menus = [notificationMenu, userMenu];

        document.addEventListener('click', (event) => {
            menus.forEach((menu) => {
                if (menu && !menu.contains(event.target)) menu.removeAttribute('open');
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            menus.forEach((menu) => {
                if (!menu?.open) return;
                menu.removeAttribute('open');
                menu.querySelector('summary')?.focus();
            });
        });
    </script>
</body>

</html>