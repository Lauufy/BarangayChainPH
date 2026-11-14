@php
    $user = auth()->user();
    $admin = $user->isAdmin();
    $can = fn($permission) => $user->hasPermission($permission);

    $notifications = $user->notifications()->latest()->limit(10)->get();
    $unread = $user->unreadNotifications()->count();

    // Notification status => [icon box classes, icon path]
    $notificationStyles = [
        'verified' => [
            'bg-[color-mix(in_srgb,var(--green-500)_18%,transparent)] text-(--green-600) dark:text-(--green-300)',
            'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        ],
        'rejected' => [
            'bg-[color-mix(in_srgb,var(--red-500)_15%,transparent)] text-(--red-600) dark:text-(--red-500)',
            'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
        ],
        'info' => [
            'bg-[color-mix(in_srgb,var(--amber-500)_18%,transparent)] text-(--amber-500)',
            'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0',
        ],
    ];

    // Sidebar sections. "show" decides if the current user can see the link.
    $nav = [
        'Overview' => [
            [
                'href' => '/admin/dashboard',
                'match' => 'admin/dashboard',
                'label' => 'Dashboard',
                'icon' =>
                    '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
                'show' => true,
            ],
        ],
        'Management' => [
            [
                'href' => '/admin/users',
                'match' => 'admin/users*',
                'label' => 'Users & Roles',
                'icon' =>
                    '<path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
                'show' => $admin,
            ],
            [
                'href' => '/admin/identity-verification',
                'match' => 'admin/identity-verification*',
                'label' => 'Identity Verification',
                'icon' =>
                    '<rect x="2" y="5" width="20" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M6.17 15a3 3 0 0 1 5.66 0"/><path d="M15 10h3M15 14h3"/>',
                'show' => $can('identity.review'),
            ],
            [
                'href' => '/admin/permits',
                'match' => 'admin/permits*',
                'label' => 'Permit Management',
                'icon' =>
                    '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7z"/><path d="M14 2v6h6"/><path d="m9 15 2 2 4-4"/>',
                'show' => $can('permits.review'),
            ],
        ],
        'Documents & Verification' => [
            [
                'href' => '/admin/document-authentication',
                'match' => 'admin/document-authentication*',
                'label' => 'Document Authentication',
                'icon' =>
                    '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
                'show' => $can('documents.authenticate'),
            ],
        ],
        'Blockchain' => [
            [
                'href' => '/admin/blockchain-ledger',
                'match' => 'admin/blockchain-ledger*',
                'label' => 'Blockchain Ledger',
                'icon' =>
                    '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
                'show' => $can('blockchain.view'),
            ],
            [
                'href' => '/admin/smart-contracts',
                'match' => 'admin/smart-contracts*',
                'label' => 'Smart Contract Management',
                'icon' =>
                    '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7z"/><path d="M14 2v6h6"/><path d="m10 13-2 2 2 2"/><path d="m14 13 2 2-2 2"/>',
                'show' => $can('contracts.view'),
            ],
        ],
        'Communication' => [
            [
                'href' => '/admin/tickets',
                'match' => 'admin/tickets*',
                'label' => 'Ticket Messages',
                'icon' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
                'show' => $can('tickets.reply'),
            ],
        ],
        'Insights & Security' => [
            [
                'href' => '/admin/reports',
                'match' => 'admin/reports*',
                'label' => 'Reports & Analytics',
                'icon' => '<path d="M3 3v18h18"/><path d="M18 17V9M13 17V5M8 17v-3"/>',
                'show' => $can('reports.view'),
            ],
            [
                'href' => '/admin/audit-logs',
                'match' => 'admin/audit-logs*',
                'label' => 'Audit Logs',
                'icon' =>
                    '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M8 11h8M8 15h8M8 19h4"/>',
                'show' => $can('audit.view'),
            ],
        ],
    ];

    // Shared class strings
    $ink = 'text-(--gray-900) dark:text-(--gray-50)';
    $muted = 'text-(--gray-500) dark:text-(--gray-400)';
    $line = 'border-(--gray-200) dark:border-white/10';
    $link = 'text-xs font-semibold text-(--blue-700) hover:underline dark:text-(--blue-300)';

    // One hover style for every header button and menu item:
    // light = soft gray, dark = soft white. Text stays readable in both.
    $hoverSoft = 'hover:bg-(--gray-100) hover:text-(--gray-900) dark:hover:bg-white/10 dark:hover:text-(--white)';

    // Header buttons (bell, profile, theme)
    $menuButton =
        'list-none cursor-pointer rounded-xl transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-(--blue-500)/40 [&::-webkit-details-marker]:hidden ' .
        $hoverSoft;

    // Dropdown panel
    $menuPanel =
        'absolute right-0 z-50 mt-3 max-w-[calc(100vw-2rem)] rounded-2xl border border-(--gray-200) bg-(--white) shadow-xl shadow-black/10 dark:border-white/10 dark:bg-(--gray-800)';
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - BarangayChain PH</title>
    <link rel="icon" type="image/png" href="{{ asset('img/branding/barangaychain-lg2.png') }}">

    {{-- Fonts: Inter for the interface, Barlow for the logo --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Barlow:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet">

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
        }

        html[data-sidebar="collapsed"] {
            --sb-width: 5rem;
        }

        .admin-sidebar {
            width: var(--sb-width);
            transition: width .25s ease;
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

        /* Collapsed: logo only, toggle underneath */
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
            margin: 0 .75rem .875rem;
            background: rgba(255, 255, 255, .1);
        }

        html[data-sidebar="collapsed"] .sb-toggle-icon {
            transform: scaleX(-1);
        }

        .sb-logo {
            display: none;
        }

        html[data-sidebar="collapsed"] .sb-logo {
            display: block;
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body
    class="bg-(--gray-100) text-(--gray-800) dark:bg-(--black-700) dark:text-(--gray-200) h-screen overflow-hidden font-sans antialiased transition-colors duration-300">
    <div class="flex h-screen overflow-hidden">

        {{-- ========== Sidebar ========== --}}
        <aside
            class="admin-sidebar bg-(--blue-900) text-(--white) fixed inset-y-0 left-0 z-50 hidden overflow-y-auto overflow-x-hidden border-r border-white/5 md:block">

            {{-- Brand + toggle --}}
            <div class="sb-head flex min-h-20 items-center justify-between gap-2 border-b border-white/10 px-5 py-4">

                <a href="/admin/dashboard" class="flex min-w-0 items-center">
                    {{-- Logo: only visible when the sidebar is collapsed --}}
                    <img src="{{ asset('img/branding/barangaychain-lg2.png') }}" alt="BarangayChain PH"
                        class="sb-logo h-10 w-10 shrink-0 object-contain">

                    {{-- Name: only visible when the sidebar is expanded --}}
                    <p class="sb-label font-brand truncate text-[22px] font-bold uppercase leading-none tracking-wide">
                        <span class="text-(--white)">BarangayChain</span>
                        <span class="text-(--green-400)">PH</span>
                    </p>
                </a>

                {{-- Toggle --}}
                <button type="button" id="admin-sidebar-toggle" aria-label="Toggle sidebar" aria-expanded="true"
                    title="Toggle sidebar"
                    class="text-(--gray-300) hover:text-(--white) flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-white/10 transition-colors duration-200 hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/30">
                    <svg class="sb-toggle-icon h-5 w-5 transition-transform" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"
                        aria-hidden="true">
                        <rect x="3" y="4" width="18" height="16" rx="2" />
                        <path d="M9 4v16" />
                    </svg>
                </button>
            </div>

            {{-- Links --}}
            <nav class="space-y-7 px-4 py-7">
                @foreach ($nav as $section => $links)
                    @php $links = collect($links)->where('show', true); @endphp
                    @continue($links->isEmpty())

                    <div>
                        <p
                            class="sb-section-label text-(--gray-400) mb-2.5 px-3 text-[10.5px] font-semibold uppercase tracking-[0.14em]">
                            {{ $section }}</p>
                        <div class="space-y-1">
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
                class="{{ $line }} bg-(--white) dark:bg-(--gray-900) relative z-40 flex h-20 shrink-0 items-center justify-between border-b px-3 sm:px-6">

                <div class="min-w-0">
                    <x-partials.live-date class="hidden lg:flex" timezone="Asia/Manila" />
                </div>

                <div class="flex shrink-0 items-center gap-1 sm:gap-2">

                    <x-partials.theme-toggle />

                    {{-- Notifications --}}
                    <details id="admin-notification-menu" class="relative">
                        <summary aria-label="Notifications"
                            class="{{ $menuButton }} {{ $muted }} relative flex p-2.5">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />
                                <path d="M10 21h4" />
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
                                <p class="{{ $ink }} text-sm font-bold">Notifications</p>
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
                                            class="{{ $iconBox }} relative flex h-9 w-9 shrink-0 items-center justify-center rounded-xl">
                                            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor"
                                                stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="{{ $iconPath }}" />
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
                                    class="ring-(--green-400) h-9 w-9 rounded-full object-cover ring-2">
                            @else
                                <span
                                    class="bg-(--blue-900) text-(--white) ring-(--green-400) flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold ring-2">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </span>
                            @endif

                            <span class="hidden text-left sm:block">
                                <span
                                    class="{{ $ink }} block max-w-40 truncate text-sm font-semibold leading-tight">{{ $user->name }}</span>
                                <span
                                    class="text-(--green-600) dark:text-(--green-400) block font-mono text-[10px] font-semibold uppercase tracking-wide">{{ $admin ? 'Admin' : 'Staff' }}</span>
                            </span>

                            <svg class="{{ $muted }} hidden h-4 w-4 transition-transform group-open/user:rotate-180 sm:block"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </summary>

                        <div class="{{ $menuPanel }} w-72 p-2">
                            <div class="{{ $line }} mb-2 border-b px-3 pb-3 pt-2">
                                <p class="{{ $ink }} truncate font-semibold">{{ $user->name }}</p>
                                <p class="{{ $muted }} truncate font-mono text-xs">{{ $user->email }}</p>
                            </div>

                            <a href="{{ route('admin.profile') }}"
                                class="{{ $hoverSoft }} text-(--gray-700) dark:text-(--gray-200) flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors duration-200">
                                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                    stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="12" cy="8" r="4" />
                                    <path d="M5 21v-2a7 7 0 0 1 14 0v2" />
                                </svg>
                                <span>My profile</span>
                            </a>

                            <form method="POST" action="{{ route('logout') }}"
                                class="{{ $line }} mt-2 border-t pt-2">
                                @csrf
                                <button type="submit"
                                    class="text-(--red-600) hover:bg-(--red-500)/10 dark:text-(--red-500) flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-semibold transition-colors duration-200">
                                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                        stroke-linejoin="round" aria-hidden="true">
                                        <path d="M10 17l5-5-5-5" />
                                        <path d="M15 12H3" />
                                        <path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6" />
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

        // ---------- Auto refresh every 5 seconds ----------
        // Replace an element's content only if it changed and it is not an open menu.
        function swap(fresh, current) {
            if (!fresh || !current || current.open) return;
            if (fresh.innerHTML !== current.innerHTML) {
                current.innerHTML = fresh.innerHTML;
            }
        }

        // True when the user typed something that is not saved yet
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
