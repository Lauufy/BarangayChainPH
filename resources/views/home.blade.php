@php
    $facebook = 'https://www.facebook.com/barangay.sanluis.35';

    // Guests fall through to the login page, so $dashboard is always a safe link target
    $dashboard = match (auth()->user()?->role) {
        'admin', 'staff' => route('admin.dashboard'),
        'citizen' => route('citizen.dashboard'),
        default => route('login'),
    };

    // ---------- SETTINGS (leave null if not available yet) ----------
    $applyRoute = null; // route name: page where a resident starts a new request
    $trackRoute = null; // route name: page that lists the resident's requests

    // Pass $announcements from the controller. Each item: title, optional date, summary, url.
    $announcements = collect($announcements ?? [])->take(3);
    // -----------------------------------------------------------------

    // Use a named route only if it exists, otherwise the fallback
    $go = fn($name, $fallback) => $name && Route::has($name) ? route($name) : $fallback;

    // Small right-arrow icon
    $arrow = fn($class = 'h-4 w-4') => '<svg class="' . $class . '" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>';
    $check = 'M4.5 12.75l6 6 9-13.5';

    $actions = [
        ['Apply', 'Submit a certificate request', $go($applyRoute, auth()->check() ? $dashboard : route('register'))],
        ['Track', 'Follow your request status', $go($trackRoute, $dashboard)],
        ['Verify', 'Check an issued document', '#verify'],
    ];

    $navLinks = [['#about', 'About'], ['#services', 'Services'], ['#process', 'How it works']];
    if ($announcements->isNotEmpty()) {
        $navLinks[] = ['#announcements', 'Updates'];
    }
    $navLinks = array_merge($navLinks, [['#verify', 'Verify'], ['#faq', 'FAQ'], ['#contact', 'Contact']]);

    $about = [
        ['For residents', 'Apply for clearances and permits from home and follow your request online, with fewer trips to the barangay office.'],
        ['For the barangay', 'Requests come into one system, which reduces paperwork and manual work for barangay staff.'],
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
        ['QR code on the document', 'Scan it to open the verification result on this website.'],
        ['Checked against the record', 'The result comes from the approved permit record in the barangay system.'],
        ['Privacy protected', 'Only limited details are shown, and the applicant\'s name is shortened.'],
    ];

    $verifySteps = [
        'Find the QR code on the document issued by the barangay.',
        'Scan it with your phone camera or any QR scanner.',
        'The result opens on this website and shows whether the document is valid.',
    ];

    $faqs = [
        ['What do I need before I apply?', 'An email address you can open, your profile details, and a valid ID for identity verification.'],
        ['How do I verify a document?', 'Scan the QR code on the document with your phone. It opens this website and shows whether the document is valid. No account is needed.'],
        ['Are there fees or processing times?', 'Requirements, fees, and processing times are confirmed by the barangay office. Contact the office before applying.'],
    ];

    $mapQuery = 'Barangay+San+Luis+Antipolo+City+Rizal';

    // Contact rows: icon path, label, value, link (null = no link)
    $contacts = [
        ['M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z', 'Location', 'Barangay San Luis, Antipolo City, Rizal 1870', null],
        ['M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z', 'Office hours', 'Monday to Friday, 8:00 AM to 5:00 PM', null],
        ['M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z', 'Phone', '8-8555-245', 'tel:88555245'],
        ['M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75', 'Email', 'barangaychain@gmail.com', 'mailto:barangaychain@gmail.com'],
        ['M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25', 'Facebook', 'Visit our Facebook page', $facebook],
    ];

    // Shared classes
    $btn = 'inline-flex items-center justify-center gap-2 rounded-md px-5 py-3 text-sm font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:shadow-md';
    $button = "$btn bg-blue-950 hover:bg-blue-800";
    $accent = "$btn bg-emerald-700 hover:bg-emerald-600";
    $ghost = 'inline-flex items-center justify-center rounded-md border border-white/50 px-5 py-3 text-sm font-semibold text-white transition-colors duration-200 hover:bg-white hover:text-blue-950';
    $textLink = 'inline-flex items-center gap-2 text-sm font-semibold text-blue-900 underline decoration-emerald-600 decoration-2 underline-offset-4 transition-colors hover:text-emerald-700';
    $label = 'text-sm font-semibold text-blue-800';
    $title = 'mt-2 text-3xl font-bold tracking-tight text-blue-950 sm:text-4xl';
    $section = 'mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-24';
    $gray = 'border-y border-slate-200 bg-slate-50';
    $card = 'rounded-md border border-slate-200 bg-white';
    $navLink = 'relative py-1 transition-colors hover:text-blue-900 focus-visible:text-blue-900 aria-[current=true]:font-semibold aria-[current=true]:text-blue-900 after:absolute after:inset-x-0 after:-bottom-0.5 after:h-0.5 after:origin-left after:scale-x-0 after:bg-emerald-600 after:transition-transform after:duration-200 hover:after:scale-x-100 focus-visible:after:scale-x-100 aria-[current=true]:after:scale-x-100';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-pt-24 scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description"
        content="BarangayChain PH is the online service portal of Barangay San Luis, Antipolo City, Rizal. Request barangay certificates, track your application, and verify documents.">
    <title>BarangayChain PH — Barangay San Luis, Antipolo City</title>
    <script>document.documentElement.classList.add('js')</script>
    <style>
        body { overflow-x: clip; }

        :focus-visible { outline: 2px solid #059669; outline-offset: 3px; }

        /* Fade-in on scroll (only when JavaScript is on) */
        .js [data-reveal] {
            opacity: 0;
            transform: translateY(14px);
            transition: opacity .55s ease, transform .55s ease;
            transition-delay: var(--d, 0ms);
        }

        .js [data-reveal].is-visible { opacity: 1; transform: none; }

        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            .js [data-reveal] { opacity: 1; transform: none; transition: none; }
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-white text-slate-800 antialiased">

    <x-feedbacks.toast />

    <a href="#main"
        class="sr-only z-60 rounded-md bg-white px-4 py-2 text-sm font-semibold text-blue-950 focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip
        to main content</a>

    {{-- Top bar --}}
    <div class="bg-blue-950 text-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-2 text-xs sm:px-8">
            <p>Republic of the Philippines / Province of Rizal / City of Antipolo</p>
            <a href="{{ $facebook }}" target="_blank" rel="noopener noreferrer"
                class="hidden text-blue-100 transition-colors hover:text-emerald-300 sm:inline">Official announcements</a>
        </div>
    </div>

    {{-- Header (mobile menu below 1280px) --}}
    <header class="sticky top-0 z-50 border-b border-slate-200 bg-white transition-shadow duration-300">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-5 py-4 sm:px-8">
            <x-partials.logo />

            <nav aria-label="Main" class="hidden items-center gap-5 text-sm text-slate-600 xl:flex">
                @foreach ($navLinks as [$link, $text])
                    <a href="{{ $link }}" class="{{ $navLink }}">{{ $text }}</a>
                @endforeach
            </nav>

            <div class="hidden items-center gap-4 xl:flex">
                @auth
                    <a href="{{ $dashboard }}" class="{{ $button }}">Open dashboard</a>
                @else
                    <a href="{{ route('login') }}"
                        class="text-sm font-semibold text-slate-700 transition-colors hover:text-blue-900">Sign in</a>
                    <a href="{{ route('register') }}" class="{{ $button }}">Create account</a>
                @endauth
            </div>

            <button type="button" id="menu-button" aria-label="Toggle menu" aria-controls="mobile-menu"
                aria-expanded="false" class="rounded-md p-2 text-blue-950 transition-colors hover:bg-slate-100 xl:hidden">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path id="icon-open" stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" />
                    <path id="icon-close" class="hidden" stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
        </div>

        <nav id="mobile-menu" aria-label="Mobile" class="hidden border-t border-slate-200 bg-white xl:hidden">
            <div class="mx-auto flex max-w-7xl flex-col px-5 pb-4 text-sm font-medium text-slate-700 sm:px-8">
                @foreach ($navLinks as [$link, $text])
                    <a href="{{ $link }}"
                        class="border-b border-slate-100 py-3 transition-colors hover:text-blue-900 focus-visible:bg-emerald-50 focus-visible:text-blue-900">{{ $text }}</a>
                @endforeach

                <div class="mt-4 flex flex-col gap-3">
                    @auth
                        <a href="{{ $dashboard }}" class="{{ $button }}">Open dashboard</a>
                    @else
                        <a href="{{ route('login') }}"
                            class="rounded-md border border-blue-950 px-5 py-3 text-center font-semibold text-blue-950">Sign in</a>
                        <a href="{{ route('register') }}" class="{{ $button }}">Create account</a>
                    @endauth
                </div>
            </div>
        </nav>
    </header>

    <main id="main">

        {{-- Hero --}}
        <section class="relative isolate overflow-hidden bg-blue-950">
            <img src="{{ asset('img/photos/ynares-hall-front.jpg') }}" alt="" width="1214" height="724"
                class="absolute inset-0 -z-20 h-full w-full object-cover object-right">
            <div class="bg-linear-to-r absolute inset-0 -z-10 from-blue-950 via-blue-950/85 to-blue-950/50"></div>

            <div class="mx-auto max-w-7xl px-5 pb-28 pt-16 sm:px-8 sm:pb-32 sm:pt-20 lg:pb-40 lg:pt-28">
                <p class="text-sm font-semibold text-emerald-300">Official Online Portal of Barangay San Luis</p>
                <h1 class="mt-3 max-w-3xl text-4xl font-bold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">
                    Barangay services, made easier and more accessible.
                </h1>
                <p class="mt-6 max-w-xl text-base leading-7 text-blue-100 sm:text-lg">
                    Access online barangay services, submit certificate requests, track applications, and verify
                    issued documents through one secure portal.
                </p>
                <div class="mt-8 flex flex-wrap items-center gap-3">
                    @auth
                        <a href="{{ $dashboard }}" class="{{ $accent }}">Go to your dashboard</a>
                    @else
                        <a href="{{ route('register') }}" class="{{ $accent }}">Apply online</a>
                    @endauth
                    <a href="#services" class="{{ $ghost }}">See available services</a>
                </div>
                @guest
                    <p class="mt-4 text-sm text-blue-100">
                        New here? Create an account first, then choose a service.
                        <a href="{{ route('login') }}" class="font-semibold text-white underline underline-offset-4 hover:text-emerald-300">Already registered? Sign in</a>
                    </p>
                @endguest

                <p class="absolute bottom-4 right-5 hidden text-xs text-white/70 sm:right-8 lg:block">
                    Ynares Hall, Barangay San Luis
                </p>
            </div>
        </section>

        {{-- Quick actions and office info --}}
        <section class="relative z-10 mx-auto -mt-20 max-w-7xl px-5 sm:px-8 lg:-mt-24">
            <div data-reveal class="overflow-hidden rounded-md border border-slate-200 border-t-4 border-t-emerald-500 bg-white shadow-lg">
                <div class="grid divide-y divide-slate-200 md:grid-cols-3 md:divide-x md:divide-y-0">
                    @foreach ($actions as [$name, $caption, $href])
                        <a href="{{ $href }}"
                            class="group flex items-center justify-between gap-4 p-6 transition-colors hover:bg-emerald-50">
                            <div>
                                <p class="text-lg font-bold text-blue-950">{{ $name }}</p>
                                <p class="mt-1 text-sm text-slate-600">{{ $caption }}</p>
                            </div>
                            {!! $arrow('h-6 w-6 shrink-0 text-emerald-700 transition-transform duration-200 group-hover:translate-x-1') !!}
                        </a>
                    @endforeach
                </div>

                <dl class="grid gap-4 border-t border-slate-200 bg-slate-50 px-6 py-4 text-sm md:grid-cols-3 md:gap-6">
                    @foreach (array_slice($contacts, 0, 3) as [, $name, $value, $link])
                        <div>
                            <dt class="text-sm font-semibold text-slate-600">{{ $name }}</dt>
                            <dd class="mt-0.5 font-semibold text-blue-950">
                                @if ($link)
                                    <a href="{{ $link }}" class="underline-offset-4 hover:text-emerald-700 hover:underline">{{ $value }}</a>
                                @else
                                    {{ $value }}
                                @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>

        {{-- About --}}
        <section id="about" class="{{ $section }} grid items-center gap-12 lg:grid-cols-2 lg:gap-20">
            <div data-reveal>
                <p class="{{ $label }}">About the website</p>
                <h2 class="{{ $title }}">An easier way to get barangay services.</h2>
                <p class="mt-5 max-w-prose leading-7 text-slate-600">
                    BarangayChain PH is an online barangay service and clearance permit portal for the residents
                    of Barangay San Luis. It is built for easy access and to lessen the physical work for both
                    the residents and the barangay.
                </p>

                <div class="mt-8 grid gap-6 sm:grid-cols-2">
                    @foreach ($about as [$name, $text])
                        <div class="border-l-2 border-emerald-600 pl-4">
                            <p class="text-sm font-bold text-blue-950">{{ $name }}</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $text }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div data-reveal style="--d: 120ms" class="relative mx-auto w-full max-w-md pb-3 pr-3 lg:mr-0">
                <span aria-hidden="true"
                    class="absolute bottom-0 right-0 h-[calc(100%-0.75rem)] w-[calc(100%-0.75rem)] rounded-md border-2 border-emerald-600"></span>
                <img src="{{ asset('img/photos/ynares-hall-side.jpg') }}" alt="Entrance of Ynares Hall"
                    width="548" height="428" loading="lazy"
                    class="relative aspect-4/3 w-full rounded-md object-cover shadow-md">
            </div>
        </section>

        {{-- Services --}}
        <section id="services" class="{{ $gray }}">
            <div class="{{ $section }}">
                <div data-reveal class="max-w-xl">
                    <p class="{{ $label }}">Online services</p>
                    <h2 class="{{ $title }}">What can we help you with?</h2>
                </div>

                <div class="mt-10 grid gap-6 md:grid-cols-3">
                    @foreach ($services as [$number, $name, $text])
                        <div data-reveal style="--d: {{ $loop->index * 120 }}ms">
                            <a href="{{ $go($applyRoute, $dashboard) }}"
                                class="{{ $card }} group flex h-full flex-col border-t-4 border-t-blue-950 p-7 transition duration-300 hover:-translate-y-1 hover:border-t-emerald-500 hover:shadow-lg">
                                <span class="text-sm font-semibold text-emerald-700">{{ $number }}</span>
                                <h3 class="mt-4 text-xl font-bold text-blue-950">{{ $name }}</h3>
                                <p class="mt-3 flex-1 text-sm leading-6 text-slate-600">{{ $text }}</p>
                                <span
                                    class="mt-8 inline-flex w-fit items-center gap-2 rounded-md border border-blue-950 px-4 py-2 text-sm font-semibold text-blue-950 transition-colors duration-200 group-hover:bg-blue-950 group-hover:text-white">
                                    Request this service {!! $arrow() !!}
                                </span>
                            </a>
                        </div>
                    @endforeach
                </div>

                <p class="mt-6 text-sm leading-6 text-slate-600">
                    Requirements, fees, and processing times are subject to confirmation by the barangay office.
                </p>
            </div>
        </section>

        {{-- Process --}}
        <section id="process">
            <div class="{{ $section }}">
                <div data-reveal class="max-w-xl">
                    <p class="{{ $label }}">The process</p>
                    <h2 class="{{ $title }}">From registration to request tracking.</h2>
                </div>

                <ol data-reveal
                    class="mt-12 grid divide-y divide-slate-300 overflow-hidden rounded-md border border-slate-300 bg-white md:grid-cols-4 md:divide-x md:divide-y-0">
                    @foreach ($steps as [$number, $name, $text])
                        <li class="group relative p-7 transition-colors duration-300 hover:bg-emerald-50">
                            <span aria-hidden="true"
                                class="{{ ['bg-blue-950', 'bg-blue-900', 'bg-emerald-800', 'bg-emerald-600'][$loop->index] }} absolute inset-x-0 top-0 h-1"></span>
                            <p class="text-4xl font-bold tracking-tight text-blue-950 transition-colors duration-300 group-hover:text-emerald-700">{{ $number }}</p>
                            <h3 class="mt-6 text-lg font-bold text-blue-950">{{ $name }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $text }}</p>
                        </li>
                    @endforeach
                </ol>

                <div data-reveal class="mt-8">
                    <a href="{{ $actions[0][2] }}" class="{{ $accent }}">
                        @auth Go to your dashboard @else Get started @endauth
                        {!! $arrow() !!}
                    </a>
                </div>
            </div>
        </section>

        {{-- Announcements: only when there is data --}}
        @if ($announcements->isNotEmpty())
            <section id="announcements" class="{{ $gray }}">
                <div class="{{ $section }}">
                    <div data-reveal class="flex flex-wrap items-end justify-between gap-4">
                        <div class="max-w-xl">
                            <p class="{{ $label }}">Announcements and updates</p>
                            <h2 class="{{ $title }}">Latest from the barangay.</h2>
                        </div>
                        <a href="{{ $facebook }}" target="_blank" rel="noopener noreferrer" class="{{ $textLink }}">
                            More on Facebook {!! $arrow() !!}
                        </a>
                    </div>

                    <div class="mt-10 grid gap-6 md:grid-cols-3">
                        @foreach ($announcements as $item)
                            @php
                                $url = data_get($item, 'url');
                                $when = filled(data_get($item, 'date')) ? \Illuminate\Support\Carbon::parse(data_get($item, 'date')) : null;
                            @endphp
                            <article data-reveal style="--d: {{ $loop->index * 120 }}ms"
                                class="{{ $card }} flex flex-col border-t-4 border-t-blue-950 p-6">
                                @if ($when)
                                    <time datetime="{{ $when->toDateString() }}"
                                        class="text-xs font-semibold text-emerald-700">{{ $when->format('F j, Y') }}</time>
                                @endif
                                <h3 class="mt-2 text-lg font-bold text-blue-950">{{ data_get($item, 'title') }}</h3>
                                @if (data_get($item, 'summary'))
                                    <p class="mt-3 flex-1 text-sm leading-6 text-slate-600">{{ data_get($item, 'summary') }}</p>
                                @endif
                                @if ($url)
                                    <a href="{{ $url }}" class="{{ $textLink }} mt-5 w-fit">Read more</a>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- Verification --}}
        <section id="verify" class="bg-blue-950 text-white">
            <div class="{{ $section }}">
                <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                    <div data-reveal>
                        <p class="text-sm font-semibold text-emerald-400">Verification</p>
                        <h2 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">
                            Scan the QR code to check a document.
                        </h2>
                        <p class="mt-4 max-w-prose text-base leading-7 text-blue-100/80">
                            Documents issued by Barangay San Luis carry a QR code. Scanning it opens this website and
                            shows whether the document is valid.
                        </p>

                        <h3 class="mt-8 text-lg font-bold">How to verify a document</h3>
                        <ol class="mt-4 space-y-4">
                            @foreach ($verifySteps as $text)
                                <li class="flex gap-4 text-sm leading-6 text-blue-100">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white">{{ $loop->iteration }}</span>
                                    <span class="pt-0.5">{{ $text }}</span>
                                </li>
                            @endforeach
                        </ol>

                        <p class="mt-8 max-w-md border-l-2 border-emerald-500 pl-4 text-sm leading-6 text-blue-100">
                            No account or sign-in is needed to check a document. If the result shows the
                            document is not valid, contact the barangay office.
                        </p>
                    </div>

                    <figure data-reveal style="--d: 120ms">
                        <figcaption class="mb-3 text-sm font-semibold text-blue-100">What you will see after scanning</figcaption>
                        <img src="{{ asset('img/photos/verify-sample.png') }}"
                            alt="Sample verification result showing a valid Barangay Clearance with its permit number, reference number and applicant name"
                            width="992" height="704" loading="lazy"
                            class="w-full rounded-xl border border-white/20 shadow-2xl shadow-black/30">
                    </figure>
                </div>

                <dl data-reveal class="mt-14 grid gap-6 border-t border-white/15 pt-8 md:grid-cols-3 md:gap-8">
                    @foreach ($features as [$name, $text])
                        <div class="flex gap-3">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-400" fill="none" stroke="currentColor"
                                stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $check }}" />
                            </svg>
                            <div>
                                <dt class="text-sm font-bold">{{ $name }}</dt>
                                <dd class="mt-1 text-sm leading-6 text-blue-100/75">{{ $text }}</dd>
                            </div>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>

        {{-- FAQ --}}
        <section id="faq" class="{{ $section }} grid gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:gap-20">
            <div data-reveal>
                <p class="{{ $label }}">Frequently asked questions</p>
                <h2 class="{{ $title }}">Quick answers.</h2>
                <p class="mt-4 text-sm leading-6 text-slate-600">
                    Need more help? Visit the barangay office or see the
                    <a href="#contact" class="font-semibold text-blue-900 underline underline-offset-2">contact details</a>.
                </p>
            </div>

            <div data-reveal style="--d: 120ms" class="divide-y divide-slate-300 border-y border-slate-300">
                @foreach ($faqs as [$question, $answer])
                    <details class="group" @if ($loop->first) open @endif>
                        <summary
                            class="flex cursor-pointer list-none items-center justify-between gap-4 py-5 text-base font-semibold text-blue-950 transition-colors hover:text-emerald-700 [&::-webkit-details-marker]:hidden">
                            {{ $question }}
                            {!! $arrow('h-5 w-5 shrink-0 transition-transform duration-200 group-open:rotate-90') !!}
                        </summary>
                        <p class="pb-5 pr-9 text-sm leading-6 text-slate-600">{{ $answer }}</p>
                    </details>
                @endforeach
            </div>
        </section>

        {{-- Contact --}}
        <section id="contact" class="grid border-t border-slate-200 bg-slate-50 lg:grid-cols-2">
            <div class="min-w-0 px-5 py-14 sm:px-8 sm:py-20 lg:pl-[max(2rem,calc(50vw-38rem))] lg:pr-12">
                <p class="{{ $label }}">Get in touch</p>
                <h2 class="{{ $title }}">Contact Us</h2>

                <p class="mt-4 max-w-md text-sm leading-6 text-slate-600">
                    Visit the barangay office or message us on Facebook for announcements and inquiries.
                </p>

                <dl class="mt-8 space-y-5">
                    @foreach ($contacts as [$icon, $name, $value, $link])
                        <div class="flex items-center gap-4">
                            <svg class="h-6 w-6 shrink-0 text-blue-900" fill="none" stroke="currentColor"
                                stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                            </svg>

                            <div class="min-w-0">
                                <dt class="text-sm font-semibold text-slate-600">{{ $name }}</dt>
                                <dd class="wrap-break-word mt-0.5 text-sm font-semibold text-blue-950">
                                    @if ($link)
                                        <a href="{{ $link }}"
                                            @if ($name === 'Facebook') target="_blank" rel="noopener noreferrer" @endif
                                            class="break-all underline-offset-4 transition-colors hover:text-blue-700 hover:underline">{{ $value }}</a>
                                    @else
                                        {{ $value }}
                                    @endif
                                </dd>
                            </div>
                        </div>
                    @endforeach
                </dl>

                <p class="mt-8 max-w-md text-sm leading-6 text-slate-600">
                    Confirm the contact number and office hours with the barangay before visiting.
                </p>
            </div>

            <div class="flex min-w-0 flex-col px-5 pb-14 sm:px-8 sm:pb-20 lg:py-20 lg:pl-0 lg:pr-[max(2rem,calc(50vw-38rem))]">
                <iframe title="Map of Barangay San Luis, Antipolo City" loading="lazy"
                    class="min-h-80 w-full flex-1 rounded-md border border-slate-300 border-t-4 border-t-emerald-500 bg-white sm:min-h-96 lg:min-h-100"
                    src="https://www.google.com/maps?q={{ $mapQuery }}&output=embed">
                </iframe>
                <a href="https://www.google.com/maps/search/?api=1&query={{ $mapQuery }}" target="_blank"
                    rel="noopener noreferrer" class="{{ $textLink }} mt-3 w-fit">
                    Open in Google Maps {!! $arrow() !!}
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
        addEventListener('scroll', onScroll, { passive: true });
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
            }), { rootMargin: '-40% 0px -55% 0px' });

            navLinks.forEach((a) => {
                const target = document.querySelector(a.hash);
                if (target) spy.observe(target);
            });

            const reveal = new IntersectionObserver((items) => items.forEach((item) => {
                if (!item.isIntersecting) return;
                item.target.classList.add('is-visible');
                reveal.unobserve(item.target);
            }), { threshold: 0.12 });

            reveals.forEach((el) => reveal.observe(el));
        }
    </script>

    <x-govern-footer />
</body>

</html>