@php
    $user = auth()->user();
    $admin = $user->isAdmin();
    $can = fn($permission) => $user->hasPermission($permission);

    $notifications = $user->notifications()->latest()->limit(10)->get();
    $unread = $user->unreadNotifications()->count();

    // status => [icon box classes, icon path]
    $notificationStyles = [
        'verified' => [
            'bg-emerald-50 text-emerald-500',
            'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        ],
        'rejected' => [
            'bg-red-50 text-red-500',
            'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
        ],
        'info' => [
            'bg-orange-50 text-orange-500',
            'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0',
        ],
    ];

    // section => [href, active match, label, svg icon, allowed?]
    $nav = [
        'Overview' => [
            [
                '/admin/dashboard',
                'admin/dashboard',
                'Dashboard',
                '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
                true,
            ],
        ],
        'Management' => [
            [
                '/admin/users',
                'admin/users*',
                'Users & Roles',
                '<path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
                $admin,
            ],
            [
                '/admin/identity-verification',
                'admin/identity-verification*',
                'Identity Verification',
                '<rect x="2" y="5" width="20" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M6.17 15a3 3 0 0 1 5.66 0"/><path d="M15 10h3M15 14h3"/>',
                $can('identity.review'),
            ],
            [
                '/admin/permits',
                'admin/permits*',
                'Permit Management',
                '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7z"/><path d="M14 2v6h6"/><path d="m9 15 2 2 4-4"/>',
                $can('permits.review'),
            ],
        ],
        'Documents & Verification' => [
            [
                '/admin/document-authentication',
                'admin/document-authentication*',
                'Document Authentication',
                '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
                $can('documents.authenticate'),
            ],
        ],
        'Blockchain' => [
            [
                '/admin/blockchain-ledger',
                'admin/blockchain-ledger*',
                'Blockchain Ledger',
                '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
                $can('blockchain.view'),
            ],
        ],
        'Communication' => [
            [
                '/admin/tickets',
                'admin/tickets*',
                'Ticket Messages',
                '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
                $can('tickets.reply'),
            ],
        ],
        'Insights & Security' => [
            [
                '/admin/reports',
                'admin/reports*',
                'Reports & Analytics',
                '<path d="M3 3v18h18"/><path d="M18 17V9M13 17V5M8 17v-3"/>',
                $can('reports.view'),
            ],
            [
                '/admin/audit-logs',
                'admin/audit-logs*',
                'Audit Logs',
                '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M8 11h8M8 15h8M8 19h4"/>',
                $can('audit.view'),
            ],
        ],
    ];

    $menuPanel =
        'absolute right-0 z-50 mt-3 max-w-[calc(100vw-2rem)] rounded-2xl border border-slate-200/70 bg-white shadow-[0_24px_60px_-15px_rgba(7,20,40,0.28)]';
    // Shared header controls: transparent by default, dark blue on hover.
    $menuButton =
        'list-none cursor-pointer rounded-xl border border-transparent bg-transparent shadow-none transition-all duration-200 ease-in-out hover:border-[#0B1F3A]/10 hover:bg-[#0B1F3A]/[0.06] hover:text-[#0B1F3A] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0B1F3A]/25 [&::-webkit-details-marker]:hidden';
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - BarangayChain PH</title>
    <link rel="icon" type="image/png" href="{{ asset('img/branding/barangayChainPH-logo.png') }}">

    {{-- Apply the saved sidebar state before first paint (no flicker) --}}
    <script>
        try {
            if (localStorage.getItem('admin-sidebar') === 'collapsed') document.documentElement.dataset.sidebar =
                'collapsed';
        } catch (e) {}
    </script>
    <style>
        :root {
            --sb-width: 18rem;
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

        /* Brand: text when expanded, bare logo when collapsed */
        .sb-logo {
            display: none;
        }

        html[data-sidebar="collapsed"] .sb-logo {
            display: block;
        }

        html[data-sidebar="collapsed"] .sb-label {
            display: none;
        }

        /* Collapsed icon rail */
        html[data-sidebar="collapsed"] .sb-brand {
            justify-content: center;
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
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-screen overflow-hidden bg-[#F3F6F5] font-sans text-slate-800 antialiased">
    <div class="flex h-screen overflow-hidden">

        {{-- Sidebar --}}
        <aside
            class="admin-sidebar bg-linear-to-b fixed inset-y-0 left-0 z-50 hidden overflow-y-auto overflow-x-hidden border-r border-white/5 from-[#071428] to-[#0b1f3a] text-white md:block">

            {{-- Brand: text only when open, logo only (no background) when collapsed --}}
            <div class="relative flex h-20 items-center border-b border-white/5 px-6">
                <div
                    class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top_left,rgba(16,185,129,0.22),transparent_70%)]">
                </div>
                <div class="sb-brand relative flex w-full items-center">
                    <p
                        class="sb-label w-full text-center font-['Barlow_Condensed'] text-[30px] font-bold leading-none tracking-wide">
                        <span class="text-[#ffffff]">BARANGAYCHAIN</span>
                        <span class="text-emerald-600">PH</span>
                    </p>
                    <img src="{{ asset('img/branding/barangayChainPH-logo.png') }}" alt="BarangayChain PH"
                        class="sb-logo h-10 w-10 object-contain">
                </div>
            </div>

            <nav class="space-y-7 px-4 py-7">
                @foreach ($nav as $section => $items)
                    @php $items = array_filter($items, fn($item) => $item[4]); @endphp
                    @continue(!$items)

                    <div>
                        <p
                            class="sb-section-label mb-2.5 px-3 text-[10.5px] font-semibold uppercase tracking-[0.14em] text-slate-500">
                            {{ $section }}</p>
                        <div class="space-y-1">
                            @foreach ($items as [$href, $match, $label, $icon])
                                <x-admin.nav-link :href="$href" :match="$match">
                                    <x-slot:icon>{!! $icon !!}</x-slot:icon>
                                    {{ $label }}
                                </x-admin.nav-link>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>
        </aside>

        {{-- Main area --}}
        <div class="admin-main flex h-screen min-w-0 flex-1 flex-col overflow-hidden">

            {{-- Header --}}
            <header
                class="relative z-40 flex h-20 shrink-0 items-center justify-between border-b border-slate-200/70 bg-white/80 px-2 backdrop-blur-md sm:px-4">

                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" id="admin-sidebar-toggle" aria-label="Toggle sidebar" aria-expanded="true"
                        title="Toggle sidebar"
                        class="{{ $menuButton }} hidden h-10 w-10 shrink-0 items-center justify-center text-slate-500 hover:text-[#0B1F3A] md:flex">
                        <svg class="sb-toggle-icon h-5 w-5 transition-transform" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"
                            aria-hidden="true">
                            <rect x="3" y="4" width="18" height="16" rx="2" />
                            <path d="M9 4v16" />
                        </svg>
                    </button>

                    <div class="min-w-0">
                        <h1 class="truncate text-xl font-bold tracking-tight text-[#071428]">@yield('title', 'Dashboard')</h1>
                        <p class="hidden text-xs font-medium text-emerald-600 sm:block">Barangay San Luis | Antipolo
                            City</p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-1 sm:gap-2">

                    {{-- Notifications --}}
                    <details id="admin-notification-menu" class="relative">
                        <summary aria-label="Notifications"
                            class="{{ $menuButton }} relative flex p-2.5 text-slate-500 hover:text-[#0B1F3A]">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />
                                <path d="M10 21h4" />
                            </svg>
                            @if ($unread)
                                <span id="admin-notification-count"
                                    class="absolute right-0.5 top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-emerald-500 px-1 text-[10px] font-bold leading-none text-white ring-2 ring-white">
                                    {{ $unread > 9 ? '9+' : $unread }}
                                </span>
                            @endif
                        </summary>

                        <div class="{{ $menuPanel }} w-96 overflow-hidden">
                            <div class="border-b border-slate-100 px-5 py-4">
                                <p class="text-sm font-bold text-slate-900">Notifications</p>
                                <p id="admin-notification-subtitle" class="mt-0.5 text-xs text-slate-500">
                                    {{ $unread ? "You have {$unread} new " . \Illuminate\Support\Str::plural('notification', $unread) . '.' : "You're all caught up." }}
                                </p>
                            </div>

                            <div class="max-h-96 overflow-y-auto px-5 py-4">
                                @forelse ($notifications as $notification)
                                    @php [$color, $icon] = $notificationStyles[$notification->data['status'] ?? 'info'] ?? $notificationStyles['info']; @endphp

                                    <div class="relative flex gap-3 pb-5 last:pb-0">
                                        @unless ($loop->last)
                                            <span
                                                class="left-4.5 absolute bottom-1 top-10 w-px -translate-x-1/2 bg-slate-200"
                                                aria-hidden="true"></span>
                                        @endunless

                                        <span
                                            class="{{ $color }} relative flex h-9 w-9 shrink-0 items-center justify-center rounded-xl">
                                            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor"
                                                stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="{{ $icon }}" />
                                            </svg>
                                        </span>

                                        <form method="POST"
                                            action="{{ route('admin.notifications.read', $notification->id) }}"
                                            class="min-w-0 flex-1">
                                            @csrf
                                            <button type="submit" class="group w-full text-left">
                                                <span class="flex items-start justify-between gap-3">
                                                    <span
                                                        class="text-[13px] font-semibold text-slate-900 group-hover:underline">
                                                        {{ $notification->data['title'] ?? 'Notification' }}
                                                        @if (!$notification->read_at)
                                                            <span data-admin-unread-dot
                                                                class="ml-1 inline-block h-2 w-2 rounded-full bg-emerald-500 align-middle">
                                                                <span class="sr-only">Unread</span>
                                                            </span>
                                                        @endif
                                                    </span>
                                                    <span
                                                        class="shrink-0 whitespace-nowrap text-[11px] text-slate-500">{{ $notification->created_at->diffForHumans() }}</span>
                                                </span>
                                                <span
                                                    class="mt-0.5 block text-xs text-slate-500">{{ $notification->data['message'] ?? '' }}</span>
                                            </button>
                                        </form>
                                    </div>
                                @empty
                                    <p class="px-2 py-8 text-center text-sm text-slate-500">No notifications yet.</p>
                                @endforelse
                            </div>

                            <div
                                class="flex items-center justify-between border-t border-slate-100 bg-slate-50/60 px-5 py-3">
                                @if ($unread)
                                    <form id="admin-mark-all-form" method="POST"
                                        action="{{ route('admin.notifications.read-all') }}">
                                        @csrf
                                        <button
                                            class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 hover:underline">Mark
                                            all as read</button>
                                    </form>
                                @endif
                                <a href="{{ route('admin.notifications.index') }}"
                                    class="ml-auto text-xs font-semibold text-emerald-600 hover:underline">View all</a>
                            </div>
                        </div>
                    </details>

                    <div class="h-8 border-l border-slate-200"></div>

                    {{-- Admin / staff profile --}}
                    <details id="admin-user-menu" class="group relative">
                        <summary class="{{ $menuButton }} group flex items-center gap-3 py-1.5 pl-1.5 pr-3 sm:pr-4">
                            @if ($user->employeeProfile?->photo_path)
                                <img src="{{ route('admin.profile.photo.show') }}?v={{ $user->updated_at?->timestamp }}"
                                    alt="" class="h-9 w-9 rounded-full object-cover ring-2 ring-emerald-400">
                            @else
                                <span
                                    class="flex h-9 w-9 items-center justify-center rounded-full bg-[#071428] text-sm font-bold text-white ring-2 ring-emerald-400">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </span>
                            @endif
                            <span class="hidden text-left sm:block">
                                <span
                                    class="block max-w-40 truncate text-sm font-bold leading-tight text-[#071428] transition-colors group-hover:text-[#0B1F3A]">{{ $user->name }}</span>
                                <span
                                    class="block text-[10px] font-semibold uppercase tracking-wide text-emerald-600 transition-colors group-hover:text-[#2563EB]">{{ $admin ? 'Admin' : 'Staff' }}</span>
                            </span>
                            <svg class="hidden h-4 w-4 text-slate-400 transition group-hover:text-[#0B1F3A] group-open:rotate-180 sm:block"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </summary>

                        <div class="{{ $menuPanel }} w-72 p-2">
                            <div class="mb-2 border-b border-slate-100 px-3 pb-3 pt-2">
                                <p class="truncate font-bold text-slate-900">{{ $user->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                            </div>

                            <a href="{{ route('admin.profile') }}"
                                class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition-colors duration-200 hover:bg-[#0B1F3A]/6 hover:text-[#0B1F3A]">
                                <svg class="h-4 w-4 shrink-0 text-slate-400 transition-colors group-hover:text-[#0B1F3A]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="12" cy="8" r="4" />
                                    <path d="M5 21v-2a7 7 0 0 1 14 0v2" />
                                </svg>
                                <span>My profile</span>
                            </a>

                            <form method="POST" action="{{ route('logout') }}"
                                class="mt-2 border-t border-slate-100 pt-2">
                                @csrf
                                <button type="submit"
                                    class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-red-600 transition-colors duration-200 hover:bg-red-500/6 hover:text-red-700">
                                    <svg class="h-4 w-4 shrink-0 text-red-400 transition-colors group-hover:text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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

            {{-- Page content (children can use cards: bg-white rounded-2xl p-5 shadow-sm) --}}
            <main id="admin-page-content"
                class="min-h-0 min-w-0 flex-1 overflow-y-auto bg-[#F3F6F5] px-4 pb-6 pt-1 sm:px-7 sm:pb-8">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        const root = document.documentElement;
        const $ = (id) => document.getElementById(id);
        const notificationMenu = $('admin-notification-menu');
        const userMenu = $('admin-user-menu');
        const menus = [notificationMenu, userMenu];
        const sidebarToggle = $('admin-sidebar-toggle');

        // Collapse the sidebar to an icon rail; the choice is remembered.
        function setSidebar(collapsed) {
            collapsed ? root.setAttribute('data-sidebar', 'collapsed') : root.removeAttribute('data-sidebar');
            sidebarToggle?.setAttribute('aria-expanded', String(!collapsed));
            document.querySelectorAll('.sb-link').forEach((link) => {
                collapsed ? link.title = link.textContent.trim() : link.removeAttribute('title');
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

        // Opening the bell marks everything as read.
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
                if (!response.ok) throw new Error('Unable to mark admin notifications as read.');

                badge.remove();
                form.remove();
                $('admin-notification-subtitle').textContent = "You're all caught up.";
                document.querySelectorAll('[data-admin-unread-dot]').forEach((dot) => dot.remove());
            } catch (error) {
                console.error(error);
            }
        });

        // Keep the page in sync without a full reload (skipped while the user is typing).
        const swap = (fresh, current) => {
            if (!fresh || !current || fresh.innerHTML === current.innerHTML) return;
            const open = current.open;
            current.innerHTML = fresh.innerHTML;
            if (open !== undefined) current.open = open;
        };

        setInterval(async () => {
            if (document.hidden || document.activeElement?.matches(
                    'input, textarea, select, [contenteditable="true"]')) return;

            const hasUnsavedInput = [...document.querySelectorAll('form input, form textarea, form select')]
                .some((field) => field.type === 'file' ? field.files.length > 0 : field.value !== field
                    .defaultValue);
            if (hasUnsavedInput) return;

            try {
                const response = await fetch(location.href, {
                    cache: 'no-store',
                    headers: {
                        'Accept': 'text/html'
                    },
                    credentials: 'same-origin',
                });
                if (!response.ok) throw new Error('Unable to synchronize the admin page.');

                const fresh = new DOMParser().parseFromString(await response.text(), 'text/html');
                ['admin-page-content', 'admin-notification-menu', 'admin-user-menu']
                .forEach((id) => swap(fresh.getElementById(id), $(id)));
            } catch (error) {
                console.error(error);
            }
        }, 15000);

        // Close menus on outside click or Escape.
        document.addEventListener('click', (event) => {
            menus.forEach((menu) => menu && !menu.contains(event.target) && menu.removeAttribute('open'));
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
