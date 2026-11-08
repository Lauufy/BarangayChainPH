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

<body class="min-h-screen bg-(--white) text-(--gray-900) antialiased transition-colors duration-300 dark:bg-(--black-700) dark:text-(--gray-50)">

    <header class="m-2 px-6 pt-6">
        <x:navigation.back-nav />
    </header>

    <main class="flex items-center justify-center px-4 py-8 sm:px-6">
        <div class="w-full max-w-md">
            @yield('content')
        </div>
    </main>

    <x:feedbacks.toast />

</body>

</html>