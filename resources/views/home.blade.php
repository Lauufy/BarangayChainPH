<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-pt-24 scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description"
        content="BarangayChain PH is the online service portal of Barangay San Luis, Antipolo City, Rizal. Request barangay certificates, track your application, and verify documents.">
    <title>BarangayChain PH — Barangay San Luis, Antipolo City</title>
    <script>
        document.documentElement.classList.add('js')
    </script>

    {{-- Font: IBM Plex Sans (set once in theme.css on body) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap">

    <style>
        body {
            overflow-x: clip;
        }

        :focus-visible {
            outline: 2px solid var(--blue-600);
            outline-offset: 3px;
        }

        /* Fade-in on scroll (only when JavaScript is on) */
        .js [data-reveal] {
            opacity: 0;
            transform: translateY(14px);
            transition: opacity .55s ease, transform .55s ease;
            transition-delay: var(--d, 0ms);
        }

        .js [data-reveal].is-visible {
            opacity: 1;
            transform: none;
        }

        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }

            .js [data-reveal] {
                opacity: 1;
                transform: none;
                transition: none;
            }
        }
    </style>

    <link rel="icon" type="image/png" href="{{ asset('img/branding/barangaychain-lg2.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

{{--
    Notes
    - Colors: one brand color in resources/css/theme.css. Use the color names from its @theme block (bg-(--gray-50) dark:bg-(--gray-900), text-(--gray-900) dark:text-(--gray-50), border-(--gray-200) dark:border-(--gray-700), bg-(--blue-600), text-(--green-600) dark:text-(--green-300), ...). No hex, no Tailwind palette colors.
    - Dark mode: each part has its own switch at the top of theme.css. The verify section uses always-dark, so it stays navy in both modes.
    - $home decides where Apply / Track / service buttons go. $apply is $home for signed-in users, register for guests.
    - $announcements can still be passed from the controller (title, date, summary, url). Max 3 shown.
    - $band is the translucent light navy tint used by Services, Announcements and Contact.
    - Separation uses soft shadows instead of borders (header, bands, cards, contact, map).
--}}
@php
    $authed = auth()->check();
    $home = route(
        match (auth()->user()?->role) {
            'admin', 'staff' => 'admin.dashboard',
            'citizen' => 'citizen.dashboard',
            default => 'login',
        },
    );
    $apply = $authed ? $home : route('register');
    $news = collect($announcements ?? [])->take(3);
    $links = array_merge(
        [['#about', 'About'], ['#services', 'Services'], ['#process', 'How it works']],
        $news->isNotEmpty() ? [['#announcements', 'Updates']] : [],
        [['#verify', 'Verify'], ['#faq', 'FAQ'], ['#contact', 'Contact']],
    );

    // Shared class strings
    $chev = 'M8.25 4.5l7.5 7.5-7.5 7.5';
    $btn =
        'inline-flex items-center justify-center gap-2 rounded-md bg-(--blue-600) px-5 py-3 text-sm font-semibold text-(--white) transition duration-200 hover:-translate-y-0.5 hover:bg-(--blue-700) hover:shadow-md';
    $btnOutline =
        'inline-flex items-center justify-center rounded-md border border-(--blue-600) px-5 py-3 text-sm font-semibold text-(--blue-700) dark:text-(--blue-300) transition-colors duration-200 hover:bg-[color-mix(in_srgb,var(--blue-600)_10%,transparent)]';
    $eyebrow = 'text-sm font-semibold text-(--green-600) dark:text-(--green-300)';
    $h2 = 'mt-2 text-3xl font-bold tracking-tight text-(--gray-900) dark:text-(--gray-50) sm:text-4xl';
    $textLink =
        'inline-flex w-fit items-center gap-2 text-sm font-semibold text-(--blue-700) dark:text-(--blue-300) underline decoration-(--green-500) decoration-2 underline-offset-4 transition-colors hover:text-(--blue-800) dark:hover:text-(--blue-200)';
    // Soft shadow for cards (a faint blue glow in dark mode so it stays visible on black)
    $glass =
        'shadow-[0_6px_24px_-8px_rgba(23,50,92,0.22)] dark:shadow-[0_6px_28px_-8px_rgba(59,130,246,0.18)] bg-[color-mix(in_srgb,var(--white)_80%,transparent)] dark:bg-[color-mix(in_srgb,var(--gray-800)_80%,transparent)] backdrop-blur-xl';
    // Translucent light navy tint with a soft shadow above and below (stronger tint in dark mode)
    $band =
        'relative z-10 shadow-[0_0_40px_-10px_rgba(23,50,92,0.25)] dark:shadow-[0_0_40px_-10px_rgba(59,130,246,0.18)] bg-[color-mix(in_srgb,var(--blue-900)_8%,transparent)] dark:bg-[color-mix(in_srgb,var(--blue-900)_40%,transparent)]';
