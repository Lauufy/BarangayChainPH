@php
    $facebook = 'https://www.facebook.com/barangay.sanluis.35';

    // Guests fall through to the login page, so $dashboard is always a safe link target
    $dashboard = match (auth()->user()?->role) {
        'admin', 'staff' => route('admin.dashboard'),
        'citizen' => route('citizen.dashboard'),
        default => route('login'),
    };

    $navLinks = [
        ['#about', 'About'],
        ['#services', 'Online Services'],
        ['#process', 'How It Works'],
        ['#blockchain', 'Verification'],
        ['#contact', 'Contact'],
    ];

    $about = [
        [
            'For residents',
            'Apply for clearances and permits from home and follow your request online, with fewer trips to the barangay office.',
        ],
        [
            'For the barangay',
            'Requests come into one system, which reduces paperwork and manual work for barangay staff.',
        ],
    ];

    $services = [
        ['01', 'Barangay Clearance', 'For employment, applications, and other official transactions.'],
        ['02', 'Certificate of Residency', 'Proof of residence in Barangay San Luis.'],
        ['03', 'Certificate of Indigency', 'For residents applying for assistance.'],
    ];

    $steps = [
        ['01', 'Create an account', 'Register and verify your email.'],
        ['02', 'Verify your identity', 'Submit your profile and valid ID.'],
        ['03', 'Request a service', 'Choose a certificate and submit.'],
        ['04', 'Track the status', 'Check updates from your account.'],
    ];

    $features = [
        ['Transaction ledger', 'Records transactions for traceability.'],
        ['QR verification', 'Check a document using its verification code.'],
        ['Document integrity', 'Compare a digital copy with the system record.'],
    ];

    $actions = [['Apply', 'Submit details'], ['Track', 'Follow progress'], ['Verify', 'Check documents']];

    // Contact rows: icon path, label, value, link (null = no link)
    $contacts = [
        [
            'M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z',
            'Location',
            'Barangay San Luis, Antipolo City, Rizal 1870',
            null,
        ],
        ['M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z', 'Office hours', 'Monday to Friday, 8:00 AM to 5:00 PM', null],
        [
            'M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z',
            'Phone',
            '8-8555-245',
            'tel:88555245',
        ],
        [
            'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75',
            'Email',
            'barangaychain@gmail.com',
            'mailto:barangaychain@gmail.com',
        ],
    ];

    // Shared classes
    $button = 'inline-flex items-center gap-3 bg-blue-900 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-800';
    $label = 'text-xs font-bold uppercase tracking-[0.18em] text-blue-800';
    $title = 'mt-3 text-3xl font-semibold tracking-tight text-blue-950 sm:text-4xl';
    $section = 'mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-20';
    $split = 'grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:gap-20';
    $list = 'divide-y divide-slate-300 border-y border-slate-300';
    $row = 'grid gap-2 py-5 sm:grid-cols-[150px_1fr] sm:gap-6';
