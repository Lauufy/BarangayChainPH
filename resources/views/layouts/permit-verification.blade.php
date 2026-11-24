<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>@yield('title') | {{ $barangayName }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/branding/barangaychain-lg1.png') }}">
    @vite(['resources/css/app.css'])
</head>

<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased selection:bg-blue-100 selection:text-blue-950 dark:bg-slate-950 dark:text-slate-100">

    <main class="mx-auto w-full max-w-2xl px-5 pb-12 pt-8 sm:px-8 sm:pb-16 sm:pt-12">
        <header class="mb-7 space-y-2">
            <h1 class="text-3xl font-semibold tracking-tight text-[#17325c] dark:text-white">@yield('title')</h1>
            <p class="text-sm text-slate-600 dark:text-slate-300">{{ $barangayName }}</p>
        </header>

        @yield('content')
    </main>
</body>

</html>
