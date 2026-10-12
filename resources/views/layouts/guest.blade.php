<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Authentication') | BarangayChain PH</title>

    <link rel="icon" type="image/png" href="{{ asset('img/branding/barangayChainPH-logo.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-[#020A18] text-slate-100 antialiased">

    <div class="relative grid min-h-screen lg:grid-cols-2">

        {{-- Back navigation: floats over the top of the page --}}
        <header class="absolute inset-x-0 top-0 z-10 px-6 pt-6">
            <x:navigation.back-nav />
        </header>

        {{-- Form --}}
        <main class="pt-35 px-4 pb-10 sm:px-6">
            <div class="mx-auto w-full max-w-md">
                @yield('content')
            </div>
        </main>

        {{-- Panel (hidden on phones and tablets) --}}
        <div class="hidden lg:block">
            @yield('panel')
        </div>

    </div>

    <x:feedbacks.toast />

</body>

</html>
