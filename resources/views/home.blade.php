@use('Illuminate\Support\Carbon')

{{--
    Optional data (share from the controller or a view composer):
      $dashboard     string|null  dashboard URL when signed in
      $announcements array        up to 3 items: title, date, summary, url
--}}

@php
    $dashboard = $dashboard ?? null;
    $announcements = $announcements ?? [];
    $hasAnnouncements = filled($announcements);

    // Shared class tokens (full class names stay in this file so Tailwind detects them)
    $glow = 'shadow-[0_14px_40px_-16px_color-mix(in_srgb,var(--celadon-500)_45%,transparent)]';
    $container = 'mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-24';
    $h2 = 'mt-3 text-4xl font-semibold leading-[1.08] tracking-tight text-balance sm:text-5xl';

    // Sections
    $lightSection = 'bg-(--blue-500)/25 text-(--blue-700) backdrop-blur-xl dark:bg-(--blue-900)/60 dark:text-white';
    $darkSection = 'bg-(--blue-850)/70 text-white backdrop-blur-xl';

    // Section edge fades (removed, kept as empty tokens so the class lists below still work)
    $fadeBoth = '';
    $fadeTop = '';
    $fadeBottom = '';

    // Eyebrow labels
    $eyebrowLight = 'text-xs font-bold uppercase tracking-wider text-(--blue-700) dark:text-(--celadon-500)';
    $eyebrowDark = 'text-xs font-bold uppercase tracking-wider text-(--celadon-500)';

    // Cards
    $lightCard = "rounded-sm border border-(--blue-700)/20 bg-white/75 p-6 backdrop-blur-md $glow dark:border-(--celadon-500)/25 dark:bg-(--blue-950)/50 dark:hover:border-(--celadon-500)/60";
    $darkCard = "rounded-sm border border-white/15 bg-white/5 backdrop-blur-md $glow hover:border-(--celadon-500)/60";

    // Buttons, icons, form fields
    $btn =
        'items-center rounded-sm bg-(--blue-700) px-5 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-(--celadon-500) hover:text-(--blue-850) dark:bg-(--celadon-500) dark:text-(--blue-850) dark:hover:bg-white';
    $iconCircle =
        'inline-flex shrink-0 items-center justify-center rounded-full bg-(--celadon-500)/15 text-(--celadon-500) ring-1 ring-(--celadon-500)/30';
    $input =
        'w-full rounded-sm border border-white/20 bg-white/10 px-4 py-3 text-sm text-white backdrop-blur-md placeholder:text-white/70 focus:border-(--celadon-500) focus:outline-none focus:ring-1 focus:ring-(--celadon-500)/50';

    $shield =
        'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-pt-24 scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description"
        content="BarangayChain PH is the blockchain-enabled e-governance portal of Barangay San Luis, Antipolo City. Request permits online and verify issued documents.">
    <meta name="theme-color" content="#173266">
    <meta property="og:type" content="website">
    <meta property="og:title" content="BarangayChain PH — Barangay San Luis, Antipolo City">
    <meta property="og:description"
        content="Request barangay certificates online and verify issued documents by QR code or hash.">
    <meta property="og:image" content="{{ asset('img/photos/ynares-hall-front.jpg') }}">
    <title>BarangayChain PH — Barangay San Luis, Antipolo City</title>
    <link rel="icon" type="image/png" href="{{ asset('img/branding/barangaychain-lg1.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="text-(--blue-700) overflow-x-clip antialiased transition-colors duration-300 dark:text-white">

    <x-feedbacks.toast />

    {{-- Backdrop behind the acrylic sections --}}
    <div aria-hidden="true" class="bg-(--blue-50) dark:bg-(--blue-950) fixed inset-0 -z-10 overflow-hidden">
        <div class="h-136 w-136 bg-(--blue-500)/30 absolute -left-40 top-20 rounded-full blur-3xl"></div>
        <div class="h-144 w-xl bg-(--blue-700)/25 absolute -right-40 top-1/2 rounded-full blur-3xl"></div>
        <div class="h-112 w-md bg-(--blue-500)/20 absolute bottom-0 left-1/3 rounded-full blur-3xl"></div>
    </div>

    <a href="#main"
        class="text-(--blue-700) focus:z-60 sr-only rounded-sm bg-white px-4 py-2 text-sm font-semibold focus:not-sr-only focus:fixed focus:left-4 focus:top-4">
        Skip to main content
    </a>

    {{-- Header --}}
    <header
        class="border-(--blue-700)/10 text-(--blue-700) dark:bg-(--blue-900)/80 sticky top-0 z-50 border-b bg-white/90 backdrop-blur-md dark:border-white/10 dark:text-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-5 py-3.5 sm:px-8">
            <x-partials.logo />

            <nav id="menu" aria-label="Main"
                class="max-xl:border-(--blue-700)/10 dark:max-xl:bg-(--blue-900) flex flex-col text-sm max-xl:absolute max-xl:inset-x-0 max-xl:top-full max-xl:hidden max-xl:border-t max-xl:bg-white max-xl:px-5 max-xl:pb-3 xl:flex-row xl:gap-6 dark:max-xl:border-white/10">
                @foreach ([
        '#about' => 'About',
        '#services' => 'Services',
        '#process' => 'How it works',
        '#announcements' => $hasAnnouncements ? 'Updates' : null,
        '#verify' => 'Verify',
        '#faq' => 'FAQ',
        '#contact' => 'Contact',
    ] as $href => $label)
                    @continue(!$label)
                    <a href="{{ $href }}"
                        class="aria-current:after:scale-x-100 dark:after:bg-(--celadon-500) relative py-3 after:absolute after:inset-x-0 after:-bottom-0.5 after:h-0.5 after:origin-left after:scale-x-0 after:bg-current after:transition-transform hover:after:scale-x-100 xl:py-1">{{ $label }}</a>
                @endforeach

                {{-- Sign in / Create account (mobile menu only, header buttons cover sm and up) --}}
                <div
                    class="border-(--blue-700)/10 mt-2 flex flex-col gap-2 border-t py-3 sm:hidden dark:border-white/10">
                    @if ($dashboard)
                        <a href="{{ $dashboard }}" class="{{ $btn }} flex justify-center">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="py-2 text-sm font-semibold">Sign in</a>
                        <a href="{{ route('register') }}" class="{{ $btn }} flex justify-center">Create
                            account</a>
                    @endif
                </div>
            </nav>

            <div class="flex items-center gap-3">
                <x-partials.theme-toggle />

                @if ($dashboard)
                    <a href="{{ $dashboard }}" class="{{ $btn }} hidden sm:inline-flex">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="hidden text-sm font-semibold hover:underline sm:inline">Sign
                        in</a>
                    <a href="{{ route('register') }}" class="{{ $btn }} hidden sm:inline-flex">Create
                        account</a>
                @endif

                <button type="button" id="menu-button" aria-label="Toggle menu" aria-controls="menu"
                    aria-expanded="false"
                    class="hover:bg-(--blue-700)/10 group rounded-sm p-2 xl:hidden dark:hover:bg-white/10">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path class="group-aria-expanded:hidden" stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" />
                        <path class="hidden group-aria-expanded:block" stroke-linecap="round"
                            d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <main id="main">

        {{-- Hero --}}
        <section class="{{ $fadeBottom }} bg-(--blue-850) relative isolate overflow-hidden text-white">
            <x-partials.blueprint base="bg-(--celadon-500)" src="{{ asset('img/photos/ynares-hall-front.jpg') }}"
                pos="object-center lg:object-right" class="absolute inset-0 -z-20" />

            {{-- Blends the photo into the navy on the text side --}}
            <div aria-hidden="true"
                class="bg-linear-to-b from-(--blue-850) via-(--blue-850)/80 to-(--blue-850)/40 lg:bg-linear-to-r lg:from-(--blue-850) lg:via-(--blue-850)/85 absolute inset-0 -z-10 lg:from-35% lg:via-55% lg:to-transparent">
            </div>

            <div class="mx-auto max-w-7xl px-5 pb-20 pt-10 sm:px-8 sm:pt-16 lg:pb-24 lg:pt-20">
                <div class="relative z-10 lg:w-[55%]">
                    <p class="text-(--celadon-500) mt-5 text-xs font-semibold uppercase tracking-wider">
                        Blockchain-Enabled e-Governance Web Portal
                    </p>

                    <h1
                        class="mt-3 text-balance text-4xl font-semibold leading-[1.06] tracking-tight [text-shadow:0_2px_22px_color-mix(in_srgb,var(--celadon-500)_40%,transparent)] sm:text-6xl xl:text-[4.25rem]">
                        Secure permit processing, <span class="text-(--celadon-500)">verified</span> by design.
                    </h1>

                    <p class="mt-6 max-w-lg text-base leading-7 text-white/80">
                        An intelligent public service portal for Barangay San Luis. Request clearances online, track
                        real-time approvals, and authenticate tamper-proof documents via QR or cryptographic hash.
                    </p>

                    <div class="mt-8 max-w-lg">
                        <a href="{{ $dashboard ?? route('register') }}"
                            class="border-white/12 bg-(--blue-900)/90 hover:ring-(--celadon-500)/60 group flex items-center justify-between gap-4 rounded-sm border py-2.5 pl-5 pr-2.5 ring-1 ring-white/15 backdrop-blur-sm transition">
                            <span class="text-sm font-medium text-white/85">
                                {{ $dashboard ? 'Access your citizen or staff workspace' : 'Start a secure certificate or permit request' }}
                            </span>
                            <span
                                class="bg-(--blue-850) group-hover:bg-(--celadon-500) group-hover:text-(--blue-850) flex h-11 shrink-0 items-center justify-center gap-2 rounded-lg px-5 text-sm font-semibold text-white ring-1 ring-white/40 transition duration-200">
                                {{ $dashboard ? 'Dashboard' : 'Apply Online' }}
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </span>
                        </a>


                    </div>

                    <dl class="mt-10 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        @foreach ([['SHA-256', 'Tamper-proof ledger'], ['24/7', 'Online portal access'], ['0 Login', 'Public QR verification'], ['Mon–Fri', '8:00 AM – 5:00 PM']] as [$value, $label])
                            <div class="border-white/12 bg-(--blue-900)/80 rounded-sm border p-4 backdrop-blur-sm">
                                <dt class="text-(--celadon-500) text-xl font-semibold tracking-tight sm:text-2xl">
                                    {{ $value }}</dt>
                                <dd class="mt-1 text-xs leading-5 text-white/75">{{ $label }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>

            {{-- Floating shortcuts --}}
            <div
                class="mx-auto max-w-7xl px-5 pb-16 sm:px-8 lg:pointer-events-none lg:absolute lg:inset-x-0 lg:top-1/2 lg:-translate-y-1/2 lg:p-0 lg:px-8">
                <div class="lg:w-76 grid gap-3 sm:grid-cols-3 lg:pointer-events-auto lg:ml-auto lg:grid-cols-1">
                    @foreach ([['Request e-Permit', 'Submit digital applications anytime.', $dashboard ?? route('register'), 'M12 4.5v15m7.5-7.5h-15'], ['Smart Status Tracking', 'Monitor real-time approval stages.', $dashboard ?? route('login'), 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'], ['Verify on Blockchain', 'Scan QR or check document hash.', '#verify', $shield]] as [$title, $text, $href, $icon])
                        <a href="{{ $href }}"
                            class="border-white/12 bg-(--blue-900)/85 hover:ring-(--celadon-500)/60 flex items-center gap-3.5 rounded-sm border p-3.5 ring-1 ring-white/15 backdrop-blur-md transition duration-300 hover:-translate-y-0.5">
                            <span class="{{ $iconCircle }} h-12 w-12">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5"
                                    viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                                </svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold">{{ $title }}</span>
                                <span class="mt-0.5 block text-xs leading-5 text-white/75">{{ $text }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- About --}}
        <section id="about" class="{{ $lightSection }} {{ $fadeBoth }}">
            <div class="{{ $container }}">
                <div class="grid gap-12 lg:grid-cols-2 lg:gap-16">
                    <div>
                        <p class="{{ $eyebrowLight }}">About BarangayChain PH</p>
                        <h2 class="{{ $h2 }}">Modernizing local services for Barangay San Luis.</h2>
                        <p class="text-current/75 mt-6 max-w-lg text-base leading-7">
                            BarangayChain PH lets residents request common barangay documents online and lets anyone
                            confirm that an issued document is genuine.
                        </p>
                        <div class="mt-8 flex flex-wrap gap-3">
                            @if ($dashboard)
                                <a href="{{ $dashboard }}" class="{{ $btn }} inline-flex">Open
                                    Workspace</a>
                            @else
                                <a href="{{ route('register') }}" class="{{ $btn }} inline-flex">Register
                                    as Resident</a>
                            @endif
                            <a href="#verify"
                                class="border-current/35 hover:bg-current/10 inline-flex items-center rounded-sm border px-5 py-3 text-sm font-semibold transition">Verify
                                a Document</a>
                        </div>
                    </div>

                    <div class="border-current/15 border-b">
                        @foreach ([['Online requests', 'Submit clearances and certificates digitally instead of queueing at Ynares Hall.'], ['Application tracking', 'Follow the status of your request from your account.'], ['Verifiable documents', 'Approved documents carry a QR code and a hash anchored on the blockchain.']] as [$title, $text])
                            <div class="border-current/15 grid gap-2 border-t py-6 sm:grid-cols-[12rem_1fr] sm:gap-8">
                                <h3 class="font-semibold">{{ $title }}</h3>
                                <p class="text-current/75 text-sm leading-6">{{ $text }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-12 grid gap-4 md:grid-cols-2">
                    @foreach ([['Our Mission', 'To give residents and barangay staff a simple digital platform that reduces paperwork and helps protect the authenticity of official documents.'], ['Our Vision', 'A model of local e-governance in Antipolo City, where public documents are trusted and every resident is served well.']] as [$title, $text])
                        <article class="{{ $lightCard }}">
                            <p class="{{ $eyebrowLight }}">{{ $title }}</p>
                            <p class="text-current/75 mt-3 text-sm leading-6">{{ $text }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Services --}}
        <section id="services" class="{{ $darkSection }} {{ $fadeBoth }}">
            <div class="{{ $container }}">
                <div class="max-w-2xl">
                    <p class="{{ $eyebrowDark }}">Services</p>
                    <h2 class="{{ $h2 }}">Find the certificate you need.</h2>
                    <p class="mt-4 text-sm leading-6 text-white/75">Fees and eligibility are confirmed by Barangay San
                        Luis staff.</p>
                </div>

                <div class="mt-10 grid gap-5 md:grid-cols-3">
                    @foreach ([['Barangay Clearance', 'Official clearance for local employment, business permits, and government transactions.', 'QR-verifiable'], ['Certificate of Residency', 'Proof of residence within Barangay San Luis, Antipolo City.', 'QR-verifiable'], ['Certificate of Indigency', 'For residents applying for medical, educational, legal, or social welfare assistance.', 'Free of charge']] as [$name, $text, $meta])
                        <a href="{{ $dashboard ?? route('register') }}"
                            class="{{ $darkCard }} group flex min-h-72 flex-col justify-between p-7 transition duration-300 hover:-translate-y-1">
                            <div>
                                <span class="{{ $eyebrowDark }}">0{{ $loop->iteration }} •
                                    {{ $meta }}</span>
                                <h3 class="mt-4 text-2xl font-semibold leading-tight">{{ $name }}</h3>
                                <p class="mt-3 text-sm leading-6 text-white/75">{{ $text }}</p>
                            </div>
                            <span
                                class="text-(--celadon-500) mt-8 inline-flex items-center gap-2 text-sm font-semibold transition-transform group-hover:translate-x-1">Request
                                this service →</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- How it works --}}
        <section id="process" class="{{ $lightSection }} {{ $fadeBoth }}">
            <div class="{{ $container }}">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="{{ $eyebrowLight }}">How it works</p>
                    <h2 class="{{ $h2 }}">From application to verified document.</h2>
                </div>

                <ol class="mt-12 grid gap-5 md:grid-cols-3">
                    @foreach ([['Create an account', 'Register with your Barangay San Luis details.'], ['Submit a request', 'Choose a certificate, state your purpose, and submit it online.'], ['Claim and verify', 'Once approved, your document is issued with a QR code and a ledger hash.']] as [$name, $text])
                        <li class="{{ $lightCard }}">
                            <span
                                class="text-(--blue-500) dark:text-(--celadon-500) font-mono text-sm font-bold">0{{ $loop->iteration }}</span>
                            <h3 class="mt-3 text-xl font-semibold">{{ $name }}</h3>
                            <p class="text-current/75 mt-2 text-sm leading-6">{{ $text }}</p>
                        </li>
                    @endforeach
                </ol>

                <div class="mt-12 text-center">
                    <a href="{{ $dashboard ?? route('register') }}" class="{{ $btn }} inline-flex">
                        {{ $dashboard ? 'Go to your dashboard' : 'Start your application' }}
                    </a>
                </div>
            </div>
        </section>

        {{-- Announcements (optional) --}}
        @if ($hasAnnouncements)
            <section id="announcements" class="{{ $darkSection }} {{ $fadeBoth }}">
                <div class="{{ $container }}">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div class="max-w-xl">
                            <p class="{{ $eyebrowDark }}">Community Advisories</p>
                            <h2 class="{{ $h2 }}">Latest from Barangay San Luis.</h2>
                        </div>
                        <a href="https://www.facebook.com/barangay.sanluis.35" target="_blank"
                            rel="noopener noreferrer"
                            class="text-(--celadon-500) text-sm font-semibold underline underline-offset-4">More on
                            Facebook →</a>
                    </div>

                    <div class="mt-10 grid gap-6 md:grid-cols-3">
                        @foreach (collect($announcements)->take(3) as $item)
                            @php($date = data_get($item, 'date') ? Carbon::parse(data_get($item, 'date')) : null)
                            <article class="{{ $darkCard }} flex flex-col p-7">
                                @if ($date)
                                    <time class="{{ $eyebrowDark }}"
                                        datetime="{{ $date->toDateString() }}">{{ $date->format('F j, Y') }}</time>
                                @endif
                                <h3 class="mt-3 text-xl font-semibold">{{ data_get($item, 'title') }}</h3>
                                @if ($summary = data_get($item, 'summary'))
                                    <p class="mt-3 flex-1 text-sm leading-6 text-white/75">{{ $summary }}</p>
                                @endif
                                @if ($url = data_get($item, 'url'))
                                    <a href="{{ $url }}"
                                        class="text-(--celadon-500) mt-5 w-fit text-sm font-semibold underline underline-offset-4">Read
                                        more</a>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- Verification --}}
        <section id="verify" class="{{ $darkSection }} {{ $fadeBoth }} relative isolate overflow-hidden">
            <x-partials.blueprint base="bg-transparent" grid="80"
                class="mask-[linear-gradient(to_right,black,transparent_85%)] absolute inset-0 -z-10" />

            <div class="{{ $container }}">
                <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                    <div>
                        <div
                            class="bg-(--celadon-500)/15 text-(--celadon-500) ring-(--celadon-500)/40 inline-flex items-center gap-2 rounded-md px-3 py-1 text-xs font-bold uppercase tracking-wider ring-1">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $shield }}" />
                            </svg>
                            Cryptographic Authenticity
                        </div>

                        <h2
                            class="mt-4 text-balance text-4xl font-semibold leading-[1.05] tracking-tight [text-shadow:0_2px_22px_color-mix(in_srgb,var(--celadon-500)_40%,transparent)] sm:text-5xl">
                            Verify any barangay permit in seconds.
                        </h2>
                        <p class="mt-5 max-w-prose text-base leading-7 text-white/80">
                            Every certificate issued through BarangayChain PH is anchored with a tamper-proof
                            cryptographic signature and QR code. Employers, banks, and agencies can confirm
                            authenticity immediately without creating an account.
                        </p>

                        <h3 class="mt-8 text-lg font-semibold">Three-step verification</h3>
                        <ol class="mt-4 list-none space-y-4">
                            @foreach (['Locate the official BarangayChain QR code printed on the bottom corner of the document.', 'Scan using any smartphone camera to launch the live ledger verification page.', 'Confirm that the permit reference number, issue date, and cryptographic hash match.'] as $text)
                                <li class="flex gap-4 text-sm leading-6 text-white/85">
                                    <span aria-hidden="true"
                                        class="bg-(--celadon-500) mt-2 h-1.5 w-1.5 shrink-0 rounded-full"></span>
                                    <span>{{ $text }}</span>
                                </li>
                            @endforeach
                        </ol>

                        <p
                            class="border-(--celadon-500) mt-8 max-w-md border-l-2 pl-4 text-sm leading-6 text-white/85">
                            Public verification respects Data Privacy Act (RA 10173) guidelines by masking sensitive
                            personal fields while proving record validity.
                        </p>
                    </div>

                    <figure
                        class="bg-(--blue-900)/90 {{ $glow }} rounded-xl p-3 ring-1 ring-white/15 backdrop-blur-md">
                        <div
                            class="mb-2.5 flex items-center justify-between border-b border-white/10 px-2 pb-2 text-xs">
                            <span class="font-semibold">Live Verification Preview</span>
                            <span class="text-(--celadon-500) inline-flex items-center gap-1.5 font-mono text-[11px]">
                                <span class="bg-(--celadon-500) h-1.5 w-1.5 rounded-full"></span>
                                HASH VERIFIED
                            </span>
                        </div>
                        <img src="{{ asset('img/photos/verify-sample.png') }}"
                            alt="Sample verification result showing a valid Barangay Clearance with its permit number, reference number and applicant name"
                            width="992" height="704" loading="lazy" class="w-full rounded-lg">
                        <figcaption
                            class="mt-2.5 flex flex-wrap items-center justify-between gap-2 px-2 text-[11px] text-white/65">
                            <span>Immutable Record Proof</span>
                            <span class="font-mono text-white/90">SHA-256 • 0x94a7...3f8c</span>
                        </figcaption>
                    </figure>
                </div>

                <dl class="mt-14 grid gap-6 border-t border-white/15 pt-8 md:grid-cols-3 md:gap-8">
                    @foreach ([['Anti-Counterfeit QR Seal', 'Eliminates forged signatures and altered dates on printed barangay clearances.'], ['Blockchain Ledger Anchored', 'Each approved permit creates an immutable audit trail verified against barangay records.'], ['Data Privacy Compliant', 'Displays only essential verification metadata with partially masked resident details.']] as [$name, $text])
                        <div class="flex gap-3">
                            <svg class="text-(--celadon-500) mt-0.5 h-5 w-5 shrink-0" fill="none"
                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <div>
                                <dt class="text-sm font-semibold">{{ $name }}</dt>
                                <dd class="mt-1 text-sm leading-6 text-white/75">{{ $text }}</dd>
                            </div>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>

        {{-- FAQ --}}
        <section id="faq" class="{{ $lightSection }} {{ $fadeBoth }}">
            <div class="{{ $container }} grid gap-10 lg:grid-cols-[0.85fr_1.15fr] lg:gap-20">
                <div class="lg:sticky lg:top-28 lg:self-start">
                    <p class="{{ $eyebrowLight }}">Citizen Help Desk</p>
                    <h2 class="mt-3 text-balance text-4xl font-semibold leading-[1.05] tracking-tight sm:text-6xl">
                        Quick
                        <span class="soft">answers.</span>
                    </h2>
                    <p class="text-current/75 mt-5 text-sm leading-6">
                        Have specific questions about requirements? Use our <strong>24/7 AI Assistant</strong> in the
                        bottom-right corner or check our
                        <a href="#contact" class="font-semibold underline underline-offset-2">office contact
                            details</a>.
                    </p>
                </div>

                <div class="divide-current/15 border-current/15 divide-y border-y">
                    @foreach ([
        ['How does blockchain make my barangay permit more secure?', 'When Barangay San Luis approves your certificate, the system generates a unique cryptographic hash (digital fingerprint) linked to your document. If anyone alters the text or date on the document, scanning its QR code will immediately flag it as invalid.'],
        ['What do I need before applying online?', 'You need an active email address, accurate personal details, and a clear photo of a valid government-issued ID showing your Barangay San Luis, Antipolo City address.'],
        ['Who is eligible to use BarangayChain PH?', 'The portal is exclusively built for bonafide residents of Barangay San Luis, Antipolo City, as well as third-party verifiers (employers, banks, agencies) checking issued documents.'],
        ['Do I need an account to verify a QR code?', 'No. Anyone receiving a BarangayChain PH certificate can scan the QR code using their phone camera to verify its authenticity instantly without signing in.'],
        ['How are fees and processing times handled?', 'Certificate fees (if applicable) and processing schedules follow official Barangay San Luis ordinances. Certificates of Indigency for qualified residents are issued free of charge.'],
    ] as [$question, $answer])
                        <details class="group" @open($loop->first)>
                            <summary
                                class="hover:text-(--blue-500) dark:hover:text-(--celadon-500) flex cursor-pointer list-none items-center justify-between gap-4 py-6 text-lg font-semibold transition-colors [&::-webkit-details-marker]:hidden">
                                {{ $question }}
                                <svg class="h-5 w-5 shrink-0 transition-transform duration-200 group-open:rotate-90"
                                    fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                </svg>
                            </summary>
                            <p class="text-current/75 pb-6 pr-9 text-sm leading-6">{{ $answer }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Contact --}}
        <section id="contact" class="{{ $darkSection }} {{ $fadeTop }}">
            <div class="{{ $container }} grid gap-12 lg:grid-cols-2 lg:gap-16">
                <div class="min-w-0">
                    <p class="{{ $eyebrowDark }}">Barangay Hall Directory</p>
                    <h2 class="mt-3 text-balance text-4xl font-semibold leading-[1.05] tracking-tight sm:text-5xl">
                        Connect with <span class="soft">Barangay San Luis.</span>
                    </h2>
                    <p class="mt-4 max-w-md text-sm leading-6 text-white/75">
                        For in-person assistance, document claiming, or community inquiries, visit Ynares Hall or reach
                        out through our official channels.
                    </p>

                    <dl class="mt-8 space-y-5">
                        @foreach ([
        ['M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z', 'Location', 'Ynares Hall, Barangay San Luis, Antipolo City, Rizal 1870', null],
        ['M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z', 'Office Hours', 'Monday to Friday, 8:00 AM to 5:00 PM', null],
        ['M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z', 'Telephone', '8-8555-245', 'tel:88555245'],
        ['M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75', 'Email', 'barangaychain@gmail.com', 'mailto:barangaychain@gmail.com'],
        ['M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25', 'Official Facebook', 'Barangay San Luis Official Page', 'https://www.facebook.com/barangay.sanluis.35'],
    ] as [$icon, $name, $value, $link])
                            <div class="flex items-center gap-4">
                                <span class="{{ $iconCircle }} h-11 w-11">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5"
                                        viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="{{ $icon }}" />
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <dt class="text-xs font-semibold text-white/75">{{ $name }}</dt>
                                    <dd class="wrap-break-word mt-0.5 text-sm font-semibold">
                                        @if ($link)
                                            <a href="{{ $link }}"
                                                @if (str_starts_with($link, 'http')) target="_blank" rel="noopener noreferrer" @endif
                                                class="hover:text-(--celadon-500) break-all underline-offset-4 transition-colors hover:underline">{{ $value }}</a>
                                        @else
                                            {{ $value }}
                                        @endif
                                    </dd>
                                </div>
                            </div>
                        @endforeach
                    </dl>
                </div>

                <form method="POST" action="{{ url('/contact') }}" class="min-w-0">
                    @csrf
                    <h3 class="text-xl font-semibold">Send us a message</h3>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <input type="text" name="first_name" placeholder="First Name" required
                            autocomplete="given-name" class="{{ $input }}">
                        <input type="text" name="last_name" placeholder="Last Name" required
                            autocomplete="family-name" class="{{ $input }}">
                        <input type="email" name="email" placeholder="Email Address" required
                            autocomplete="email" class="{{ $input }} sm:col-span-2">
                        <select name="service" required class="{{ $input }}">
                            <option value="" class="text-(--blue-700)">Service Interested In</option>
                            @foreach (['Barangay Clearance', 'Certificate of Residency', 'Certificate of Indigency', 'Other'] as $service)
                                <option class="text-(--blue-700)">{{ $service }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="subject" placeholder="Subject" required
                            class="{{ $input }}">
                        <textarea name="message" rows="5" placeholder="Tell Us About Your Request" required
                            class="{{ $input }} resize-none sm:col-span-2"></textarea>
                    </div>

                    <button type="submit"
                        class="text-(--blue-900) {{ $glow }} hover:bg-(--celadon-500) hover:text-(--blue-850) mt-4 w-full rounded-sm bg-white px-5 py-3 text-sm font-semibold transition hover:-translate-y-0.5">Submit</button>
                </form>
            </div>

            {{-- Map --}}
            <div class="mx-auto max-w-7xl px-5 pb-16 sm:px-8 sm:pb-24">
                <iframe title="Map of Barangay San Luis, Antipolo City" loading="lazy"
                    class="{{ $glow }} h-80 w-full rounded-xl sm:h-96"
                    src="https://www.google.com/maps?q=Barangay+San+Luis+Antipolo+City+Rizal&output=embed"></iframe>
                <a href="https://www.google.com/maps/search/?api=1&query=Barangay+San+Luis+Antipolo+City+Rizal"
                    target="_blank" rel="noopener noreferrer"
                    class="text-(--celadon-500) mt-3 inline-flex w-fit items-center gap-2 text-sm font-semibold underline underline-offset-4">
                    Open in Google Maps →
                </a>
            </div>
        </section>

    </main>

    <script>
        const menu = document.getElementById('menu');
        const menuButton = document.getElementById('menu-button');
        const links = menu.querySelectorAll('a[href^="#"]');

        const isOpen = () => menuButton.getAttribute('aria-expanded') === 'true';
        const setMenu = (open) => {
            menu.classList.toggle('max-xl:hidden', !open);
            menuButton.setAttribute('aria-expanded', String(open));
        };

        menuButton.addEventListener('click', () => setMenu(!isOpen()));
        menu.addEventListener('click', (e) => e.target.closest('a') && setMenu(false));
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && isOpen()) {
                setMenu(false);
                menuButton.focus();
            }
        });

        // Highlight the current section in the nav
        if ('IntersectionObserver' in window) {
            const spy = new IntersectionObserver((entries) => entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    links.forEach((a) => a.setAttribute('aria-current', a.hash === '#' + entry.target.id));
                }
            }), {
                rootMargin: '-40% 0px -55% 0px'
            });

            links.forEach((a) => {
                const target = document.querySelector(a.hash);
                if (target) spy.observe(target);
            });
        }
    </script>

    <script src="https://www.tuqlas.com/chatbot.js" data-key="tq_live_b008f3e486ad59592451ce7d324b4c54c94ccd3a"
        data-api="https://www.tuqlas.com" defer></script>

    <x-govern-footer />
</body>

</html>
