@props(['title', 'updated'])

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Legal') | BarangayChain PH</title>

    <link rel="icon" type="image/png" href="{{ asset('img/branding/barangayChainPH-logo.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-dvh bg-[#020A18] font-sans text-white antialiased">

    <header class="m-2 px-6 pt-6">
        <x:navigation.back-nav />
    </header>

    <main class="mx-auto w-full max-w-3xl px-5 py-8 sm:px-8">
        @yield('content')

    </main>
    {{-- Footer --}}
    <footer
        class="mt-10 mb-14 mx-5 flex flex-wrap items-center justify-between gap-3 border-t border-white/10 pt-6 text-xs text-blue-100/40">
        <span>&copy; {{ date('Y') }} BarangayChain PH. All rights reserved.</span>

        <span class="flex gap-4">
            <a href="{{ route('terms') }}" class="transition hover:text-white">Terms of Service</a>
            <a href="{{ route('privacy') }}" class="transition hover:text-white">Privacy Policy</a>
        </span>
    </footer>

</body>

</html>
