@props(['title', 'updated'])

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title }} | BarangayChain PH</title>

    <link rel="icon" type="image/png" href="{{ asset('img/branding/barangayChainPH-logo.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-dvh bg-[#020A18] font-sans text-white antialiased">

    <main class="mx-auto w-full max-w-3xl px-5 py-8 sm:px-8">

        {{-- Top bar: Back (left) and name (right) --}}
        <div class="mb-12 flex items-center justify-between">
            <a href="{{ url('/') }}"
                class="inline-flex items-center gap-2 text-sm font-medium text-[#A8A29E] transition hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400">

                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5m7 7-7-7 7-7" />
                </svg>

                <span>Back</span>
            </a>

            <span class="text-sm font-bold uppercase tracking-[0.18em] text-emerald-400">
                BarangayChain PH
            </span>
        </div>

        {{-- Page header --}}
        <header class="mb-10 border-b border-white/10 pb-8">
            <p class="mb-3 text-xs font-bold uppercase tracking-[0.22em] text-emerald-400">
                Legal
            </p>

            <h1 class="text-4xl font-bold tracking-tight text-white">
                {{ $title }}
            </h1>

            <p class="mt-3 text-sm text-blue-100/50">
                Last updated: {{ $updated }}
            </p>
        </header>

        {{-- Page content --}}
        <div class="space-y-10">
            {{ $slot }}
        </div>

        {{-- Footer --}}
        <footer class="mt-14 flex flex-wrap items-center justify-between gap-3 border-t border-white/10 pt-6 text-xs text-blue-100/40">
            <span>&copy; {{ date('Y') }} BarangayChain PH. All rights reserved.</span>

            <span class="flex gap-4">
                <a href="{{ route('terms') }}" class="transition hover:text-white">Terms of Service</a>
                <a href="{{ route('privacy') }}" class="transition hover:text-white">Privacy Policy</a>
            </span>
        </footer>

    </main>

</body>

</html>