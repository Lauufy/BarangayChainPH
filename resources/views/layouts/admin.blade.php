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

        @media (min-width: 768px) {
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
        }

        @media (width < 768px) {
            .admin-sidebar:popover-open {
                display: block;
                inset: 0 auto 0 0;
                margin: 0;
                width: min(19rem, calc(100vw - 3rem));
                height: 100%;
                max-height: none;
            }

            .admin-sidebar::backdrop {
                background: rgb(2 6 23 / .5);
            }
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('page-scripts')
</head>

<body
    class="bg-(--gray-100) text-(--gray-800) dark:bg-(--black-700) dark:text-(--gray-200) h-screen overflow-hidden font-sans antialiased transition-colors duration-300">

    <x-feedbacks.t-confirmation />

    <div class="flex h-screen overflow-hidden">

        {{-- ========== Sidebar ========== --}}
        <aside id="admin-sidebar" aria-label="Admin navigation"
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
                    <svg class="sb-toggle-icon h-5 w-5 transition-transform" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"
                        aria-hidden="true">
                        <rect x="3" y="3" width="18" height="18" rx="2" />
                        <path d="M9 3v18" />
                    </svg>
                </button>
            </div>

            {{-- Links --}}
            <nav class="space-y-5 px-3 py-5">

                {{-- Overview --}}
                <div>
                    <p
                        class="sb-section-label mb-2 px-3 text-[11px] font-medium uppercase tracking-wider text-[#8b93b8]">
                        Overview</p>
                    <div class="space-y-0.5">
                        <x-admin.nav-link href="/admin/dashboard" match="admin/dashboard">
                            <x-slot:icon><path d="M12 2a10 10 0 1 0 10 10H12z"/><path d="M14 2.2V10h7.8A10 10 0 0 0 14 2.2z"/></x-slot:icon>
                            Dashboard
                        </x-admin.nav-link>
                    </div>
                </div>

                {{-- Management --}}
                @if (auth()->user()->isAdmin() ||
                        auth()->user()->hasPermission('identity.review') ||
                        auth()->user()->hasPermission('permits.review'))
                    <div>
                        <p
                            class="sb-section-label mb-2 px-3 text-[11px] font-medium uppercase tracking-wider text-[#8b93b8]">
                            Management</p>
                        <div class="space-y-0.5">
                            @if (auth()->user()->isAdmin())
                                <x-admin.nav-link href="/admin/users" match="admin/users*">
                                    <x-slot:icon><circle cx="9" cy="8" r="4"/><path d="M2 20a7 7 0 0 1 14 0z"/><circle cx="17" cy="9" r="3"/><path d="M17.5 14a4.5 4.5 0 0 1 4.5 4.5V20h-4.2a8.6 8.6 0 0 0-2-5 4.6 4.6 0 0 1 1.7-1z"/></x-slot:icon>
                                    Users & Roles
                                </x-admin.nav-link>
                            @endif

                            @if (auth()->user()->hasPermission('identity.review'))
                                <x-admin.nav-link href="/admin/identity-verification"
                                    match="admin/identity-verification*">
                                    <x-slot:icon><rect x="2" y="5" width="20" height="14" rx="3"/><circle class="cut" cx="8.5" cy="11" r="2"/><path class="cut" d="M5.5 16.5a3 3 0 0 1 6 0z"/><path class="cutl" d="M14.5 10h4M14.5 14h4"/></x-slot:icon>
                                    Identity Verification
                                </x-admin.nav-link>
                            @endif

                            @if (auth()->user()->hasPermission('permits.review'))
                                <x-admin.nav-link href="/admin/permits" match="admin/permits*">
                                    <x-slot:icon><rect x="4" y="4" width="16" height="18" rx="3"/><rect x="8" y="2" width="8" height="4" rx="1.5"/><path class="cutl" d="m9 14 2 2 4-4"/></x-slot:icon>
                                    Permit Management
                                </x-admin.nav-link>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Documents & Verification --}}
                @if (auth()->user()->hasPermission('documents.authenticate'))
                    <div>
                        <p
                            class="sb-section-label mb-2 px-3 text-[11px] font-medium uppercase tracking-wider text-[#8b93b8]">
                            Documents & Verification</p>
                        <div class="space-y-0.5">
                            <x-admin.nav-link href="/admin/document-authentication"
                                match="admin/document-authentication*">
                                <x-slot:icon><path d="M12 2 4 5v6c0 5 3.4 9.3 8 11 4.6-1.7 8-6 8-11V5z"/><path class="cutl" d="m8.5 12 2.5 2.5 4.5-4.5"/></x-slot:icon>
                                Document Records
                            </x-admin.nav-link>
                        </div>
                    </div>
                @endif

                {{-- Blockchain --}}
                @if (auth()->user()->hasPermission('blockchain.view') || auth()->user()->hasPermission('contracts.view'))
                    <div>
                        <p
                            class="sb-section-label mb-2 px-3 text-[11px] font-medium uppercase tracking-wider text-[#8b93b8]">
                            Blockchain</p>
                        <div class="space-y-0.5">
                            @if (auth()->user()->hasPermission('blockchain.view'))
                                <x-admin.nav-link href="/admin/blockchain-ledger" match="admin/blockchain-ledger*">
                                    <x-slot:icon><path d="M12 2 3 7v10l9 5 9-5V7z"/><path class="cutl" d="m3 7 9 5 9-5M12 12v10"/></x-slot:icon>
                                    Blockchain Ledger
                                </x-admin.nav-link>
                            @endif

                            @if (auth()->user()->hasPermission('contracts.view'))
                                <x-admin.nav-link href="/admin/smart-contracts" match="admin/smart-contracts*">
                                    <x-slot:icon><path d="M6 2h8l6 6v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"/><path class="cutl" d="m10 12-2 2.5 2 2.5M14 12l2 2.5-2 2.5"/></x-slot:icon>
                                    Smart Contract Management
                                </x-admin.nav-link>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Communication --}}
                @if (auth()->user()->hasPermission('tickets.reply'))
                    <div>
                        <p
                            class="sb-section-label mb-2 px-3 text-[11px] font-medium uppercase tracking-wider text-[#8b93b8]">
                            Communication</p>
                        <div class="space-y-0.5">
                            <x-admin.nav-link :href="route('admin.tickets.index')" :match="['admin/tickets*', 'admin/contact-messages*']">
                                <x-slot:icon><path d="M5 3h14a3 3 0 0 1 3 3v9a3 3 0 0 1-3 3H10l-5 4v-4a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3z"/><path d="M8 8h8M8 12h5"/></x-slot:icon>
                                Communications Management
                            </x-admin.nav-link>
                        </div>
                    </div>
                @endif

                {{-- Insights & Security --}}
                @if (auth()->user()->hasPermission('reports.view') || auth()->user()->hasPermission('audit.view'))
                    <div>
                        <p
                            class="sb-section-label mb-2 px-3 text-[11px] font-medium uppercase tracking-wider text-[#8b93b8]">
                            Insights & Security</p>
                        <div class="space-y-0.5">
                            @if (auth()->user()->hasPermission('reports.view'))
                                <x-admin.nav-link href="/admin/reports" match="admin/reports*">
                                    <x-slot:icon><rect x="3" y="3" width="18" height="18" rx="3"/><path class="cutl" d="M8 17v-4M12 17V8M16 17v-6"/></x-slot:icon>
                                    Reports & Analytics
                                </x-admin.nav-link>
                            @endif

                            @if (auth()->user()->hasPermission('audit.view'))
                                <x-admin.nav-link href="/admin/audit-logs" match="admin/audit-logs*">
                                    <x-slot:icon><circle cx="12" cy="12" r="10"/><path class="cutl" d="M12 7v5l3.5 2"/></x-slot:icon>
                                    Audit Logs
                                </x-admin.nav-link>
                            @endif
                        </div>
                    </div>
                @endif
            </nav>
        </aside>

        {{-- ========== Main area ========== --}}
        <div class="admin-main flex h-screen min-w-0 flex-1 flex-col overflow-hidden">

            {{-- Header --}}
            <header
                class="border-(--gray-200) bg-(--white) dark:bg-(--blue-850) relative z-40 flex h-20 shrink-0 items-center justify-between border-b px-3 sm:px-6 dark:border-white/10">

                <div class="min-w-0">
                    <button type="button" id="admin-mobile-sidebar-toggle" popovertarget="admin-sidebar"
                        aria-label="Open sidebar" aria-controls="admin-sidebar" aria-expanded="false"
                        class="grid size-11 place-items-center rounded-lg bg-[#070b24] text-[#a4accd] focus-visible:outline-2 focus-visible:outline-indigo-500 md:hidden">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"
                        aria-hidden="true">
                        <rect x="3" y="3" width="18" height="18" rx="2" />
                        <path d="M9 3v18" />
                    </svg>
                    </button>
                    <x-partials.live-date class="hidden lg:flex" timezone="Asia/Manila" />
                </div>

                <div class="flex shrink-0 items-center gap-1 sm:gap-2">

                    <x-partials.theme-toggle />

                    {{-- Notifications --}}
                    @php($unreadNotificationCount = auth()->user()->unreadNotifications()->count())
                    <details id="admin-notification-menu" class="md:relative">
                        <summary aria-label="Notifications"
                            class="list-none cursor-pointer rounded-lg outline-none transition-colors duration-200 focus-visible:bg-(--gray-100) dark:focus-visible:bg-white/10 [&::-webkit-details-marker]:hidden hover:bg-(--gray-100) hover:text-(--gray-900) dark:hover:bg-white/10 dark:hover:text-(--white) text-(--gray-500) dark:text-(--gray-400) relative flex p-2.5">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M10.268 21a2 2 0 0 0 3.464 0" />
                                <path
                                    d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326" />
                            </svg>
                            @if ($unreadNotificationCount)
                                <span id="admin-notification-count"
                                    class="bg-(--red-600) text-(--white) ring-(--white) dark:ring-(--gray-900) absolute right-0.5 top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full px-1 font-mono text-[10px] font-bold leading-none ring-2">
                                    {{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}
                                </span>
                            @endif
                        </summary>

                        <div
                            class="absolute inset-x-3 top-full z-50 mt-1 grid max-h-[calc(100dvh-6rem)] grid-rows-[auto_auto_minmax(0,1fr)_auto] overflow-y-auto rounded-xl border border-(--gray-200) bg-(--gray-50) p-3 shadow-xl shadow-black/10 md:inset-x-auto md:right-0 md:mt-3 md:w-104 dark:border-white/10 dark:bg-(--gray-900)">

                            {{-- Header --}}
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-(--gray-900) dark:text-(--gray-50) text-lg font-bold leading-tight">
                                        Notifications</p>
                                    <p id="admin-notification-subtitle"
                                        class="text-(--gray-500) dark:text-(--gray-400) mt-0.5 text-xs">
                                        @if ($unreadNotificationCount)
                                            You have {{ $unreadNotificationCount }} new
                                            {{ \Illuminate\Support\Str::plural('notification', $unreadNotificationCount) }}.
                                        @else
                                            You're all caught up.
                                        @endif
                                    </p>
                                </div>

                                @if ($unreadNotificationCount)
                                    <form id="admin-mark-all-form" method="POST"
                                        action="{{ route('admin.notifications.read-all') }}">
                                        @csrf
                                        <button type="submit"
                                            class="text-(--gray-500) dark:text-(--gray-300) flex shrink-0 items-center gap-1.5 rounded-lg border border-(--gray-200) bg-(--white) px-3 py-1.5 text-xs font-medium transition-colors hover:bg-(--gray-100) dark:border-white/10 dark:bg-(--gray-800) dark:hover:bg-white/10">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round" aria-hidden="true">
                                                <path d="M20 6 9 17l-5-5" />
                                            </svg>
                                            Mark all as read
                                        </button>
                                    </form>
                                @endif
                            </div>

                            {{-- Tabs --}}
                            <div
                                class="mt-3 grid grid-cols-2 overflow-hidden rounded-lg border border-(--blue-600) bg-(--white) dark:bg-(--gray-800)">
                                <button type="button" data-notif-tab="all"
                                    class="bg-(--blue-600) text-(--white) py-1.5 text-xs font-bold uppercase tracking-wide transition-colors">
                                    All
                                </button>
                                <button type="button" data-notif-tab="unread"
                                    class="text-(--gray-900) dark:text-(--gray-50) py-1.5 text-xs font-bold uppercase tracking-wide transition-colors">
                                    Unread
                                </button>
                            </div>

                            {{-- List --}}
                            <div class="mt-2.5 max-h-80 space-y-1 overflow-y-auto">
                                @forelse (auth()->user()->notifications()->latest()->limit(10)->get() as $notification)
                                    <form method="POST" action="{{ route('admin.notifications.read', $notification->id) }}" data-notif-item data-unread="{{ $notification->read_at ? '0' : '1' }}"
                                        class="relative flex gap-2 rounded-lg border border-(--gray-200) bg-(--white) p-2.5 dark:border-white/10 dark:bg-(--gray-800)">
                                        @csrf
                                        <input type="hidden" name="open" value="1">

                                        @switch($notification->data['status'] ?? 'info')
                                            @case('verified')
                                                <span
                                                    class="bg-[color-mix(in_srgb,var(--green-500)_16%,transparent)] text-(--green-600) dark:text-(--green-300) relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full">
                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                                                        stroke="currentColor" stroke-width="1.75" stroke-linecap="round"
                                                        stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20ZM9 12l2 2 4-4" />
                                                    </svg>
                                                </span>
                                            @break

                                            @case('rejected')
                                                <span
                                                    class="bg-[color-mix(in_srgb,var(--red-500)_14%,transparent)] text-(--red-600) dark:text-(--red-500) relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full">
                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                                                        stroke="currentColor" stroke-width="1.75" stroke-linecap="round"
                                                        stroke-linejoin="round" aria-hidden="true">
                                                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3" />
                                                        <path d="M12 9v4M12 17h.01" />
                                                    </svg>
                                                </span>
                                            @break

                                            @default
                                                <span
                                                    class="bg-[color-mix(in_srgb,var(--amber-500)_16%,transparent)] text-(--amber-500) relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full">
                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                                                        stroke="currentColor" stroke-width="1.75" stroke-linecap="round"
                                                        stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20ZM12 16v-4M12 8h.01" />
                                                    </svg>
                                                </span>
                                        @endswitch

                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-start justify-between gap-1">
                                                <div class="flex min-w-0 items-center gap-1">
                                                    <button type="submit" class="cursor-pointer text-left after:absolute after:inset-0 text-(--blue-900) dark:text-(--gray-50) text-sm font-bold hover:underline">
                                                        {{ $notification->data['title'] ?? 'Notification' }}
                                                        @unless ($notification->read_at)
                                                            <span data-admin-unread-dot
                                                                class="bg-(--blue-600) dark:bg-(--blue-300) ml-1 inline-block h-2 w-2 rounded-full align-middle">
                                                                <span class="sr-only">Unread</span>
                                                            </span>
                                                        @endunless
                                                    </button>
                                                </div>
                                                <span
                                                    class="text-(--gray-500) dark:text-(--gray-400) shrink-0 whitespace-nowrap text-xs">{{ $notification->created_at->diffForHumans() }}</span>
                                            </div>

                                            <span
                                                class="text-(--gray-500) dark:text-(--gray-400) mt-0.5 block text-xs leading-snug">
                                                {{ $notification->data['message'] ?? '' }}
                                            </span>
                                        </div>
                                    </form>
                                @empty
                                    <p class="text-(--gray-500) dark:text-(--gray-400) px-2 py-8 text-center text-sm">
                                        No notifications yet.</p>
                                @endforelse

                                <p data-notif-unread-empty hidden
                                    class="text-(--gray-500) dark:text-(--gray-400) px-2 py-8 text-center text-sm">
                                    No unread notifications.</p>
                            </div>

                            {{-- Footer --}}
                            <div class="mt-3 text-center">
                                <a href="{{ route('admin.notifications.index') }}"
                                    class="text-xs font-bold text-(--blue-900) hover:underline dark:text-(--blue-300)">View
                                    All</a>
                            </div>
                        </div>
                    </details>

                    <div class="border-(--gray-200) h-8 border-l dark:border-white/10"></div>

                    {{-- Profile menu --}}
                    <details id="admin-user-menu" class="group/user relative">
                        <summary
                            class="list-none cursor-pointer rounded-lg outline-none transition-colors duration-200 focus-visible:bg-(--gray-100) dark:focus-visible:bg-white/10 [&::-webkit-details-marker]:hidden hover:bg-(--gray-100) hover:text-(--gray-900) dark:hover:bg-white/10 dark:hover:text-(--white) flex items-center gap-3 py-1.5 pl-1.5 pr-3 sm:pr-4">
                            @if (auth()->user()->employeeProfile?->photo_path)
                                <img src="{{ route('admin.profile.photo.show') }}?v={{ auth()->user()->updated_at?->timestamp }}"
                                    alt=""
                                    class="ring-(--gray-200) h-9 w-9 rounded-full object-cover ring-2 dark:ring-white/10">
                            @else
                                <span
                                    class="text-(--white) bg-(--gray-800) flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </span>
                            @endif

                            <span class="hidden text-left sm:block">
                                <span
                                    class="text-(--gray-900) dark:text-(--gray-50) block max-w-40 truncate text-sm font-semibold leading-tight">{{ auth()->user()->name }}</span>
                                <span
                                    class="text-(--gray-500) dark:text-(--gray-400) block font-mono text-[10px] font-semibold uppercase tracking-wide">{{ auth()->user()->isAdmin() ? 'Admin' : 'Staff' }}</span>
                            </span>

                            <svg class="text-(--gray-500) dark:text-(--gray-400) hidden h-4 w-4 transition-transform group-open/user:rotate-180 sm:block"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </summary>

                        <div
                            class="absolute right-0 z-50 mt-3 max-w-[calc(100vw-2rem)] rounded-xl border border-(--gray-200) bg-(--white) shadow-xl shadow-black/10 dark:border-white/10 dark:bg-(--gray-800) w-72 p-2">
                            <div class="border-(--gray-200) mb-2 border-b px-3 pb-3 pt-2 dark:border-white/10">
                                <p class="text-(--gray-900) dark:text-(--gray-50) truncate font-semibold">
                                    {{ auth()->user()->name }}</p>
                                <p class="text-(--gray-500) dark:text-(--gray-400) truncate font-mono text-xs">
                                    {{ auth()->user()->email }}</p>
                            </div>

                            <a href="{{ route('admin.profile') }}"
                                class="hover:bg-(--gray-100) hover:text-(--gray-900) dark:hover:bg-white/10 dark:hover:text-(--white) text-(--gray-700) dark:text-(--gray-200) flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-200">
                                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.75" stroke-linecap="round"
                                    stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="12" cy="8" r="5" />
                                    <path d="M20 21a8 8 0 0 0-16 0" />
                                </svg>
                                <span>My profile</span>
                            </a>

                            <form method="POST" action="{{ route('logout') }}"
                                class="border-(--gray-200) mt-2 border-t pt-2 dark:border-white/10">
                                @csrf
                                <button type="submit"
                                    class="text-(--red-600) hover:bg-(--red-500)/10 dark:text-(--red-500) flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-semibold transition-colors duration-200">
                                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="1.75" stroke-linecap="round"
                                        stroke-linejoin="round" aria-hidden="true">
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
                class="min-h-0 min-w-0 flex-1 overflow-y-auto px-3 pb-12 pt-6 sm:px-8 sm:pb-18">
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

    {{-- Shared live-sync poller --}}
    @include('components.partials.live-sync')

    <script>
        const root = document.documentElement;
        const $ = (id) => document.getElementById(id);
        const notificationMenu = $('admin-notification-menu');
        const userMenu = $('admin-user-menu');
        const sidebarToggle = $('admin-sidebar-toggle');

        const sidebar = $('admin-sidebar');
        const mobileOpener = $('admin-mobile-sidebar-toggle');
        const mobileViewport = window.matchMedia('(width < 768px)');

        function syncSidebarMode() {
            if (mobileViewport.matches) {
                sidebar.setAttribute('popover', 'auto');
                sidebarToggle.setAttribute('aria-label', 'Close sidebar');
                sidebarToggle.removeAttribute('aria-expanded');
            } else {
                if (sidebar.matches(':popover-open')) sidebar.hidePopover();
                sidebar.removeAttribute('popover');
                sidebarToggle.setAttribute('aria-label', 'Toggle sidebar');
                sidebarToggle.setAttribute('aria-expanded', String(root.dataset.sidebar !== 'collapsed'));
            }
        }

        sidebar.addEventListener('toggle', () => {
            const open = sidebar.matches(':popover-open');
            mobileOpener.setAttribute('aria-expanded', String(open));
            if (open) sidebarToggle.focus();
        });

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

        syncSidebarMode();
        mobileViewport.addEventListener('change', syncSidebarMode);
        sidebarToggle?.addEventListener('click', () => {
            if (mobileViewport.matches) {
                sidebar.hidePopover();
                mobileOpener.focus();
                return;
            }
            const collapsed = root.dataset.sidebar !== 'collapsed';
            setSidebar(collapsed);
            try {
                localStorage.setItem('admin-sidebar', collapsed ? 'collapsed' : 'expanded');
            } catch (e) {}
        });

        // ---------- Live refresh: only when the server reports a change ----------
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

        async function refreshContent() {
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
        }

        startLiveSync({
            url: "{{ route('sync.version', ['scope' => 'admin']) }}",
            version: @json(\App\Support\SyncVersion::current(auth()->id(), 'admin')),
            refresh: refreshContent,
            hasUnsavedInput,
        });

        // ---------- Notification tabs (delegated, so it survives live-refresh swaps) ----------
        function applyNotifTab(filter) {
            if (!notificationMenu) return;

            const items = notificationMenu.querySelectorAll('[data-notif-item]');
            const emptyMsg = notificationMenu.querySelector('[data-notif-unread-empty]');
            let visible = 0;

            items.forEach((item) => {
                const show = filter === 'all' || item.dataset.unread === '1';
                item.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            if (emptyMsg) emptyMsg.hidden = !(filter === 'unread' && visible === 0 && items.length > 0);

            notificationMenu.querySelectorAll('[data-notif-tab]').forEach((tab) => {
                const on = tab.dataset.notifTab === filter;
                tab.classList.toggle('bg-(--blue-600)', on);
                tab.classList.toggle('text-(--white)', on);
                tab.classList.toggle('text-(--gray-900)', !on);
                tab.classList.toggle('dark:text-(--gray-50)', !on);
            });
        }

        notificationMenu?.addEventListener('click', (event) => {
            const tab = event.target.closest('[data-notif-tab]');
            if (tab) applyNotifTab(tab.dataset.notifTab);
        });

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
