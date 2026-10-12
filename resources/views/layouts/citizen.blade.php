<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Citizen Portal') | Barangay San Luis</title>
    <link rel="icon" type="image/png" href="{{ asset('img/branding/barangayChainPH-logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-[#f7f8f4] text-slate-800 antialiased">
    <div class="flex min-h-screen flex-col">
        <div class="bg-[#123d30] text-white">
            <div class="mx-auto flex min-h-10 max-w-7xl items-center justify-between gap-4 px-4 text-xs sm:px-6 lg:px-8">
                <p class="font-medium text-white/90">Official Barangay San Luis Website</p>
                <p class="hidden text-white/70 sm:block">Antipolo City, Rizal · Republic of the Philippines</p>
            </div>
        </div>

        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div class="mx-auto flex min-h-21 max-w-7xl items-center justify-between gap-6 px-4 sm:px-6 lg:px-8">
                <a href="{{ route('citizen.dashboard') }}" class="flex min-w-0 items-center gap-3">
                    <img src="{{ asset('img/branding/barangayChainPH-logo.png') }}" alt="Barangay San Luis seal" class="h-12 w-12 shrink-0 rounded-full border border-slate-200 object-contain p-0.5">
                    <span class="min-w-0 leading-tight">
                        <span class="block text-[10px] font-bold uppercase tracking-[0.15em] text-emerald-800">Barangay Government</span>
                        <span class="mt-1 block truncate text-base font-extrabold tracking-tight text-[#173a2e] sm:text-lg">Barangay San Luis</span>
                        <span class="mt-0.5 block text-xs text-slate-500">Antipolo City, Rizal</span>
                    </span>
                </a>

                <nav aria-label="Main navigation" class="hidden items-center gap-1 lg:flex">
                    <a href="{{ route('citizen.dashboard') }}" @class([
                        'rounded-lg px-3 py-2.5 text-sm font-semibold transition',
                        'bg-emerald-50 text-emerald-900' => request()->routeIs('citizen.dashboard'),
                        'text-slate-600 hover:bg-slate-50 hover:text-emerald-900' => ! request()->routeIs('citizen.dashboard'),
                    ])>Home</a>
                    <a href="{{ route('citizen.services') }}" @class([
                        'rounded-lg px-3 py-2.5 text-sm font-semibold transition',
                        'bg-emerald-50 text-emerald-900' => request()->routeIs('citizen.services'),
                        'text-slate-600 hover:bg-slate-50 hover:text-emerald-900' => ! request()->routeIs('citizen.services'),
                    ])>Services</a>
                    <a href="{{ route('citizen.applications') }}" @class([
                        'rounded-lg px-3 py-2.5 text-sm font-semibold transition',
                        'bg-emerald-50 text-emerald-900' => request()->routeIs('citizen.applications'),
                        'text-slate-600 hover:bg-slate-50 hover:text-emerald-900' => ! request()->routeIs('citizen.applications'),
                    ])>Applications</a>
                    <a href="{{ route('citizen.documents') }}" @class([
                        'rounded-lg px-3 py-2.5 text-sm font-semibold transition',
                        'bg-emerald-50 text-emerald-900' => request()->routeIs('citizen.documents'),
                        'text-slate-600 hover:bg-slate-50 hover:text-emerald-900' => ! request()->routeIs('citizen.documents'),
                    ])>Documents</a>
                    <a href="{{ route('citizen.identity-verification') }}" @class([
                        'rounded-lg px-3 py-2.5 text-sm font-semibold transition',
                        'bg-emerald-50 text-emerald-900' => request()->routeIs('citizen.identity-verification'),
                        'text-slate-600 hover:bg-slate-50 hover:text-emerald-900' => ! request()->routeIs('citizen.identity-verification'),
                    ])>Identity</a>
                </nav>

                <div class="hidden items-center gap-3 lg:flex">
                    <a href="{{ route('citizen.profile') }}" class="flex items-center gap-2.5 rounded-full border border-slate-200 py-1.5 pl-1.5 pr-3 transition hover:border-emerald-300 hover:bg-emerald-50">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-900">{{ strtoupper(substr(auth()->user()->name ?? 'C', 0, 1)) }}</span>
                        <span class="max-w-32 truncate text-sm font-semibold text-slate-700">{{ auth()->user()->name }}</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-500 transition hover:bg-red-50 hover:text-red-700">Sign out</button>
                    </form>
                </div>

                <details class="relative lg:hidden">
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg border border-slate-200 px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-900">{{ strtoupper(substr(auth()->user()->name ?? 'C', 0, 1)) }}</span>
                        <span class="hidden max-w-28 truncate sm:inline">{{ auth()->user()->name }}</span>
                        <svg class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                    </summary>
                    <nav aria-label="Mobile navigation" class="absolute right-0 mt-3 w-64 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">
                        <a class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-900" href="{{ route('citizen.dashboard') }}">Home</a>
                        <a class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-900" href="{{ route('citizen.services') }}">Barangay services</a>
                        <a class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-900" href="{{ route('citizen.applications') }}">My applications</a>
                        <a class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-900" href="{{ route('citizen.documents') }}">My documents</a>
                        <a class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-900" href="{{ route('citizen.identity-verification') }}">Identity verification</a>
                        <a class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:text-emerald-900" href="{{ route('citizen.profile') }}">My profile</a>
                        <form method="POST" action="{{ route('logout') }}" class="mt-2 border-t border-slate-100 pt-2">
                            @csrf
                            <button type="submit" class="w-full rounded-lg px-3 py-2.5 text-left text-sm font-semibold text-red-700 hover:bg-red-50">Sign out</button>
                        </form>
                    </nav>
                </details>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 sm:py-10 lg:px-8 lg:py-12">
            <x:feedbacks.toast />
            @yield('content')
        </main>

        <footer class="mt-auto bg-[#173a2e] text-white">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-9 sm:px-6 md:grid-cols-[1fr_auto] md:items-center lg:px-8">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('img/branding/barangayChainPH-logo.png') }}" alt="" class="h-10 w-10 rounded-full bg-white object-contain p-0.5">
                    <div>
                        <p class="text-sm font-bold">Barangay San Luis</p>
                        <p class="mt-1 text-xs text-white/65">Antipolo City, Rizal · Citizen Services</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-white/75">
                    <a href="{{ route('privacy') }}" class="transition hover:text-white">Privacy notice</a>
                    <a href="{{ route('terms') }}" class="transition hover:text-white">Terms of service</a>
                    <a href="{{ route('citizen.profile') }}" class="transition hover:text-white">My profile</a>
                </div>
                <p class="border-t border-white/15 pt-5 text-xs text-white/55 md:col-span-2">© {{ now()->year }} Barangay San Luis, Antipolo City. Official citizen service portal.</p>
            </div>
        </footer>
    </div>
</body>

</html>
