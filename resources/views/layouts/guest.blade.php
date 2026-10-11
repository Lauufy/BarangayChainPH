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

    <header class="m-2 px-6 pt-6">
        <x:navigation.back-nav />
    </header>

    <main class="flex items-center justify-center px-4 py-10 sm:px-6">
        <div class="w-full max-w-md">
            @yield('content')
        </div>
    </main>


    <x:feedbacks.toast />

</body>

</html>