@endphp

<body
    class="bg-(--gray-50) text-(--gray-700) dark:text-(--gray-200) dark:bg-(--black-700) antialiased transition-colors duration-300">

    <x-feedbacks.toast />

    <a href="#main"
        class="z-60 bg-(--white) dark:bg-(--gray-800) text-(--blue-700) dark:text-(--blue-300) sr-only rounded-md px-4 py-2 text-sm font-semibold focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip
        to main content</a>

    {{-- Header (mobile menu below 1280px) --}}
    <header
        class="sticky top-0 z-50 bg-[color-mix(in_srgb,var(--white)_80%,transparent)] shadow-[0_4px_20px_-8px_rgba(23,50,92,0.18)] backdrop-blur-xl transition-shadow duration-300 dark:bg-[color-mix(in_srgb,var(--black-700)_50%,transparent)] dark:shadow-[0_4px_24px_-6px_rgba(59,130,246,0.16)]">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-5 py-4 sm:px-8">
            <x-partials.logo />

            <nav aria-label="Main"
                class="text-(--gray-600) dark:text-(--gray-300) hidden items-center gap-5 text-sm xl:flex">
                @foreach ($links as [$href, $text])
                    <a href="{{ $href }}"
                        class="aria-current:font-semibold aria-current:text-(--gray-900) dark:aria-current:text-(--gray-50) aria-current:after:scale-x-100 after:bg-(--green-600) hover:text-(--gray-900) dark:hover:text-(--gray-50) focus-visible:text-(--gray-900) dark:focus-visible:text-(--gray-50) relative py-1 transition-colors after:absolute after:inset-x-0 after:-bottom-0.5 after:h-0.5 after:origin-left after:scale-x-0 after:transition-transform after:duration-200 hover:after:scale-x-100 focus-visible:after:scale-x-100">{{ $text }}</a>
                @endforeach
            </nav>

            <div class="hidden items-center gap-4 xl:flex">
                <x-partials.theme-toggle />
                @auth
                    <a href="{{ $home }}" class="{{ $btn }}">Continue</a>
                @else
                    <a href="{{ route('login') }}"
                        class="text-(--blue-700) dark:text-(--blue-300) hover:text-(--blue-800) dark:hover:text-(--blue-200) text-sm font-semibold transition-colors">Sign
                        in</a>
                    <a href="{{ route('register') }}" class="{{ $btn }}">Create account</a>
                @endauth
            </div>

            <div class="flex items-center gap-1 xl:hidden">
                <x-partials.theme-toggle />
                <button type="button" id="menu-button" aria-label="Toggle menu" aria-controls="mobile-menu"
                    aria-expanded="false"
                    class="text-(--gray-900) dark:text-(--gray-50) hover:bg-(--gray-100) dark:hover:bg-(--gray-800) rounded-md p-2 transition-colors">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path id="icon-open" stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" />
                        <path id="icon-close" class="hidden" stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>
        </div>

        <nav id="mobile-menu" aria-label="Mobile"
            class="border-(--gray-200) dark:border-(--gray-700) hidden border-t shadow-lg xl:hidden">
            <div
                class="text-(--gray-700) dark:text-(--gray-200) mx-auto flex max-w-7xl flex-col px-5 pb-4 text-sm font-medium sm:px-8">
                @foreach ($links as [$href, $text])
                    <a href="{{ $href }}"
                        class="border-(--gray-200) dark:border-(--gray-700) hover:text-(--blue-700) dark:hover:text-(--blue-300) focus-visible:bg-(--gray-100) dark:focus-visible:bg-(--gray-800) focus-visible:text-(--gray-900) dark:focus-visible:text-(--gray-50) border-b py-3 transition-colors">{{ $text }}</a>
                @endforeach

                <div class="mt-4 flex flex-col gap-3">
                    @auth
                        <a href="{{ $home }}" class="{{ $btn }}">Continue</a>
                    @else
                        <a href="{{ route('login') }}" class="{{ $btnOutline }}">Sign in</a>
                        <a href="{{ route('register') }}" class="{{ $btn }}">Create account</a>
                    @endauth
                </div>
            </div>
        </nav>
    </header>

    <main id="main">

        {{-- Hero --}}
        <section class="bg-(--gray-50) dark:bg-(--gray-900) relative isolate overflow-hidden">
            <x-partials.blueprint src="{{ asset('img/photos/ynares-hall-front.jpg') }}"
                class="absolute inset-0 -z-20" />
            <div
                class="bg-linear-to-r from-(--gray-50) dark:from-(--gray-900) absolute inset-0 -z-10 via-[color-mix(in_srgb,var(--gray-50)_90%,transparent)] to-[color-mix(in_srgb,var(--gray-50)_10%,transparent)] lg:via-[color-mix(in_srgb,var(--gray-50)_70%,transparent)] lg:to-transparent dark:via-[color-mix(in_srgb,var(--gray-900)_90%,transparent)] dark:to-[color-mix(in_srgb,var(--gray-900)_10%,transparent)] dark:lg:via-[color-mix(in_srgb,var(--gray-900)_70%,transparent)]">
            </div>
            <div
                class="bg-linear-to-t from-(--gray-50) dark:from-(--gray-900) absolute inset-x-0 bottom-0 -z-10 h-1/3 to-transparent">
            </div>

            <div class="mx-auto max-w-7xl px-5 pb-10 pt-14 sm:px-8 sm:pt-20 lg:pt-24">
                <div class="relative">
                    <p class="text-(--green-600) dark:text-(--green-300) text-sm font-semibold uppercase">Official
                        Online Portal of Barangay
                        San Luis</p>
                    <h1
                        class="text-(--gray-900) dark:text-(--gray-50) mt-3 max-w-2xl text-4xl font-bold leading-tight tracking-tight sm:text-5xl lg:text-6xl">
                        Barangay services, made <span class="text-(--green-600) dark:text-(--green-300)">easier</span>
                        and more
                        <span class="text-(--green-600) dark:text-(--green-300)">accessible.</span>
                    </h1>
                    <p class="text-(--gray-600) dark:text-(--gray-300) mt-6 max-w-md text-base leading-7">
                        Access online barangay services, submit certificate requests, track applications, and verify
                        issued documents through one secure portal.
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a href="{{ $apply }}"
                            class="{{ $btn }}">{{ $authed ? 'Go to your dashboard' : 'Apply online' }}</a>
                        <a href="#services" class="{{ $btnOutline }}">See available services</a>
                    </div>

                    {{-- Floating office card + contact button (desktop) --}}
                    <div class="absolute right-0 top-0 hidden w-52 flex-col items-center gap-6 lg:flex">
                        <div
                            class="w-full rounded-2xl bg-[color-mix(in_srgb,var(--white)_90%,transparent)] p-4 text-center shadow-[0_8px_28px_-8px_rgba(23,50,92,0.28)] backdrop-blur-md dark:bg-[color-mix(in_srgb,var(--gray-800)_90%,transparent)] dark:shadow-[0_8px_28px_-8px_rgba(59,130,246,0.22)]">
                            <svg class="text-(--blue-700) dark:text-(--blue-300) mx-auto h-8 w-8" fill="none"
                                stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                            </svg>
                            <p class="text-(--gray-900) dark:text-(--gray-50) mt-2 text-sm font-bold">Barangay San Luis
                            </p>
                            <p class="text-(--gray-600) dark:text-(--gray-300) text-xs">Antipolo City, Rizal 1870</p>
                            <p class="text-(--gray-900) dark:text-(--gray-50) mt-2 text-xs font-semibold">Mon to Fri,
                                8:00 AM to 5:00 PM
                            </p>
                        </div>
                        <a href="#contact"
                            class="text-(--gray-900) dark:text-(--gray-50) group flex flex-col items-center gap-2 text-sm font-semibold">
                            <span
                                class="bg-(--blue-600) text-(--white) group-hover:bg-(--blue-700) flex h-16 w-16 items-center justify-center rounded-full shadow-md transition duration-200 group-hover:-translate-y-0.5">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.5"
                                    viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                                </svg>
                            </span>
                            Contact us
                        </a>
                    </div>
                </div>

                {{-- Quick action cards --}}
                <div data-reveal class="mt-16 grid gap-3 lg:mt-40 lg:grid-cols-3">
                    @foreach ([
        // title, description, href, icon path, chips
        ['Apply for Services', 'Request barangay certificates and permits online, without the extra trip to the office.', $apply, 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z', ['Clearance', 'Residency', 'Indigency']],
        ['Track Application', 'Monitor your application status and updates from your account, anytime.', $home, 'M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0118 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3l1.5 1.5 3-3.75', ['Status updates', 'One dashboard']],
        ['Verify Document', 'Check the authenticity of documents issued by the barangay by scanning their QR code.', '#verify', 'M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5zM6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z', ['No account needed', 'Shows valid or not']],
    ] as [$title, $text, $href, $icon, $chips])
                        <a href="{{ $href }}"
                            class="group relative flex h-full min-h-48 flex-col overflow-hidden rounded-2xl bg-[color-mix(in_srgb,var(--white)_85%,transparent)] p-5 shadow-[0_8px_28px_-8px_rgba(23,50,92,0.28)] backdrop-blur-xl transition duration-300 hover:-translate-y-1 hover:shadow-[0_14px_36px_-8px_rgba(23,50,92,0.38)] dark:bg-[color-mix(in_srgb,var(--gray-800)_85%,transparent)] dark:shadow-[0_8px_28px_-8px_rgba(59,130,246,0.22)] dark:hover:shadow-[0_14px_36px_-8px_rgba(59,130,246,0.32)]">
                            <h2 class="text-(--gray-900) dark:text-(--gray-50) text-xl font-extrabold tracking-tight">
                                {{ $title }}</h2>
                            <p class="text-(--gray-600) dark:text-(--gray-300) mt-1.5 max-w-60 text-[13px] leading-5">
                                {{ $text }}</p>

                            <ul class="mt-3 flex flex-wrap gap-1.5 pr-14">
                                @foreach ($chips as $chip)
                                    <li
                                        class="text-(--blue-700) dark:text-(--blue-300) rounded-full bg-[color-mix(in_srgb,var(--blue-600)_10%,transparent)] px-2.5 py-0.5 text-[11px] font-semibold">
                                        {{ $chip }}</li>
                                @endforeach
                            </ul>

                            <span
                                class="mt-auto pt-4 text-2xl font-semibold text-[color-mix(in_srgb,var(--gray-500)_50%,transparent)] dark:text-[color-mix(in_srgb,var(--gray-400)_50%,transparent)]">{{ sprintf('%02d', $loop->iteration) }}</span>

                            {{-- Corner cut-out with icon --}}
                            <span aria-hidden="true"
                                class="bg-(--gray-50) dark:bg-(--gray-900) text-(--blue-700) dark:text-(--blue-300) group-hover:text-(--blue-800) dark:group-hover:text-(--blue-200) absolute bottom-0 right-0 flex h-20 w-20 items-end justify-end rounded-tl-full p-4 transition-colors duration-300">
                                <svg class="h-8 w-8 transition-transform duration-300 group-hover:scale-110"
                                    fill="none" stroke="currentColor" stroke-width="1.25" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                                </svg>
                            </span>
                        </a>
                    @endforeach
                </div>

                <p class="text-(--gray-500) dark:text-(--gray-400) mt-4 text-right text-xs">Ynares Hall, Barangay San
                    Luis</p>
            </div>
        </section>

        {{-- About --}}
        <section id="about" class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-24">

            {{-- Heading row --}}
            <div data-reveal class="grid gap-8 lg:grid-cols-2 lg:items-start lg:gap-16">
                <div>
                    <p class="{{ $eyebrow }} uppercase">About Us</p>
                    <h2 class="{{ $h2 }} max-w-md">An easier way to get barangay services.</h2>
                    <a href="{{ $apply }}" class="{{ $btn }} mt-8 rounded-full">
                        {{ $authed ? 'Go to dashboard' : 'Get started' }}
                    </a>
                </div>

                <p class="text-(--gray-600) dark:text-(--gray-300) max-w-xl text-sm leading-6 lg:pt-8">
                    BarangayChain PH is an online barangay service and clearance permit portal for the residents
                    of Barangay San Luis. It is built for easy access and to lessen the physical work for both
                    the residents and the barangay, so you can focus on what matters most.
                </p>
            </div>

            {{-- Cards --}}
            <div class="mt-12 grid gap-4 lg:grid-cols-[1.5fr_1fr]">

                {{-- Our Story (image card) --}}
                <article data-reveal
                    class="lg:min-h-112 relative isolate flex min-h-96 flex-col justify-end overflow-hidden rounded-3xl p-7 shadow-md">
                    <img src="{{ asset('img/photos/ynares-hall-side.jpg') }}" alt="Entrance of Ynares Hall"
                        width="548" height="428" loading="lazy"
                        class="absolute inset-0 -z-20 h-full w-full object-cover">
                    <div aria-hidden="true"
                        class="bg-linear-to-t from-(--gray-900) absolute inset-0 -z-10 via-[color-mix(in_srgb,var(--gray-900)_60%,transparent)] to-transparent">
                    </div>

                    <h3 class="text-xl font-semibold text-white">Our Story</h3>
                    <p class="text-(--gray-100) mt-3 max-w-xl text-sm leading-6">
                        BarangayChain PH brings the services of Barangay San Luis online, with fewer trips to
                        the office, requests that can be tracked, and documents that can be verified.
                    </p>
                </article>

                {{-- Mission + Vision --}}
                <div class="grid gap-4">
                    <article data-reveal style="--d: 120ms"
                        class="flex flex-col justify-center rounded-3xl bg-[color-mix(in_srgb,var(--blue-600)_15%,transparent)] p-7">
                        <h3 class="text-(--gray-900) dark:text-(--gray-50) text-xl font-semibold">Our Mission</h3>
                        <p class="text-(--gray-600) dark:text-(--gray-300) mt-3 text-sm leading-6">
                            To deliver simple, reliable online services that save residents time and reduce paperwork
                            for the barangay.
                        </p>
                    </article>

                    <article data-reveal style="--d: 240ms"
                        class="bg-(--blue-600) text-(--white) flex flex-col justify-center rounded-3xl p-7">
                        <h3 class="text-xl font-semibold">Our Vision</h3>
                        <p class="mt-3 text-sm leading-6 opacity-90">
                            A more connected Barangay San Luis, where every resident can get help without the long
                            queue.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        {{-- Services --}}
        <section id="services" class="{{ $band }}">
            <div class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-24">
                <div data-reveal class="max-w-xl">
                    <p class="{{ $eyebrow }} uppercase">Online services</p>
                    <h2 class="{{ $h2 }}">What can we help you with?</h2>
                </div>

                <div class="mt-10 grid gap-6 md:grid-cols-3">
                    @foreach ([['01', 'Barangay Clearance', 'For employment, applications, and other official transactions.'], ['02', 'Certificate of Residency', 'Proof of residence in Barangay San Luis.'], ['03', 'Certificate of Indigency', 'For residents applying for assistance.']] as [$number, $name, $text])
                        <div data-reveal style="--d: {{ $loop->index * 120 }}ms">
                            <a href="{{ $home }}"
                                class="{{ $glass }} border-t-(--blue-600) hover:border-t-(--blue-700) group flex h-full flex-col rounded-md border-t-4 p-7 transition duration-300 hover:-translate-y-1 hover:shadow-md">
                                <span
                                    class="text-(--green-600) dark:text-(--green-300) text-sm font-semibold">{{ $number }}</span>
                                <h3 class="text-(--gray-900) dark:text-(--gray-50) mt-4 text-xl font-bold">
                                    {{ $name }}</h3>
                                <p class="text-(--gray-600) dark:text-(--gray-300) mt-3 flex-1 text-sm leading-6">
                                    {{ $text }}</p>
                                <span
                                    class="border-(--blue-600) text-(--blue-700) dark:text-(--blue-300) group-hover:bg-(--blue-600) group-hover:text-(--white) mt-8 inline-flex w-fit items-center gap-2 rounded-md border px-4 py-2 text-sm font-semibold transition-colors duration-200">
                                    Request this service
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5"
                                        viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="{{ $chev }}" />
                                    </svg>
                                </span>
                            </a>
                        </div>
                    @endforeach
                </div>

                <p class="text-(--gray-600) dark:text-(--gray-300) mt-6 text-sm leading-6">Requirements, fees, and
                    processing times are subject
                    to confirmation by the barangay office.</p>
            </div>
        </section>

        {{-- Process --}}
        <section id="process">
            <div class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-24">
                <div data-reveal class="max-w-xl">
                    <p class="{{ $eyebrow }} uppercase">The process</p>
                    <h2 class="{{ $h2 }}">From registration to request tracking.</h2>
                </div>

                <ol data-reveal
                    class="{{ $glass }} divide-(--gray-200) dark:divide-(--gray-700) mt-12 grid divide-y overflow-hidden rounded-md md:grid-cols-4 md:divide-x md:divide-y-0">
                    @foreach ([['01', 'Create an account', 'Register and verify your email.'], ['02', 'Verify your identity', 'Submit your profile and valid ID.'], ['03', 'Request a service', 'Choose a certificate and submit.'], ['04', 'Track the status', 'Check updates from your account.']] as [$number, $name, $text])
                        <li
                            class="hover:bg-(--gray-100) dark:hover:bg-(--gray-800) group relative p-7 transition-colors duration-300">
                            <span aria-hidden="true"
                                class="{{ ['bg-(--blue-600)', 'bg-[color-mix(in_srgb,var(--blue-600)_75%,transparent)]', 'bg-[color-mix(in_srgb,var(--blue-600)_50%,transparent)]', 'bg-(--green-600)'][$loop->index] }} absolute inset-x-0 top-0 h-1"></span>
                            <p
                                class="text-(--gray-900) dark:text-(--gray-50) group-hover:text-(--blue-700) dark:group-hover:text-(--blue-300) text-4xl font-bold tracking-tight transition-colors duration-300">
                                {{ $number }}</p>
                            <h3 class="text-(--gray-900) dark:text-(--gray-50) mt-6 text-lg font-bold">
                                {{ $name }}</h3>
                            <p class="text-(--gray-600) dark:text-(--gray-300) mt-2 text-sm leading-6">
                                {{ $text }}</p>
                        </li>
                    @endforeach
                </ol>

                <div data-reveal class="mt-8">
                    <a href="{{ $apply }}" class="{{ $btn }}">
                        {{ $authed ? 'Go to your dashboard' : 'Get started' }}
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $chev }}" />
                        </svg>
                    </a>
                </div>
            </div>
        </section>

        {{-- Announcements: only when there is data --}}
        @if ($news->isNotEmpty())
            <section id="announcements" class="{{ $band }}">
                <div class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-24">
                    <div data-reveal class="flex flex-wrap items-end justify-between gap-4">
                        <div class="max-w-xl">
                            <p class="{{ $eyebrow }} uppercase">Announcements and updates</p>
                            <h2 class="{{ $h2 }}">Latest from the barangay.</h2>
                        </div>
                        <a href="https://www.facebook.com/barangay.sanluis.35" target="_blank"
                            rel="noopener noreferrer" class="{{ $textLink }}">
                            More on Facebook
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $chev }}" />
                            </svg>
                        </a>
                    </div>

                    <div class="mt-10 grid gap-6 md:grid-cols-3">
                        @foreach ($news as $item)
                            <article data-reveal style="--d: {{ $loop->index * 120 }}ms"
                                class="{{ $glass }} border-t-(--blue-600) flex flex-col rounded-md border-t-4 p-6">
                                @if (filled(data_get($item, 'date')))
                                    @php($date = \Illuminate\Support\Carbon::parse(data_get($item, 'date')))
                                    <time datetime="{{ $date->toDateString() }}"
                                        class="text-(--green-600) dark:text-(--green-300) text-xs font-semibold">{{ $date->format('F j, Y') }}</time>
                                @endif
                                <h3 class="text-(--gray-900) dark:text-(--gray-50) mt-2 text-lg font-bold">
                                    {{ data_get($item, 'title') }}</h3>
                                @if (data_get($item, 'summary'))
                                    <p class="text-(--gray-600) dark:text-(--gray-300) mt-3 flex-1 text-sm leading-6">
                                        {{ data_get($item, 'summary') }}</p>
                                @endif
                                @if (data_get($item, 'url'))
                                    <a href="{{ data_get($item, 'url') }}" class="{{ $textLink }} mt-5">Read
                                        more</a>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- Verification (always dark navy, in light and dark mode) --}}
        <section id="verify" class="always-dark bg-(--blue-900) dark:border-(--gray-700) text-white dark:border-y">
            <div class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-24">
                <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                    <div data-reveal>
                        <p class="text-(--green-300) text-sm font-semibold uppercase">Verification</p>
                        <h2 class="mt-2 text-3xl font-bold tracking-tight text-white sm:text-4xl">Scan the QR code to
                            check a document.</h2>
                        <p class="text-(--gray-200) mt-4 max-w-prose text-base leading-7">
                            Documents issued by Barangay San Luis carry a QR code. Scanning it opens this website and
                            shows whether the document is valid.
                        </p>

                        <h3 class="mt-8 text-lg font-bold text-white">How to verify a document</h3>
                        <ol class="mt-4 space-y-4">
                            @foreach (['Find the QR code on the document issued by the barangay.', 'Scan it with your phone camera or any QR scanner.', 'The result opens on this website and shows whether the document is valid.'] as $text)
                                <li class="text-(--gray-100) flex gap-4 text-sm leading-6">
                                    <span
                                        class="text-(--gray-900) flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-white text-sm font-bold">{{ $loop->iteration }}</span>
                                    <span class="pt-0.5">{{ $text }}</span>
                                </li>
                            @endforeach
                        </ol>

                        <p
                            class="border-(--green-400) text-(--gray-100) mt-8 max-w-md border-l-2 pl-4 text-sm leading-6">
                            No account or sign-in is needed to check a document. If the result shows the document is not
                            valid, contact the barangay office.
                        </p>
                    </div>

                    <figure data-reveal style="--d: 120ms">
                        <figcaption class="text-(--gray-100) mb-3 text-sm font-semibold">What you will see after
                            scanning</figcaption>
                        <img src="{{ asset('img/photos/verify-sample.png') }}"
                            alt="Sample verification result showing a valid Barangay Clearance with its permit number, reference number and applicant name"
                            width="992" height="704" loading="lazy" class="w-full rounded-xl shadow-2xl">
                    </figure>
                </div>

                <dl data-reveal class="border-(--gray-500) mt-14 grid gap-6 border-t pt-8 md:grid-cols-3 md:gap-8">
                    @foreach ([['QR code on the document', 'Scan it to open the verification result on this website.'], ['Checked against the record', 'The result comes from the approved permit record in the barangay system.'], ['Privacy protected', 'Only limited details are shown, and the applicant\'s name is shortened.']] as [$name, $text])
                        <div class="flex gap-3">
                            <svg class="text-(--green-300) mt-0.5 h-5 w-5 shrink-0" fill="none"
                                stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <div>
                                <dt class="text-sm font-bold text-white">{{ $name }}</dt>
                                <dd class="text-(--gray-200) mt-1 text-sm leading-6">{{ $text }}</dd>
                            </div>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>

        {{-- FAQ --}}
        <section id="faq"
            class="mx-auto grid max-w-7xl gap-10 px-5 py-16 sm:px-8 sm:py-24 lg:grid-cols-[0.8fr_1.2fr] lg:gap-20">
            <div data-reveal>
                <p class="{{ $eyebrow }} uppercase">Frequently asked questions</p>
                <h2 class="{{ $h2 }}">Quick answers.</h2>
                <p class="text-(--gray-600) dark:text-(--gray-300) mt-4 text-sm leading-6">
                    Need more help? Visit the barangay office or see the
                    <a href="#contact"
                        class="text-(--blue-700) dark:text-(--blue-300) font-semibold underline underline-offset-2">contact
                        details</a>.
                </p>
            </div>

            <div data-reveal style="--d: 120ms"
                class="divide-(--gray-200) dark:divide-(--gray-700) border-(--gray-200) dark:border-(--gray-700) divide-y border-y">
                @foreach ([['What do I need before I apply?', 'An email address you can open, your profile details, and a valid ID for identity verification.'], ['How do I verify a document?', 'Scan the QR code on the document with your phone. It opens this website and shows whether the document is valid. No account is needed.'], ['Are there fees or processing times?', 'Requirements, fees, and processing times are confirmed by the barangay office. Contact the office before applying.']] as [$question, $answer])
                    <details class="group" @if ($loop->first) open @endif>
                        <summary
                            class="text-(--gray-900) dark:text-(--gray-50) hover:text-(--blue-700) dark:hover:text-(--blue-300) flex cursor-pointer list-none items-center justify-between gap-4 py-5 text-base font-semibold transition-colors [&::-webkit-details-marker]:hidden">
                            {{ $question }}
                            <svg class="h-5 w-5 shrink-0 transition-transform duration-200 group-open:rotate-90"
                                fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $chev }}" />
                            </svg>
                        </summary>
                        <p class="text-(--gray-600) dark:text-(--gray-300) pb-5 pr-9 text-sm leading-6">
                            {{ $answer }}</p>
                    </details>
                @endforeach
            </div>
        </section>

        {{-- Contact --}}
        <section id="contact"
            class="relative z-10 grid bg-[color-mix(in_srgb,var(--blue-900)_8%,transparent)] shadow-[0_0_40px_-10px_rgba(23,50,92,0.25)] lg:grid-cols-2 dark:bg-[color-mix(in_srgb,var(--blue-900)_40%,transparent)] dark:shadow-[0_0_40px_-10px_rgba(59,130,246,0.18)]">
            <div class="min-w-0 px-5 py-14 sm:px-8 sm:py-20 lg:pl-[max(2rem,calc(50vw-38rem))] lg:pr-12">
                <p class="{{ $eyebrow }} uppercase">Get in touch</p>
                <h2 class="{{ $h2 }}">Contact Us</h2>
                <p class="text-(--gray-600) dark:text-(--gray-300) mt-4 max-w-md text-sm leading-6">Visit the barangay
                    office or message us on
                    Facebook for announcements and inquiries.</p>

                <dl class="mt-8 space-y-5">
                    {{-- icon path, label, value, link (null = no link) --}}
                    @foreach ([
        ['M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z', 'Location', 'Barangay San Luis, Antipolo City, Rizal 1870', null],
        ['M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z', 'Office hours', 'Monday to Friday, 8:00 AM to 5:00 PM', null],
        ['M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z', 'Phone', '8-8555-245', 'tel:88555245'],
        ['M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75', 'Email', 'barangaychain@gmail.com', 'mailto:barangaychain@gmail.com'],
        ['M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25', 'Facebook', 'Visit our Facebook page', 'https://www.facebook.com/barangay.sanluis.35'],
    ] as [$icon, $name, $value, $link])
                        <div class="flex items-center gap-4">
                            <svg class="text-(--green-600) dark:text-(--green-300) h-6 w-6 shrink-0" fill="none"
                                stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                            </svg>
                            <div class="min-w-0">
                                <dt class="text-(--gray-600) dark:text-(--gray-300) text-sm font-semibold">
                                    {{ $name }}</dt>
                                <dd
                                    class="wrap-break-word text-(--gray-700) dark:text-(--gray-200) mt-0.5 text-sm font-semibold">
                                    @if ($link)
                                        <a href="{{ $link }}"
                                            @if ($name === 'Facebook') target="_blank" rel="noopener noreferrer" @endif
                                            class="hover:text-(--blue-700) dark:hover:text-(--blue-300) break-all underline-offset-4 transition-colors hover:underline">{{ $value }}</a>
                                    @else
                                        {{ $value }}
                                    @endif
                                </dd>
                            </div>
                        </div>
                    @endforeach
                </dl>

                <p class="text-(--gray-600) dark:text-(--gray-300) mt-8 max-w-md text-sm leading-6">Confirm the contact
                    number and office hours
                    with the barangay before visiting.</p>
            </div>

            <div
                class="flex min-w-0 flex-col px-5 pb-14 sm:px-8 sm:pb-20 lg:py-20 lg:pl-0 lg:pr-[max(2rem,calc(50vw-38rem))]">
                <iframe title="Map of Barangay San Luis, Antipolo City" loading="lazy"
                    class="lg:min-h-100 border-t-(--blue-600) min-h-80 w-full flex-1 rounded-md border-t-4 bg-[color-mix(in_srgb,var(--white)_80%,transparent)] shadow-[0_6px_24px_-8px_rgba(23,50,92,0.22)] sm:min-h-96 dark:bg-[color-mix(in_srgb,var(--gray-800)_80%,transparent)] dark:shadow-[0_6px_28px_-8px_rgba(59,130,246,0.18)]"
                    src="https://www.google.com/maps?q=Barangay+San+Luis+Antipolo+City+Rizal&output=embed"></iframe>
                <a href="https://www.google.com/maps/search/?api=1&query=Barangay+San+Luis+Antipolo+City+Rizal"
                    target="_blank" rel="noopener noreferrer" class="{{ $textLink }} mt-3">
                    Open in Google Maps
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5"
                        viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $chev }}" />
                    </svg>
                </a>
            </div>
        </section>

    </main>

    <script>
        // Mobile menu
        const menu = document.getElementById('mobile-menu');
        const menuButton = document.getElementById('menu-button');
        const menuParts = [menu, document.getElementById('icon-open'), document.getElementById('icon-close')];

        function toggleMenu() {
            menuParts.forEach((el) => el.classList.toggle('hidden'));
            menuButton.setAttribute('aria-expanded', !menu.classList.contains('hidden'));
        }

        function closeMenu() {
            if (!menu.classList.contains('hidden')) toggleMenu();
        }

        menuButton.addEventListener('click', toggleMenu);
        menu.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMenu));

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !menu.classList.contains('hidden')) {
                closeMenu();
                menuButton.focus();
            }
        });

        // Header shadow after scrolling
        const header = document.querySelector('header');
        const onScroll = () => header.classList.toggle('shadow-md', scrollY > 8);
        addEventListener('scroll', onScroll, {
            passive: true
        });
        onScroll();

        // Fade in on scroll and highlight the current section in the nav
        const reveals = document.querySelectorAll('[data-reveal]');

        if (!('IntersectionObserver' in window)) {
            reveals.forEach((el) => el.classList.add('is-visible'));
        } else {
            const navLinks = document.querySelectorAll('nav[aria-label="Main"] a');

            const spy = new IntersectionObserver((items) => items.forEach((item) => {
                if (item.isIntersecting) {
                    navLinks.forEach((a) => a.setAttribute('aria-current', a.hash === '#' + item.target.id));
                }
            }), {
                rootMargin: '-40% 0px -55% 0px'
            });

            navLinks.forEach((a) => {
                const target = document.querySelector(a.hash);
                if (target) spy.observe(target);
            });

            const reveal = new IntersectionObserver((items) => items.forEach((item) => {
                if (!item.isIntersecting) return;
                item.target.classList.add('is-visible');
                reveal.unobserve(item.target);
            }), {
                threshold: 0.12
            });

            reveals.forEach((el) => reveal.observe(el));
        }
    </script>


    <script src="https://www.tuqlas.com/chatbot.js" data-key="tq_live_b008f3e486ad59592451ce7d324b4c54c94ccd3a"
        data-api="https://www.tuqlas.com" defer></script>


    <x-govern-footer />
</body>

</html>
