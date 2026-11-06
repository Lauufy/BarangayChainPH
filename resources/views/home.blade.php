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
    <x-partials.theme-init />
    <style>
        body {
            overflow-x: clip;
        }

        :focus-visible {
            outline: 2px solid #1e3a8a;
            outline-offset: 3px;
        }

        html.dark :focus-visible {
            outline-color: #6ee7b7;
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

    <link rel="icon" type="image/png" href="{{ asset('img/branding/barangayChainPH-logo.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

{{--
    Notes
    - Colors: light mode = white + blue-900 only. Dark mode = black #0f1110, deep greens #152a1c / #294833 / #596e56, white, and light emerald (emerald-300) accents.
    - Dashboard link (guests fall through to login):
      route(match (auth()->user()?->role) { 'admin', 'staff' => 'admin.dashboard', 'citizen' => 'citizen.dashboard', default => 'login' })
    - $announcements can still be passed from the controller (title, date, summary, url). Max 3 shown.
    - To point Apply / Track / service cards to real pages later, change the hrefs marked APPLY and TRACK.
--}}

<body class="bg-white text-blue-900 antialiased transition-colors duration-300 dark:bg-[#0f1110] dark:text-white">

    <x-feedbacks.toast />

    {{-- Ambient glow behind the glass panels --}}
    <div aria-hidden="true" class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -left-32 top-24 h-96 w-96 rounded-full bg-blue-300/40 blur-3xl dark:bg-emerald-500/20"></div>
        <div class="absolute -right-32 top-[45%] h-112 w-md rounded-full bg-blue-200/60 blur-3xl dark:bg-[#596e56]/30"></div>
        <div class="absolute -bottom-24 left-1/3 h-96 w-96 rounded-full bg-blue-400/20 blur-3xl dark:bg-emerald-300/10"></div>
    </div>

    <a href="#main"
        class="z-60 sr-only rounded-md bg-white px-4 py-2 text-sm font-semibold text-blue-900 focus:not-sr-only focus:fixed focus:left-4 focus:top-4 dark:bg-[#0f1110] dark:text-emerald-300">Skip
        to main content</a>


    {{-- Header (mobile menu below 1280px) --}}
    <header
        class="sticky top-0 z-50 border-b border-blue-100 bg-white/70 backdrop-blur-xl transition-shadow duration-300 dark:border-white/10 dark:bg-[#0f1110]/60">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-5 py-4 sm:px-8">
            <x-partials.logo />

            <nav aria-label="Main"
                class="hidden items-center gap-5 text-sm text-blue-900/70 xl:flex dark:text-white/70">
                @foreach ([['#about', 'About'], ['#services', 'Services'], ['#process', 'How it works'], ...collect($announcements ?? [])->isNotEmpty() ? [['#announcements', 'Updates']] : [], ['#verify', 'Verify'], ['#faq', 'FAQ'], ['#contact', 'Contact']] as [$link, $text])
                    <a href="{{ $link }}"
                        class="aria-current:font-semibold aria-current:text-blue-900 dark:aria-current:text-emerald-300 aria-current:after:scale-x-100 relative py-1 transition-colors after:absolute after:inset-x-0 after:-bottom-0.5 after:h-0.5 after:origin-left after:scale-x-0 after:bg-blue-900 after:transition-transform after:duration-200 hover:text-blue-900 hover:after:scale-x-100 focus-visible:text-blue-900 focus-visible:after:scale-x-100 dark:after:bg-emerald-300 dark:hover:text-white dark:focus-visible:text-white">{{ $text }}</a>
                @endforeach
            </nav>

            <div class="hidden items-center gap-4 xl:flex">
                <x-partials.theme-toggle />
                @auth
                    <a href="{{ route(match (auth()->user()?->role) {'admin', 'staff' => 'admin.dashboard','citizen' => 'citizen.dashboard',default => 'login'}) }}"
                        class="inline-flex items-center justify-center gap-2 rounded-md bg-blue-900/90 px-5 py-3 ring-1 ring-white/20 backdrop-blur-md dark:ring-1 text-sm font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:bg-blue-800 hover:shadow-md dark:bg-emerald-400/20 dark:text-white dark:ring-emerald-300/40 dark:hover:bg-emerald-400/30">Open
                        dashboard</a>
                @else
                    <a href="{{ route('login') }}"
                        class="text-sm font-semibold text-blue-900 transition-colors hover:text-blue-700 dark:text-emerald-300 dark:hover:text-white">Sign
                        in</a>
                    <a href="{{ route('register') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-md bg-blue-900/90 px-5 py-3 ring-1 ring-white/20 backdrop-blur-md dark:ring-1 text-sm font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:bg-blue-800 hover:shadow-md dark:bg-emerald-400/20 dark:text-white dark:ring-emerald-300/40 dark:hover:bg-emerald-400/30">Create
                        account</a>
                @endauth
            </div>

            <div class="flex items-center gap-1 xl:hidden">
                <x-partials.theme-toggle />
                <button type="button" id="menu-button" aria-label="Toggle menu" aria-controls="mobile-menu"
                    aria-expanded="false"
                    class="rounded-md p-2 text-blue-900 transition-colors hover:bg-blue-50 dark:text-emerald-300 dark:hover:bg-[#294833]/40">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path id="icon-open" stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" />
                        <path id="icon-close" class="hidden" stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>
        </div>

        <nav id="mobile-menu" aria-label="Mobile"
            class="hidden border-t border-blue-100 bg-white/80 backdrop-blur-xl xl:hidden dark:border-white/10 dark:bg-[#0f1110]/80">
            <div
                class="mx-auto flex max-w-7xl flex-col px-5 pb-4 text-sm font-medium text-blue-900 sm:px-8 dark:text-white">
                @foreach ([['#about', 'About'], ['#services', 'Services'], ['#process', 'How it works'], ...collect($announcements ?? [])->isNotEmpty() ? [['#announcements', 'Updates']] : [], ['#verify', 'Verify'], ['#faq', 'FAQ'], ['#contact', 'Contact']] as [$link, $text])
                    <a href="{{ $link }}"
                        class="border-b border-blue-100 py-3 transition-colors hover:text-blue-700 focus-visible:bg-blue-50 focus-visible:text-blue-900 dark:border-white/10 dark:hover:text-white dark:focus-visible:bg-[#294833]/40">{{ $text }}</a>
                @endforeach

                <div class="mt-4 flex flex-col gap-3">
                    @auth
                        <a href="{{ route(match (auth()->user()?->role) {'admin', 'staff' => 'admin.dashboard','citizen' => 'citizen.dashboard',default => 'login'}) }}"
                            class="inline-flex items-center justify-center gap-2 rounded-md bg-blue-900/90 px-5 py-3 ring-1 ring-white/20 backdrop-blur-md dark:ring-1 text-sm font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:bg-blue-800 hover:shadow-md dark:bg-emerald-400/20 dark:text-white dark:ring-emerald-300/40 dark:hover:bg-emerald-400/30">Open
                            dashboard</a>
                    @else
                        <a href="{{ route('login') }}"
                            class="rounded-md border border-blue-900 px-5 py-3 text-center font-semibold text-blue-900 dark:border-white/20 dark:bg-white/5 dark:text-white dark:backdrop-blur-md">Sign
                            in</a>
                        <a href="{{ route('register') }}"
                            class="inline-flex items-center justify-center gap-2 rounded-md bg-blue-900/90 px-5 py-3 ring-1 ring-white/20 backdrop-blur-md dark:ring-1 text-sm font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:bg-blue-800 hover:shadow-md dark:bg-emerald-400/20 dark:text-white dark:ring-emerald-300/40 dark:hover:bg-emerald-400/30">Create
                            account</a>
                    @endauth
                </div>
            </div>
        </nav>
    </header>

    <main id="main">

        {{-- Hero --}}
        <section class="relative isolate overflow-hidden bg-white dark:bg-[#0f1110]">
            <x-partials.blueprint src="{{ asset('img/photos/ynares-hall-front.jpg') }}"
                class="absolute inset-0 -z-20" />
            <div
                class="bg-linear-to-r absolute inset-0 -z-10 from-white via-white/90 to-white/10 lg:via-white/70 lg:to-transparent dark:from-[#0f1110] dark:via-[#0f1110]/90 dark:to-[#0f1110]/20 lg:dark:via-[#0f1110]/70 lg:dark:to-transparent">
            </div>
            <div
                class="bg-linear-to-t absolute inset-x-0 bottom-0 -z-10 h-1/3 from-white to-transparent dark:from-[#0f1110]">
            </div>

            <div class="mx-auto max-w-7xl px-5 pb-10 pt-14 sm:px-8 sm:pt-20 lg:pt-24">
                <div class="relative">
                    <p class="text-sm font-semibold uppercase text-blue-900 dark:text-emerald-300">Official Online
                        Portal of
                        Barangay San Luis</p>
                    <h1
                        class="mt-3 max-w-2xl text-4xl font-bold leading-tight tracking-tight text-blue-900 sm:text-5xl lg:text-6xl dark:text-white">
                        Barangay services, made <span class="text-blue-700 dark:text-emerald-300">easier</span> and
                        more
                        <span class="text-blue-700 dark:text-emerald-300">accessible.</span>
                    </h1>
                    <p class="mt-6 max-w-md text-base leading-7 text-blue-900/80 dark:text-white/80">
                        Access online barangay services, submit certificate requests, track applications, and verify
                        issued documents through one secure portal.
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        @auth
                            <a href="{{ route(match (auth()->user()?->role) {'admin', 'staff' => 'admin.dashboard','citizen' => 'citizen.dashboard',default => 'login'}) }}"
                                class="inline-flex items-center justify-center gap-2 rounded-md bg-blue-900/90 px-5 py-3 ring-1 ring-white/20 backdrop-blur-md dark:ring-1 text-sm font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:bg-blue-800 hover:shadow-md dark:bg-emerald-400/20 dark:text-white dark:ring-emerald-300/40 dark:hover:bg-emerald-400/30">Go
                                to your dashboard</a>
                        @else
                            <a href="{{ route('register') }}"
                                class="inline-flex items-center justify-center gap-2 rounded-md bg-blue-900/90 px-5 py-3 ring-1 ring-white/20 backdrop-blur-md dark:ring-1 text-sm font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:bg-blue-800 hover:shadow-md dark:bg-emerald-400/20 dark:text-white dark:ring-emerald-300/40 dark:hover:bg-emerald-400/30">Apply
                                online</a>
                        @endauth
                        <a href="#services"
                            class="inline-flex items-center justify-center rounded-md border border-blue-900 px-5 py-3 text-sm font-semibold text-blue-900 transition-colors duration-200 hover:bg-blue-900 hover:text-white dark:border-white/20 dark:bg-white/5 dark:text-white dark:backdrop-blur-md dark:hover:bg-white/15 dark:hover:text-white">See
                            available services</a>
                    </div>

                    {{-- Floating office card + contact button (desktop) --}}
                    <div class="absolute right-0 top-0 hidden w-52 flex-col items-center gap-6 lg:flex">
                        <div
                            class="w-full rounded-2xl bg-white/80 p-4 text-center shadow-lg backdrop-blur-md dark:bg-white/85 dark:shadow-none">
                            <svg class="mx-auto h-8 w-8 text-blue-900 dark:text-[#0f1110]" fill="none"
                                stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                            </svg>
                            <p class="mt-2 text-sm font-bold text-blue-900 dark:text-[#0f1110]">Barangay San Luis
                            </p>
                            <p class="text-xs text-blue-900/70 dark:text-[#0f1110]/70">Antipolo City, Rizal 1870</p>
                            <p class="mt-2 text-xs font-semibold text-blue-900 dark:text-[#0f1110]">Mon to Fri, 8:00
                                AM
                                to 5:00 PM</p>
                        </div>
                        <a href="#contact"
                            class="group flex flex-col items-center gap-2 text-sm font-semibold text-white">
                            <span
                                class="flex h-16 w-16 items-center justify-center rounded-full bg-blue-900 shadow-lg transition duration-200 group-hover:-translate-y-0.5 group-hover:bg-blue-800 dark:bg-emerald-400/20 dark:text-white dark:ring-1 dark:ring-emerald-300/40 dark:backdrop-blur-md dark:shadow-none dark:group-hover:bg-emerald-400/30">
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
        // title, description, href, icon path, info chips, call to action
        // APPLY
        [
            'Apply for Services',
            'Request barangay certificates and permits online, without the extra trip to the office.',
            auth()->check()
                ? route(
                    match (auth()->user()?->role) {
                        'admin', 'staff' => 'admin.dashboard',
                        'citizen' => 'citizen.dashboard',
                        default => 'login',
                    },
                )
                : route('register'),
            'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
            ['Clearance', 'Residency', 'Indigency'],
            auth()->check() ? 'Go to dashboard' : 'Get started',
        ],
        // TRACK
        [
            'Track Application',
            'Monitor your application status and updates from your account, anytime.',
            route(
                match (auth()->user()?->role) {
                    'admin', 'staff' => 'admin.dashboard',
                    'citizen' => 'citizen.dashboard',
                    default => 'login',
                },
            ),
            'M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0118 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3l1.5 1.5 3-3.75',
            ['Status updates', 'One dashboard'],
            'Open my requests',
        ],
        // VERIFY
        ['Verify Document', 'Check the authenticity of documents issued by the barangay by scanning their QR code.', '#verify', 'M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5zM6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z', ['No account needed', 'Shows valid or not'], 'How to verify'],
    ] as [$title, $text, $href, $icon, $chips, $cta])
                        <a href="{{ $href }}" aria-label="{{ $cta }}"
                            class="group relative flex h-full min-h-48 flex-col overflow-hidden rounded-2xl border border-white/60 bg-blue-50/70 p-5 backdrop-blur-xl shadow-lg shadow-blue-900/10 transition duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-white/15 dark:bg-white/10 dark:shadow-none">
                            <h3 class="text-xl font-extrabold tracking-tight text-blue-900 dark:text-white">
                                {{ $title }}</h3>
                            <p class="mt-1.5 max-w-60 text-[13px] leading-5 text-blue-900/70 dark:text-white/70">
                                {{ $text }}</p>

                            <ul class="mt-3 flex flex-wrap gap-1.5 pr-14">
                                @foreach ($chips as $chip)
                                    <li
                                        class="rounded-full bg-blue-100 px-2.5 py-0.5 text-[11px] font-semibold text-blue-900 dark:bg-white/10 dark:text-white">
                                        {{ $chip }}</li>
                                @endforeach
                            </ul>

                            <span
                                class="mt-auto pt-4 text-2xl font-semibold text-blue-900/25 dark:text-white/25">{{ sprintf('%02d', $loop->iteration) }}</span>

                            {{-- White quarter-circle corner with icon --}}
                            <span aria-hidden="true"
                                class="absolute bottom-0 right-0 flex h-20 w-20 items-end justify-end rounded-tl-full bg-white p-4 text-blue-900 transition-colors duration-300 group-hover:text-blue-700 dark:bg-[#0f1110] dark:text-emerald-300 dark:group-hover:text-white">
                                <svg class="h-8 w-8 transition-transform duration-300 group-hover:scale-110"
                                    fill="none" stroke="currentColor" stroke-width="1.25" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                                </svg>
                            </span>
                        </a>
                    @endforeach
                </div>

                <p class="mt-4 text-right text-xs text-blue-900/60 dark:text-white/50">Ynares Hall, Barangay San
                    Luis</p>
            </div>
        </section>

        {{-- About --}}
        <section id="about"
            class="mx-auto grid max-w-7xl items-center gap-12 px-5 py-16 sm:px-8 sm:py-24 lg:grid-cols-2 lg:gap-20">
            <div data-reveal>
                <p class="text-sm font-semibold text-blue-900 dark:text-emerald-300">About the website</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-blue-900 sm:text-4xl dark:text-white">An
                    easier
                    way to get
                    barangay services.</h2>
                <p class="mt-5 max-w-prose leading-7 text-blue-900/70 dark:text-white/60">
                    BarangayChain PH is an online barangay service and clearance permit portal for the residents
                    of Barangay San Luis. It is built for easy access and to lessen the physical work for both
                    the residents and the barangay.
                </p>

                <div class="mt-8 grid gap-6 sm:grid-cols-2">
                    <div class="border-l-2 border-blue-900 pl-4 dark:border-[#596e56]">
                        <p class="text-sm font-bold text-blue-900 dark:text-white">For residents</p>
                        <p class="mt-2 text-sm leading-6 text-blue-900/70 dark:text-white/60">Apply for
                            clearances and
                            permits from home
                            and follow your request online, with fewer trips to the barangay office.</p>
                    </div>
                    <div class="border-l-2 border-blue-900 pl-4 dark:border-[#596e56]">
                        <p class="text-sm font-bold text-blue-900 dark:text-white">For the barangay</p>
                        <p class="mt-2 text-sm leading-6 text-blue-900/70 dark:text-white/60">Requests come into
                            one
                            system, which reduces
                            paperwork and manual work for barangay staff.</p>
                    </div>
                </div>
            </div>

            <div data-reveal style="--d: 120ms" class="relative mx-auto w-full max-w-md pb-3 pr-3 lg:mr-0">
                <span aria-hidden="true"
                    class="absolute bottom-0 right-0 h-[calc(100%-0.75rem)] w-[calc(100%-0.75rem)] rounded-md border-2 border-blue-900 dark:border-[#596e56]"></span>
                <img src="{{ asset('img/photos/ynares-hall-side.jpg') }}" alt="Entrance of Ynares Hall"
                    width="548" height="428" loading="lazy"
                    class="aspect-4/3 relative w-full rounded-md object-cover shadow-md dark:shadow-none">
            </div>
        </section>

        {{-- Services --}}
        <section id="services"
            class="border-y border-blue-100 bg-blue-50/60 backdrop-blur-2xl dark:border-white/10 dark:bg-[#152a1c]/40">
            <div class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-24">
                <div data-reveal class="max-w-xl">
                    <p class="text-sm font-semibold text-blue-900 dark:text-emerald-300">Online services</p>
                    <h2 class="mt-2 text-3xl font-bold tracking-tight text-blue-900 sm:text-4xl dark:text-white">
                        What
                        can we help you
                        with?</h2>
                </div>

                <div class="mt-10 grid gap-6 md:grid-cols-3">
                    @foreach ([['01', 'Barangay Clearance', 'For employment, applications, and other official transactions.'], ['02', 'Certificate of Residency', 'Proof of residence in Barangay San Luis.'], ['03', 'Certificate of Indigency', 'For residents applying for assistance.']] as [$number, $name, $text])
                        <div data-reveal style="--d: {{ $loop->index * 120 }}ms">
                            {{-- APPLY --}}
                            <a href="{{ route(match (auth()->user()?->role) {'admin', 'staff' => 'admin.dashboard','citizen' => 'citizen.dashboard',default => 'login'}) }}"
                                class="group flex h-full flex-col rounded-md border border-t-4 border-blue-100 border-t-blue-900 bg-white/70 p-7 backdrop-blur-xl transition duration-300 hover:-translate-y-1 hover:border-t-blue-700 hover:shadow-lg dark:border-white/10 dark:border-t-emerald-300/60 dark:bg-white/5 dark:hover:border-t-emerald-300 dark:hover:bg-white/10 dark:hover:shadow-none">
                                <span
                                    class="text-sm font-semibold text-blue-900/60 dark:text-emerald-300">{{ $number }}</span>
                                <h3 class="mt-4 text-xl font-bold text-blue-900 dark:text-white">
                                    {{ $name }}
                                </h3>
                                <p class="mt-3 flex-1 text-sm leading-6 text-blue-900/70 dark:text-white/60">
                                    {{ $text }}</p>
                                <span
                                    class="mt-8 inline-flex w-fit items-center gap-2 rounded-md border border-blue-900 px-4 py-2 text-sm font-semibold text-blue-900 transition-colors duration-200 group-hover:bg-blue-900 group-hover:text-white dark:border-white/20 dark:bg-white/5 dark:text-white dark:backdrop-blur-md dark:group-hover:bg-emerald-400/25 dark:group-hover:text-white">
                                    Request this service
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5"
                                        viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                    </svg>
                                </span>
                            </a>
                        </div>
                    @endforeach
                </div>

                <p class="mt-6 text-sm leading-6 text-blue-900/70 dark:text-white/60">
                    Requirements, fees, and processing times are subject to confirmation by the barangay office.
                </p>
            </div>
        </section>

        {{-- Process --}}
        <section id="process">
            <div class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-24">
                <div data-reveal class="max-w-xl">
                    <p class="text-sm font-semibold text-blue-900 dark:text-emerald-300">The process</p>
                    <h2 class="mt-2 text-3xl font-bold tracking-tight text-blue-900 sm:text-4xl dark:text-white">
                        From
                        registration to
                        request tracking.</h2>
                </div>

                <ol data-reveal
                    class="mt-12 grid divide-y divide-blue-100 overflow-hidden rounded-md border border-blue-100 bg-white/70 backdrop-blur-xl md:grid-cols-4 md:divide-x md:divide-y-0 dark:divide-white/10 dark:border-white/10 dark:bg-white/5">
                    @foreach ([['01', 'Create an account', 'Register and verify your email.'], ['02', 'Verify your identity', 'Submit your profile and valid ID.'], ['03', 'Request a service', 'Choose a certificate and submit.'], ['04', 'Track the status', 'Check updates from your account.']] as [$number, $name, $text])
                        <li
                            class="group relative p-7 transition-colors duration-300 hover:bg-blue-50 dark:hover:bg-[#294833]/40">
                            <span aria-hidden="true"
                                class="{{ ['bg-blue-900 dark:bg-emerald-300', 'bg-blue-900/80 dark:bg-[#596e56]', 'bg-blue-900/60 dark:bg-[#294833]', 'bg-blue-900/40 dark:bg-[#294833]/60'][$loop->index] }} absolute inset-x-0 top-0 h-1"></span>
                            <p
                                class="text-4xl font-bold tracking-tight text-blue-900 transition-colors duration-300 group-hover:text-blue-700 dark:text-emerald-300 dark:group-hover:text-white">
                                {{ $number }}</p>
                            <h3 class="mt-6 text-lg font-bold text-blue-900 dark:text-white">{{ $name }}
                            </h3>
                            <p class="mt-2 text-sm leading-6 text-blue-900/70 dark:text-white/60">
                                {{ $text }}
                            </p>
                        </li>
                    @endforeach
                </ol>

                <div data-reveal class="mt-8">
                    {{-- APPLY --}}
                    <a href="{{ auth()->check()? route(match (auth()->user()?->role) {'admin', 'staff' => 'admin.dashboard','citizen' => 'citizen.dashboard',default => 'login'}): route('register') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-md bg-blue-900/90 px-5 py-3 ring-1 ring-white/20 backdrop-blur-md dark:ring-1 text-sm font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:bg-blue-800 hover:shadow-md dark:bg-emerald-400/20 dark:text-white dark:ring-emerald-300/40 dark:hover:bg-emerald-400/30">
                        @auth Go to your dashboard
                        @else
                        Get started @endauth
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5"
                            viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </div>
            </div>
        </section>

        {{-- Announcements: only when there is data --}}
        @if (collect($announcements ?? [])->isNotEmpty())
            <section id="announcements"
                class="border-y border-blue-100 bg-blue-50/60 backdrop-blur-2xl dark:border-white/10 dark:bg-[#152a1c]/40">
                <div class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-24">
                    <div data-reveal class="flex flex-wrap items-end justify-between gap-4">
                        <div class="max-w-xl">
                            <p class="text-sm font-semibold text-blue-900 dark:text-emerald-300">Announcements and
                                updates
                            </p>
                            <h2
                                class="mt-2 text-3xl font-bold tracking-tight text-blue-900 sm:text-4xl dark:text-white">
                                Latest from
                                the barangay.</h2>
                        </div>
                        <a href="https://www.facebook.com/barangay.sanluis.35" target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 text-sm font-semibold text-blue-900 underline decoration-blue-900 decoration-2 underline-offset-4 transition-colors hover:text-blue-700 dark:text-emerald-300 dark:decoration-[#596e56] dark:hover:text-white">
                            More on Facebook
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </a>
                    </div>

                    <div class="mt-10 grid gap-6 md:grid-cols-3">
                        @foreach (collect($announcements)->take(3) as $item)
                            <article data-reveal style="--d: {{ $loop->index * 120 }}ms"
                                class="flex flex-col rounded-md border border-t-4 border-blue-100 border-t-blue-900 bg-white/70 p-6 backdrop-blur-xl dark:border-white/10 dark:border-t-emerald-300/60 dark:bg-white/5">
                                @if (filled(data_get($item, 'date')))
                                    <time
                                        datetime="{{ \Illuminate\Support\Carbon::parse(data_get($item, 'date'))->toDateString() }}"
                                        class="text-xs font-semibold text-blue-900/60 dark:text-emerald-300">{{ \Illuminate\Support\Carbon::parse(data_get($item, 'date'))->format('F j, Y') }}</time>
                                @endif
                                <h3 class="mt-2 text-lg font-bold text-blue-900 dark:text-white">
                                    {{ data_get($item, 'title') }}</h3>
                                @if (data_get($item, 'summary'))
                                    <p class="mt-3 flex-1 text-sm leading-6 text-blue-900/70 dark:text-white/60">
                                        {{ data_get($item, 'summary') }}</p>
                                @endif
                                @if (data_get($item, 'url'))
                                    <a href="{{ data_get($item, 'url') }}"
                                        class="mt-5 inline-flex w-fit items-center gap-2 text-sm font-semibold text-blue-900 underline decoration-blue-900 decoration-2 underline-offset-4 transition-colors hover:text-blue-700 dark:text-emerald-300 dark:decoration-[#596e56] dark:hover:text-white">Read
                                        more</a>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- Verification --}}
        <section id="verify"
            class="bg-blue-900/90 text-white backdrop-blur-2xl dark:border-y dark:border-white/10 dark:bg-[#294833]/50 dark:text-white dark:backdrop-blur-2xl">
            <div class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-24">
                <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                    <div data-reveal>
                        <p class="text-sm font-semibold text-blue-200 dark:text-emerald-300">Verification</p>
                        <h2 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl dark:text-white">
                            Scan the QR code to check a document.
                        </h2>
                        <p class="mt-4 max-w-prose text-base leading-7 text-blue-100/80 dark:text-white/70">
                            Documents issued by Barangay San Luis carry a QR code. Scanning it opens this website and
                            shows whether the document is valid.
                        </p>

                        <h3 class="mt-8 text-lg font-bold dark:text-white">How to verify a document</h3>
                        <ol class="mt-4 space-y-4">
                            @foreach (['Find the QR code on the document issued by the barangay.', 'Scan it with your phone camera or any QR scanner.', 'The result opens on this website and shows whether the document is valid.'] as $text)
                                <li class="flex gap-4 text-sm leading-6 text-blue-100 dark:text-white/80">
                                    <span
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-white text-sm font-bold text-blue-900 dark:bg-white dark:text-[#0f1110]">{{ $loop->iteration }}</span>
                                    <span class="pt-0.5">{{ $text }}</span>
                                </li>
                            @endforeach
                        </ol>

                        <p
                            class="mt-8 max-w-md border-l-2 border-white/60 pl-4 text-sm leading-6 text-blue-100 dark:border-[#596e56] dark:text-white/80">
                            No account or sign-in is needed to check a document. If the result shows the
                            document is not valid, contact the barangay office.
                        </p>
                    </div>

                    <figure data-reveal style="--d: 120ms">
                        <figcaption class="mb-3 text-sm font-semibold text-blue-100 dark:text-white">What you
                            will see after scanning
                        </figcaption>
                        <img src="{{ asset('img/photos/verify-sample.png') }}"
                            alt="Sample verification result showing a valid Barangay Clearance with its permit number, reference number and applicant name"
                            width="992" height="704" loading="lazy"
                            class="w-full rounded-xl border border-white/20 shadow-2xl shadow-black/30 dark:border-white/10 dark:shadow-none">
                    </figure>
                </div>

                <dl data-reveal
                    class="mt-14 grid gap-6 border-t border-white/15 pt-8 md:grid-cols-3 md:gap-8 dark:border-white/10">
                    @foreach ([['QR code on the document', 'Scan it to open the verification result on this website.'], ['Checked against the record', 'The result comes from the approved permit record in the barangay system.'], ['Privacy protected', 'Only limited details are shown, and the applicant\'s name is shortened.']] as [$name, $text])
                        <div class="flex gap-3">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-blue-200 dark:text-emerald-300" fill="none"
                                stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <div>
                                <dt class="text-sm font-bold dark:text-white">{{ $name }}</dt>
                                <dd class="mt-1 text-sm leading-6 text-blue-100/75 dark:text-white/60">
                                    {{ $text }}</dd>
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
                <p class="text-sm font-semibold text-blue-900 dark:text-emerald-300">Frequently asked questions</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-blue-900 sm:text-4xl dark:text-white">
                    Quick
                    answers.</h2>
                <p class="mt-4 text-sm leading-6 text-blue-900/70 dark:text-white/60">
                    Need more help? Visit the barangay office or see the
                    <a href="#contact"
                        class="font-semibold text-blue-900 underline underline-offset-2 dark:text-emerald-300">contact
                        details</a>.
                </p>
            </div>

            <div data-reveal style="--d: 120ms"
                class="divide-y divide-blue-100 border-y border-blue-100 dark:divide-white/10 dark:border-white/10">
                @foreach ([['What do I need before I apply?', 'An email address you can open, your profile details, and a valid ID for identity verification.'], ['How do I verify a document?', 'Scan the QR code on the document with your phone. It opens this website and shows whether the document is valid. No account is needed.'], ['Are there fees or processing times?', 'Requirements, fees, and processing times are confirmed by the barangay office. Contact the office before applying.']] as [$question, $answer])
                    <details class="group" @if ($loop->first) open @endif>
                        <summary
                            class="flex cursor-pointer list-none items-center justify-between gap-4 py-5 text-base font-semibold text-blue-900 transition-colors hover:text-blue-700 dark:text-emerald-300 dark:hover:text-white [&::-webkit-details-marker]:hidden">
                            {{ $question }}
                            <svg class="h-5 w-5 shrink-0 transition-transform duration-200 group-open:rotate-90"
                                fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </summary>
                        <p class="pb-5 pr-9 text-sm leading-6 text-blue-900/70 dark:text-white/60">
                            {{ $answer }}
                        </p>
                    </details>
                @endforeach
            </div>
        </section>

        {{-- Contact --}}
        <section id="contact"
            class="grid border-t border-blue-100 bg-blue-50/60 backdrop-blur-2xl lg:grid-cols-2 dark:border-white/10 dark:bg-[#152a1c]/40">
            <div class="min-w-0 px-5 py-14 sm:px-8 sm:py-20 lg:pl-[max(2rem,calc(50vw-38rem))] lg:pr-12">
                <p class="text-sm font-semibold text-blue-900 dark:text-emerald-300">Get in touch</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-blue-900 sm:text-4xl dark:text-white">
                    Contact Us
                </h2>

                <p class="mt-4 max-w-md text-sm leading-6 text-blue-900/70 dark:text-white/60">
                    Visit the barangay office or message us on Facebook for announcements and inquiries.
                </p>

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
                            <svg class="h-6 w-6 shrink-0 text-blue-900 dark:text-emerald-300" fill="none"
                                stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                            </svg>

                            <div class="min-w-0">
                                <dt class="text-sm font-semibold text-blue-900/70 dark:text-white/60">
                                    {{ $name }}</dt>
                                <dd
                                    class="wrap-break-word mt-0.5 text-sm font-semibold text-blue-900 dark:text-white">
                                    @if ($link)
                                        <a href="{{ $link }}"
                                            @if ($name === 'Facebook') target="_blank" rel="noopener noreferrer" @endif
                                            class="break-all underline-offset-4 transition-colors hover:text-blue-700 hover:underline dark:hover:text-white">{{ $value }}</a>
                                    @else
                                        {{ $value }}
                                    @endif
                                </dd>
                            </div>
                        </div>
                    @endforeach
                </dl>

                <p class="mt-8 max-w-md text-sm leading-6 text-blue-900/70 dark:text-white/60">
                    Confirm the contact number and office hours with the barangay before visiting.
                </p>
            </div>

            <div
                class="flex min-w-0 flex-col px-5 pb-14 sm:px-8 sm:pb-20 lg:py-20 lg:pl-0 lg:pr-[max(2rem,calc(50vw-38rem))]">
                <iframe title="Map of Barangay San Luis, Antipolo City" loading="lazy"
                    class="lg:min-h-100 min-h-80 w-full flex-1 rounded-md border border-t-4 border-blue-100 border-t-blue-900 bg-white/70 sm:min-h-96 dark:border-white/10 dark:border-t-emerald-300/60 dark:bg-white/5"
                    src="https://www.google.com/maps?q=Barangay+San+Luis+Antipolo+City+Rizal&output=embed">
                </iframe>
                <a href="https://www.google.com/maps/search/?api=1&query=Barangay+San+Luis+Antipolo+City+Rizal"
                    target="_blank" rel="noopener noreferrer"
                    class="mt-3 inline-flex w-fit items-center gap-2 text-sm font-semibold text-blue-900 underline decoration-blue-900 decoration-2 underline-offset-4 transition-colors hover:text-blue-700 dark:text-emerald-300 dark:decoration-[#596e56] dark:hover:text-white">
                    Open in Google Maps
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5"
                        viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
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

        menuButton.addEventListener('click', toggleMenu);
        menu.querySelectorAll('a').forEach((link) => link.addEventListener('click', toggleMenu));

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !menu.classList.contains('hidden')) {
                toggleMenu();
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

    <x-govern-footer />
</body>

</html>