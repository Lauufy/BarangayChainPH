<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Dashboard') - BarangayChain PH</title>

    <link rel="icon" type="image/png" href="{{ asset('img/branding/barangayChainPH-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-screen overflow-hidden bg-white font-sans text-slate-800 antialiased">

    <div class="flex h-screen overflow-hidden">

        {{-- Sidebar (dark) --}}
        <aside
            class="admin-sidebar-scroll fixed inset-y-0 left-0 z-50 hidden h-screen w-64 overflow-y-auto border-r border-[#172A45] bg-[#09172D] text-white md:block">

            {{-- Branding --}}
            <div class="flex h-16 items-center gap-3 border-b border-white/10 px-4">
                <img src="{{ asset('img/branding/barangayChainPH-logo.png') }}" alt="BarangayChain PH Logo"
                    class="h-10 w-10 shrink-0 rounded-md bg-white object-contain p-1">

                <div class="min-w-0">
                    <p class="whitespace-nowrap font-['Barlow_Condensed'] text-xl font-bold leading-none tracking-wide">
                        <span class="text-white">BARANGAYCHAIN PH</span>
                    </p>
                    <p class="mt-1 truncate text-[10px] font-semibold uppercase tracking-widest text-emerald-300">
                        Administration
                    </p>
                </div>
            </div>

            <nav class="space-y-6 px-3 py-5">

                {{-- Overview --}}
                <div>
                    <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Overview
                    </p>

                    <x-admin.nav-link href="/admin/dashboard" match="admin/dashboard">
                        <x-slot:icon>
                            <rect x="3" y="3" width="7" height="9" rx="1" />
                            <rect x="14" y="3" width="7" height="5" rx="1" />
                            <rect x="14" y="12" width="7" height="9" rx="1" />
                            <rect x="3" y="16" width="7" height="5" rx="1" />
                        </x-slot:icon>
                        Dashboard
                    </x-admin.nav-link>
                </div>

                {{-- Management --}}
                <div>
                    <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Management
                    </p>

                    <div class="space-y-1">
                        <x-admin.nav-link href="/admin/users" match="admin/users*">
                            <x-slot:icon>
                                <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                <circle cx="10" cy="7" r="4" />
                                <path d="M20 21v-2a4 4 0 0 0-3-3.87" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </x-slot:icon>
                            Users &amp; Roles
                        </x-admin.nav-link>

                        <x-admin.nav-link href="/admin/identity-verification" match="admin/identity-verification*">
                            <x-slot:icon>
                                <rect x="2" y="5" width="20" height="14" rx="2" />
                                <circle cx="9" cy="11" r="2" />
                                <path d="M6.17 15a3 3 0 0 1 5.66 0" />
                                <path d="M15 10h3M15 14h3" />
                            </x-slot:icon>
                            Identity Verification
                        </x-admin.nav-link>

                        <x-admin.nav-link href="/admin/permits" match="admin/permits*">
                            <x-slot:icon>
                                <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7z" />
                                <path d="M14 2v6h6" />
                                <path d="m9 15 2 2 4-4" />
                            </x-slot:icon>
                            Permit Management
                        </x-admin.nav-link>
                    </div>
                </div>

                {{-- Documents --}}
                <div>
                    <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Documents
                        &amp; Verification</p>

                    <x-admin.nav-link href="/admin/document-authentication" match="admin/document-authentication*">
                        <x-slot:icon>
                            <path
                                d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                            <path d="m9 12 2 2 4-4" />
                        </x-slot:icon>
                        Document Authentication
                    </x-admin.nav-link>
                </div>

                {{-- Blockchain --}}
                <div>
                    <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Blockchain
                    </p>

                    <x-admin.nav-link href="/admin/blockchain-ledger" match="admin/blockchain-ledger*">
                        <x-slot:icon>
                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                        </x-slot:icon>
                        Blockchain Ledger
                    </x-admin.nav-link>
                </div>

                {{-- Communication --}}
                <div>
                    <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                        Communication</p>

                    <x-admin.nav-link href="/admin/tickets" match="admin/tickets*">
                        <x-slot:icon>
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                        </x-slot:icon>
                        Ticket Messages
                    </x-admin.nav-link>
                </div>

                {{-- Insights and Security --}}
                <div>
                    <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Insights
                        &amp; Security</p>

                    <div class="space-y-1">
                        <x-admin.nav-link href="/admin/reports" match="admin/reports*">
                            <x-slot:icon>
                                <path d="M3 3v18h18" />
                                <path d="M18 17V9M13 17V5M8 17v-3" />
                            </x-slot:icon>
                            Reports &amp; Analytics
                        </x-admin.nav-link>

                        <x-admin.nav-link href="/admin/audit-logs" match="admin/audit-logs*">
                            <x-slot:icon>
                                <rect x="8" y="2" width="8" height="4" rx="1" />
                                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" />
                                <path d="M8 11h8M8 15h8M8 19h4" />
                            </x-slot:icon>
                            Audit Logs
                        </x-admin.nav-link>
                    </div>
                </div>

                {{-- Logout --}}
                <div class="border-t border-white/10 pt-4">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium text-red-400 transition hover:bg-red-500/10 hover:text-red-300">
                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                <path d="M16 17l5-5-5-5" />
                                <path d="M21 12H9" />
                            </svg>
                            Logout
                        </button>
                    </form>
                </div>
            </nav>
        </aside>

        {{-- Main area (white) --}}
        <div class="flex h-screen min-w-0 flex-1 flex-col overflow-hidden md:ml-64">

            {{-- Header --}}
            <header
                class="relative z-40 flex h-16 shrink-0 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6">

                <div class="min-w-0">
                    <h1 class="truncate text-lg font-bold text-slate-900">@yield('title', 'Dashboard')</h1>
                    <p class="hidden text-xs text-slate-500 sm:block">Barangay San Luis | Antipolo City</p>
                </div>

                <div class="flex shrink-0 items-center gap-3 sm:gap-4">

                    {{-- Notifications --}}
                    <button type="button" aria-label="Notifications"
                        class="relative rounded-md p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />
                            <path d="M10 21h4" />
                        </svg>
                        <span
                            class="absolute right-2 top-2 h-2 w-2 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                    </button>

                    <div class="h-8 border-l border-slate-200"></div>

                    {{-- Administrator --}}
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-semibold leading-tight text-slate-900">
                            {{ auth()->user()->name ?? 'Administrator' }}</p>
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-emerald-600">Admin</p>
                    </div>

                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-[#17325c] text-sm font-bold text-white">
                        {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                    </div>
                </div>
            </header>

            {{-- Page content --}}
            <main class="min-h-0 min-w-0 flex-1 overflow-y-auto bg-white p-4 sm:p-6 lg:p-7">
                @yield('content')
            </main>
        </div>
    </div>

</body>

</html>