@endphp
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
    <style>
        body {
            overflow-x: clip;
        }

        /* Fade-in on scroll */
        .js [data-reveal] {
            opacity: 0;
            transform: translateY(16px);
            transition: all .6s ease;
            transition-delay: var(--d, 0ms);
        }

        .js [data-reveal="left"] {
            transform: translateX(-24px);
        }

        .js [data-reveal="right"] {
            transform: translateX(24px);
        }

        .js [data-reveal].is-visible {
            opacity: 1;
            transform: none;
        }

        @media (prefers-reduced-motion: reduce) {
            .js [data-reveal] {
                opacity: 1;
                transform: none;
                transition: none;
            }
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-white text-slate-800 antialiased">

    <x-feedbacks.toast />

    {{-- Soft blue and green glow in the background --}}
    <div aria-hidden="true" class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="h-112 w-md absolute -left-32 -top-32 rounded-full bg-blue-900/15 blur-3xl"></div>
        <div class="h-120 w-120 bg-green-800/12 absolute -right-32 top-1/3 rounded-full blur-3xl"></div>
        <div class="h-104 w-104 bg-blue-950/12 absolute -bottom-40 left-1/4 rounded-full blur-3xl"></div>
    </div>

    {{-- Top bar --}}
    <div class="bg-blue-950 text-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-2 text-xs sm:px-8">
            <p>Republic of the Philippines / Province of Rizal / City of Antipolo</p>
            <a href="{{ $facebook }}" target="_blank" rel="noopener noreferrer"
                class="hidden text-blue-100 hover:text-white sm:inline">Official announcements</a>
        </div>
    </div>

    {{-- Header --}}
    <header class="sticky top-0 z-50 border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-5 py-4 sm:px-8">

            <x-partials.logo />

            {{-- Desktop menu --}}
            <nav class="hidden items-center gap-6 text-sm text-slate-600 lg:flex">
                @foreach ($navLinks as [$link, $text])
                    <a href="{{ $link }}" class="hover:text-blue-900">{{ $text }}</a>
                @endforeach
            </nav>

            {{-- Desktop buttons --}}
            <div class="hidden items-center gap-4 lg:flex">
                @auth
                    <a href="{{ $dashboard }}" class="{{ $button }}">Open dashboard →</a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-700 hover:text-blue-900">Sign
                        in</a>
                    <a href="{{ route('register') }}" class="{{ $button }}">Create account →</a>
                @endauth
            </div>

            {{-- Mobile menu button --}}
            <button type="button" id="menu-button" aria-label="Toggle menu" class="p-2 text-blue-950 lg:hidden">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path id="icon-open" stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" />
                    <path id="icon-close" class="hidden" stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
        </div>

        {{-- Mobile menu --}}
        <nav id="mobile-menu" class="hidden border-t border-slate-200 bg-white lg:hidden">
            <div class="mx-auto flex max-w-7xl flex-col px-5 pb-4 text-sm font-medium text-slate-700 sm:px-8">
                @foreach ($navLinks as [$link, $text])
                    <a href="{{ $link }}"
                        class="border-b border-slate-100 py-3 hover:text-blue-900">{{ $text }}</a>
                @endforeach

                <div class="mt-4 flex flex-col gap-3">
                    @auth
                        <a href="{{ $dashboard }}" class="{{ $button }} justify-center">Open dashboard →</a>
                    @else
                        <a href="{{ route('login') }}"
                            class="border border-slate-300 px-5 py-3 text-center font-semibold text-slate-700">Sign in</a>
                        <a href="{{ route('register') }}" class="{{ $button }} justify-center">Create account →</a>
                    @endauth
                </div>
            </div>
        </nav>
    </header>

    <main>

        {{-- Hero --}}
        <section class="lg:min-h-142.5 mx-auto mt-5 grid max-w-7xl lg:grid-cols-[1.02fr_0.98fr]">
            <div class="flex flex-col justify-center px-5 py-16 sm:px-8 sm:py-20 lg:py-24">
                <p data-reveal class="{{ $label }} flex items-center gap-3">
                    <span class="h-px w-8 bg-green-700"></span> Official Barangay Portal
                </p>
                <h1 data-reveal style="--d: 100ms"
                    class="mt-6 max-w-xl text-4xl font-semibold leading-tight tracking-tight text-blue-950 sm:text-5xl">
                    Online services of Barangay San Luis.
                </h1>
                <p data-reveal style="--d: 200ms" class="mt-6 max-w-lg text-base leading-7 text-slate-600">
                    Apply for barangay certificates and clearances, track the status of your request, and verify
                    issued documents.
                </p>
                <div data-reveal style="--d: 300ms" class="mt-8 flex flex-wrap items-center gap-4">
                    @auth
                        <a href="{{ $dashboard }}" class="{{ $button }}">Go to your dashboard →</a>
                    @else
                        <a href="{{ route('register') }}" class="{{ $button }}">Apply online →</a>
                    @endauth
                    <a href="#services" class="text-sm font-semibold text-blue-950 underline underline-offset-4">
                        See available services
                    </a>
                </div>
            </div>

            {{-- Dark panel --}}
            <div data-reveal="right" style="--d: 200ms"
                class="bg-linear-to-br relative flex min-h-80 flex-col justify-between overflow-hidden from-blue-900 via-blue-950 to-green-950 p-7 text-white sm:min-h-96 sm:p-10 lg:p-12">
                <img src="{{ asset('img/branding/barangayChainPH-logo.png') }}" alt=""
                    class="absolute right-8 top-8 h-12 w-12 object-contain opacity-70">

                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-200">Barangay San Luis</p>
                    <p class="mt-3 max-w-sm text-2xl font-medium leading-snug sm:text-3xl">
                        One place to begin your service request.
                    </p>
                </div>

                <div class="mt-10 grid grid-cols-3 gap-3 border-t border-white/20 pt-5">
                    @foreach ($actions as [$name, $caption])
                        <div>
                            <p class="text-lg font-semibold">{{ $name }}</p>
                            <p class="mt-1 text-xs text-blue-200">{{ $caption }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- About --}}
        <section id="about" class="border-y border-slate-200">
            <div class="{{ $section }} {{ $split }}">
                <div data-reveal="left">
                    <p class="{{ $label }}">About the website</p>
                    <h2 class="{{ $title }}">An easier way to get barangay services.</h2>
                    <p class="mt-5 leading-7 text-slate-600">
                        BarangayChain PH is an online barangay service and clearance permit portal for the residents
                        of Barangay San Luis. It is built for easy access and to lessen the physical work for both
                        the residents and the barangay.
                    </p>
                </div>

                <div data-reveal="right" class="{{ $list }}">
                    @foreach ($about as [$name, $text])
                        <div class="{{ $row }}">
                            <p class="text-sm font-semibold text-blue-950">{{ $name }}</p>
                            <p class="text-sm leading-6 text-slate-600">{{ $text }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Services --}}
        <section id="services" class="{{ $section }}">
            <div data-reveal class="border-b border-slate-300 pb-7">
                <p class="{{ $label }}">Online services</p>
                <h2 class="{{ $title }}">What can we help you with?</h2>
            </div>

            <div class="divide-y divide-slate-300">
                @foreach ($services as [$number, $name, $text])
                    <a href="{{ $dashboard }}" data-reveal style="--d: {{ $loop->index * 120 }}ms"
                        class="group grid gap-3 py-6 sm:grid-cols-[64px_1fr_1fr_32px] sm:items-center sm:gap-5">
                        <span class="text-sm font-semibold text-green-800">{{ $number }}</span>
                        <h3 class="text-lg font-semibold text-blue-950 group-hover:text-blue-700">{{ $name }}
                        </h3>
                        <p class="text-sm leading-6 text-slate-600">{{ $text }}</p>
                        <span class="text-xl text-slate-400 group-hover:text-blue-900">→</span>
                    </a>
                @endforeach
            </div>

            <p class="mt-2 text-xs leading-5 text-slate-500">
                Requirements, fees, and processing times are subject to confirmation by the barangay office.
            </p>
        </section>

        {{-- Process --}}
        <section id="process" class="border-y border-slate-200">
            <div class="{{ $section }}">
                <p class="{{ $label }}">The process</p>
                <h2 class="{{ $title }}">From registration to request tracking.</h2>

                <div class="mt-10 grid gap-8 md:grid-cols-4">
                    @foreach ($steps as [$number, $name, $text])
                        <div data-reveal style="--d: {{ $loop->index * 120 }}ms"
                            class="border-t-2 border-blue-900 pt-5">
                            <p class="text-xs font-bold tracking-widest text-green-800">{{ $number }}</p>
                            <h3 class="mt-4 text-base font-semibold text-blue-950">{{ $name }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $text }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Verification --}}
        <section id="blockchain" class="{{ $section }} {{ $split }}">
            <div data-reveal="left">
                <p class="{{ $label }}">Built with verification in mind</p>
                <h2 class="{{ $title }}">Digital services with a record you can check.</h2>
                <p class="mt-5 leading-7 text-slate-600">
                    Blockchain technology supports traceable records and helps users check the authenticity of
                    documents issued by Barangay San Luis.
                </p>
                <a href="{{ $dashboard }}"
                    class="mt-7 inline-block text-sm font-semibold text-blue-900 hover:text-blue-700">
                    Access the portal →
                </a>
            </div>

            <div data-reveal="right" class="{{ $list }}">
                @foreach ($features as [$name, $text])
                    <div class="{{ $row }}">
                        <p class="text-sm font-semibold text-blue-950">{{ $name }}</p>
                        <p class="text-sm leading-6 text-slate-600">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Contact --}}
        <section id="contact" class="grid border-t border-slate-200 lg:grid-cols-2">

            {{-- Contact details --}}
            <div class="min-w-0 px-5 py-14 sm:px-8 sm:py-16 lg:pl-[max(2rem,calc(50vw-38rem))] lg:pr-8">
                <p class="{{ $label }} flex items-center gap-3">
                    Get in touch
                </p>

                <h2 class="{{ $title }}">Contact Us</h2>

                <p class="mt-4 max-w-md text-sm leading-6 text-slate-600">
                    Visit the barangay office or message us on Facebook for announcements and inquiries.
                </p>

                <dl class="{{ $list }} mt-8">
                    @foreach ($contacts as [$icon, $name, $value, $link])
                        <div class="flex gap-4 py-5">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-green-900" fill="none" stroke="currentColor"
                                stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                            </svg>

                            <div class="min-w-0">
                                <dt class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                    {{ $name }}
                                </dt>
                                <dd class="wrap-break-word mt-1 text-sm font-semibold text-blue-950">
                                    @if ($link)
                                        <a href="{{ $link }}"
                                            class="break-all hover:underline">{{ $value }}</a>
                                    @else
                                        {{ $value }}
                                    @endif
                                </dd>
                            </div>
                        </div>
                    @endforeach
                </dl>

                <a href="{{ $facebook }}" target="_blank" rel="noopener noreferrer"
                    class="{{ $button }} mt-8">Facebook page →</a>

                <p class="mt-4 max-w-md text-xs leading-5 text-slate-500">
                    Confirm the contact number and office hours with the barangay before visiting.
                </p>
            </div>

            {{-- Map --}}
            <div class="flex min-w-0 flex-col px-5 py-10 sm:px-8 sm:py-12 lg:px-8 lg:py-16">
                <div class="flex h-full w-full flex-col lg:w-[65%]">
                    <iframe title="Map of Barangay San Luis, Antipolo City" loading="lazy"
                        class="min-h-100 w-full flex-1 border-0"
                        src="https://www.google.com/maps?q=Barangay+San+Luis+Antipolo+City+Rizal&output=embed">
                    </iframe>

                    <div
                        class="flex shrink-0 items-center gap-3 border-t border-slate-200 bg-blue-950 px-5 py-4 text-white">
                        <svg class="h-5 w-5 shrink-0 text-green-300" fill="none" stroke="currentColor"
                            stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z" />
                            <circle cx="12" cy="10" r="2.5" />
                        </svg>

                        <div>
                            <p class="text-sm font-semibold">Barangay San Luis</p>
                            <p class="mt-1 text-xs text-slate-300">Antipolo City, Rizal</p>
                        </div>
                    </div>
                </div>
            </div>

        </section>

    </main>

    <script>
        // Mobile menu: open and close
        const menu = document.getElementById('mobile-menu');
        const iconOpen = document.getElementById('icon-open');
        const iconClose = document.getElementById('icon-close');

        function toggleMenu() {
            [menu, iconOpen, iconClose].forEach(el => el.classList.toggle('hidden'));
        }

        document.getElementById('menu-button').addEventListener('click', toggleMenu);
        menu.querySelectorAll('a').forEach(link => link.addEventListener('click', toggleMenu));

        // Fade in elements when they scroll into view
        const observer = new IntersectionObserver(items => {
            items.forEach(item => {
                if (item.isIntersecting) {
                    item.target.classList.add('is-visible');
                    observer.unobserve(item.target);
                }
            });
        }, {
            threshold: 0.12
        });

        document.querySelectorAll('[data-reveal]').forEach(el => observer.observe(el));
    </script>

    <x-govern-footer />
</body>

</html>
