<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Authentication') | BarangayChain PH</title>

    <link rel="icon" type="image/png" href="{{ asset('img/branding/barangaychain-lg2.png') }}">

    {{-- Follow the saved theme (set by the toggle on other pages) before first paint --}}
    <script>
        try {
            const t = localStorage.getItem('theme');
            document.documentElement.classList.toggle('dark', t ? t === 'dark' : matchMedia('(prefers-color-scheme: dark)').matches);
        } catch (e) {}
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-dvh bg-(--white) text-(--gray-900) antialiased transition-colors duration-300 dark:bg-(--black-700) dark:text-(--gray-50)">

    <div class="relative grid min-h-dvh grid-cols-1 items-stretch lg:grid-cols-2">

        {{-- Back navigation --}}
        <header class="absolute inset-x-0 top-0 z-10 px-4 pt-5 sm:px-6 sm:pt-6">
            <x:navigation.back-nav />
        </header>

        {{-- Authentication form --}}
        <main class="flex min-w-0 flex-col px-4 pb-8 pt-24 sm:px-6 sm:pb-10 sm:pt-28 lg:pt-24">
            <div class="mx-auto my-auto w-full max-w-md py-4">
                @yield('content')
            </div>
        </main>

        {{-- Journey panel: desktop only --}}
        <div class="hidden min-w-0 lg:flex lg:items-stretch">
            @yield('panel')
        </div>

    </div>

    <x:feedbacks.toast />

</body>

</html>