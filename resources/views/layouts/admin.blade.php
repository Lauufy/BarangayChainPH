<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Dashboard') - BarangayChain PH</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="icon" type="image/png" href="{{ asset('img/branding/barangayChainPH-logo.png') }}">
</head>

<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">

    <div class="flex min-h-screen">

        {{-- Sidebar --}}
        <aside class="hidden w-64 shrink-0 border-r border-[#172A45] bg-[#09172D] text-white md:block">

            {{-- Branding --}}
            <div class="flex h-20 items-center gap-3 border-b border-[#172A45] px-4">

                <img src="{{ asset('img/branding/barangayChainPH-logo.png') }}" alt="BarangayChain PH Logo"
                    class="h-10 w-10 rounded-lg bg-white object-contain p-1">

                <div>
                    <h2 class="text-sm font-bold">BarangayChain</h2>
                    <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-emerald-400">
                        Administration
                    </p>
                </div>
            </div>

            <nav class="space-y-5 px-3 py-5">

                {{-- Overview --}}
                <div>
                    <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                        Overview
                    </p>

                    <a href="{{ url('/admin/dashboard') }}"
                        class="{{ request()->is('admin/dashboard')
                            ? 'bg-[#0B2347] text-emerald-400'
                            : 'text-slate-300 hover:bg-white/5 hover:text-white' }} flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium transition">

                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7" rx="1" />
                            <rect x="14" y="3" width="7" height="7" rx="1" />
                            <rect x="14" y="14" width="7" height="7" rx="1" />
                            <rect x="3" y="14" width="7" height="7" rx="1" />
                        </svg>

                        Dashboard
                    </a>
                </div>

                {{-- Management --}}
                <div>
                    <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                        Management
                    </p>

                    <div class="space-y-1">

                        <a href="{{ url('/admin/users') }}"
                            class="{{ request()->is('admin/users*')
                                ? 'bg-[#0B2347] text-emerald-400'
                                : 'text-slate-300 hover:bg-white/5 hover:text-white' }} flex items-center gap-3 rounded-lg px-3 py-3 text-sm transition">

                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                <circle cx="10" cy="7" r="4" />
                                <path d="M20 21v-2a4 4 0 0 0-3-3.87" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>

                            <span>Users &amp; Roles</span>
                        </a>

                        <a href="{{ url('/admin/identity-verification') }}"
                            class="{{ request()->is('admin/identity-verification*')
                                ? 'bg-[#0B2347] text-emerald-400'
                                : 'text-slate-300 hover:bg-white/5 hover:text-white' }} flex items-center gap-3 rounded-lg px-3 py-3 text-sm transition">

                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 11a2 2 0 0 0-2 2c0 1.5-.2 3-.5 4.5" />
                                <path d="M14 13a2 2 0 0 0-4 0c0 2-.3 4-.8 6" />
                                <path d="M16 13a4 4 0 0 0-8 0c0 3-.4 5.5-1.2 7.5" />
                                <path d="M18 13a6 6 0 0 0-12 0" />
                                <path d="M12 3a10 10 0 0 0-10 10" />
                                <path d="M22 13a10 10 0 0 0-2-6" />
                            </svg>

                            <span>Identity Verification</span>
                        </a>

                        <a href="{{ url('/admin/permits') }}"
                            class="{{ request()->is('admin/permits*')
                                ? 'bg-[#0B2347] text-emerald-400'
                                : 'text-slate-300 hover:bg-white/5 hover:text-white' }} flex items-center gap-3 rounded-lg px-3 py-3 text-sm transition">

                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7z" />
                                <path d="M14 2v6h6" />
                                <path d="m9 15 2 2 4-4" />
                            </svg>

                            <span>Permit Management</span>
                        </a>
                    </div>
                </div>

                {{-- Documents --}}
                <div>
                    <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                        Documents &amp; Verification
                    </p>

                    <a href="{{ url('/admin/document-authentication') }}"
                        class="{{ request()->is('admin/document-authentication*')
                            ? 'bg-[#0B2347] text-emerald-400'
                            : 'text-slate-300 hover:bg-white/5 hover:text-white' }} flex items-center gap-3 rounded-lg px-3 py-3 text-sm transition">

                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 22h14a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2z" />
                            <path d="M14 2v6h6" />
                            <path d="m9 15 2 2 4-4" />
                        </svg>

                        <span>Document Authentication</span>
                    </a>
                </div>

                {{-- Blockchain --}}
                <div>
                    <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                        Blockchain
                    </p>

                    <a href="{{ url('/admin/blockchain-ledger') }}"
                        class="{{ request()->is('admin/blockchain-ledger*')
                            ? 'bg-[#0B2347] text-emerald-400'
                            : 'text-slate-300 hover:bg-white/5 hover:text-white' }} flex items-center gap-3 rounded-lg px-3 py-3 text-sm transition">

                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7" rx="1" />
                            <rect x="14" y="3" width="7" height="7" rx="1" />
                            <rect x="14" y="14" width="7" height="7" rx="1" />
                            <rect x="3" y="14" width="7" height="7" rx="1" />
                        </svg>

                        <span>Blockchain Ledger</span>
                    </a>
                </div>

                {{-- Communication --}}
                <div>
                    <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                        Communication
                    </p>

                    <a href="{{ url('/admin/notifications') }}"
                        class="{{ request()->is('admin/notifications*')
                            ? 'bg-[#0B2347] text-emerald-400'
                            : 'text-slate-300 hover:bg-white/5 hover:text-white' }} flex items-center gap-3 rounded-lg px-3 py-3 text-sm transition">

                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />
                            <path d="M10 21h4" />
                        </svg>

                        <span>Notifications &amp; Messaging</span>
                    </a>
                </div>

                {{-- Insights and Security --}}
                <div>
                    <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                        Insights &amp; Security
                    </p>

                    <div class="space-y-1">

                        <a href="{{ url('/admin/reports') }}"
                            class="{{ request()->is('admin/reports*')
                                ? 'bg-[#0B2347] text-emerald-400'
                                : 'text-slate-300 hover:bg-white/5 hover:text-white' }} flex items-center gap-3 rounded-lg px-3 py-3 text-sm transition">

                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 3v18h18" />
                                <path d="m19 9-5 5-4-4-5 5" />
                            </svg>

                            <span>Reports &amp; Analytics</span>
                        </a>

                        <a href="{{ url('/admin/audit-logs') }}"
                            class="{{ request()->is('admin/audit-logs*')
                                ? 'bg-[#0B2347] text-emerald-400'
                                : 'text-slate-300 hover:bg-white/5 hover:text-white' }} flex items-center gap-3 rounded-lg px-3 py-3 text-sm transition">

                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="8" y="2" width="8" height="4" rx="1" />
                                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" />
                                <path d="M8 11h8M8 15h8M8 19h4" />
                            </svg>

                            <span>Audit Logs</span>
                        </a>
                    </div>
                </div>

                <div class="border-t border-[#172A45] pt-4">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button type="submit"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium text-red-400 transition hover:bg-red-500/10 hover:text-red-300">

                            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                <path d="M16 17l5-5-5-5" />
                                <path d="M21 12H9" />
                            </svg>

                            <span>Logout</span>
                        </button>
                    </form>
                </div>

            </nav>
        </aside>

        {{-- Main Area --}}
        <div class="flex min-w-0 flex-1 flex-col">

            {{-- Header --}}
            <header
                class="sticky top-0 z-40 flex h-16 shrink-0 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6">

                <div class="min-w-0">
                    <h1 class="text-lg font-bold text-[#09172D]">
                        @yield('title', 'Dashboard')
                    </h1>

                    <p class="hidden text-xs text-slate-500 sm:block">
                        BarangayChain PH Administration
                    </p>
                </div>

                <div class="flex shrink-0 items-center gap-3 sm:gap-5">

                    {{-- Notifications --}}
                    <button type="button" aria-label="Notifications"
                        class="relative rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-emerald-700">

                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />
                            <path d="M10 21h4" />
                        </svg>

                        <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-emerald-500"></span>
                    </button>

                    <div class="h-8 border-l border-slate-200"></div>

                    {{-- Administrator --}}
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-semibold text-[#09172D]">
                            {{ auth()->user()->name ?? 'Administrator' }}
                        </p>

                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                            Admin
                        </p>
                    </div>

                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white ring-2 ring-emerald-100">
                        {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                    </div>
                </div>
            </header>

            {{-- Page Content --}}
            <main class="min-w-0 flex-1 bg-blue-950 p-4 sm:p-6 lg:p-7">
                @yield('content')
            </main>

        </div>
    </div>

</body>

</html>
