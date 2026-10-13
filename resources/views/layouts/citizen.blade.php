<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Citizen Portal') | Barangay San Luis</title>
    <link rel="icon" type="image/png" href="{{ asset('img/branding/barangayChainPH-logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    {{-- Public Sans = body text, Barlow Condensed = brand name --}}
    <link
        href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700&family=Public+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="flex min-h-screen flex-col bg-slate-50 font-['Public_Sans',sans-serif] text-slate-800">

    {{-- Government bar --}}
    <div class="bg-blue-950 text-xs text-white">
        <div class="mx-auto flex h-9 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
            <span class="truncate text-blue-100">Official Barangay Website</span>
            <span class="hidden text-blue-200 sm:block">Republic of the Philippines | Antipolo City, Rizal</span>
        </div>
    </div>

    {{-- Header --}}
    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">

            <x-partials.logo />

            {{-- Page links (desktop only) --}}
            <nav class="hidden gap-1 lg:flex">
                @foreach (['citizen.dashboard' => 'Home', 'citizen.services' => 'Services', 'citizen.applications' => 'Applications', 'citizen.documents' => 'Documents'] as $route => $label)
                    <a href="{{ route($route) }}" @class([
                        'rounded-md px-3 py-2 text-sm font-semibold',
                        'bg-slate-100 text-[#17325c]' => request()->routeIs($route),
                        'text-slate-600 hover:bg-slate-50' => !request()->routeIs($route),
                    ])>{{ $label }}</a>
                @endforeach
            </nav>

            {{-- User dropdown --}}
            <details id="user-menu" class="group relative shrink-0">

                {{-- Button --}}
                <summary class="flex cursor-pointer list-none items-center gap-3 rounded-lg p-1.5 hover:bg-slate-100 [&::-webkit-details-marker]:hidden">

                    {{-- Desktop: name, photo, arrow --}}
                    <span class="hidden text-right lg:block">
                        <span class="block max-w-40 truncate text-sm font-bold">{{ auth()->user()->name }}</span>
                        <span class="block text-xs text-slate-500">Citizen</span>
                    </span>

                    @if (auth()->user()->citizenProfile?->photo_path)
                        <img src="{{ route('citizen.profile.photo.show') }}" alt="" class="hidden h-10 w-10 rounded-full object-cover lg:block">
                    @else
                        <span class="hidden h-10 w-10 items-center justify-center rounded-full bg-[#17325c] font-bold text-white lg:flex">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>
                    @endif

                    {{-- Mobile: menu icon, becomes X when open --}}
                    <span class="flex h-10 w-10 items-center justify-center rounded-md bg-[#eef5ff] lg:hidden">
                        <svg class="h-6 w-6 group-open:hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg class="hidden h-6 w-6 group-open:block" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                        </svg>
                    </span>
                </summary>

                {{-- Panel --}}
                <div class="absolute right-0 z-40 mt-2 w-72 max-w-[calc(100vw-2rem)] rounded-xl border border-slate-200 bg-white p-2 shadow-xl">

                    {{-- Name and email --}}
                    <div class="mb-2 border-b border-slate-100 px-3 pb-3 pt-2">
                        <p class="truncate font-bold text-slate-900">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                    </div>

                    {{-- Page links (phone and tablet only) --}}
                    <div class="mb-2 border-b border-slate-100 pb-2 lg:hidden">
                        @foreach (['citizen.dashboard' => 'Home', 'citizen.services' => 'Services', 'citizen.applications' => 'Applications', 'citizen.documents' => 'Documents'] as $route => $label)
                            <a href="{{ route($route) }}" class="block rounded-md px-3 py-2.5 text-sm hover:bg-slate-100">{{ $label }}</a>
                        @endforeach
                    </div>

                    {{-- Identity verification with colored badge --}}
                    <a href="{{ route('citizen.identity-verification') }}"
                        class="flex items-center justify-between rounded-md px-3 py-2.5 text-sm hover:bg-slate-100">
                        Identity verification
                        @if (auth()->user()->isIdentityVerified())
                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">Verified</span>
                        @elseif (auth()->user()->identityStatus() === 'pending')
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700">Pending</span>
                        @else
                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">Required</span>
                        @endif
                    </a>

                    <a href="{{ route('citizen.profile') }}" class="block rounded-md px-3 py-2.5 text-sm hover:bg-slate-100">My profile</a>

                    {{-- Sign out --}}
                    <form method="POST" action="{{ route('logout') }}" class="mt-2 border-t border-slate-100 pt-2">
                        @csrf
                        <button class="w-full rounded-md px-3 py-2.5 text-left text-sm font-semibold text-red-600 hover:bg-red-50">Sign out</button>
                    </form>
                </div>
            </details>
        </div>
    </header>

    {{-- Page content --}}
    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
        <x:feedbacks.toast />

        {{-- Unread notifications --}}
        @foreach (auth()->user()->unreadNotifications as $notification)
            <div class="mb-4 flex flex-col gap-2 rounded-md border border-blue-200 bg-blue-50 p-4 sm:flex-row sm:justify-between sm:gap-4">
                <div>
                    <p class="text-sm font-semibold text-[#17325c]">{{ $notification->data['title'] }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $notification->data['message'] }}</p>
                </div>
                <form method="POST" action="{{ route('citizen.notifications.read', $notification->id) }}">
                    @csrf
                    <button class="text-sm font-semibold text-[#17325c] underline">Dismiss</button>
                </form>
            </div>
        @endforeach

        @yield('content')
    </main>

    {{-- Footer --}}
    <x-partials.c-footer />

    <script>
        // Close the dropdown when clicking outside it
        const menu = document.getElementById('user-menu');
        document.addEventListener('click', (e) => {
            if (!menu.contains(e.target)) menu.removeAttribute('open');
        });
    </script>
</body>

</html>