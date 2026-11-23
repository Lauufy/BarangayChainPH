@use('Illuminate\Support\Carbon')

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-pt-24 scroll-smooth motion-reduce:scroll-auto">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description"
        content="BarangayChain PH is the blockchain-enabled e-governance portal of Barangay San Luis, Antipolo City. Request permits online and verify issued documents.">
    <meta name="theme-color" content="#173266" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#070b24" media="(prefers-color-scheme: dark)">
    <meta property="og:type" content="website">
    <meta property="og:title" content="BarangayChain PH — Barangay San Luis, Antipolo City">
    <meta property="og:description"
        content="Request barangay certificates online and verify issued documents by QR code or hash.">
    <meta property="og:image" content="{{ asset('img/photos/ynares-hall-front.jpg') }}">
    <title>BarangayChain PH — Barangay San Luis, Antipolo City</title>
    <link rel="icon" type="image/png" href="{{ asset('img/branding/barangaychain-lg1.png') }}">

    <script>
        document.documentElement.classList.add('js')
    </script>
    {{-- app.js imports ./reveal (scroll animations); app.css imports ./reveal.css --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body
    class="text-(--blue-700) dark:bg-(--blue-850) bg-(--off-white) dark:text-(--off-white) overflow-x-clip antialiased transition-colors duration-300">

    <x-feedbacks.toast />

    <a href="#main"
        class="text-(--blue-700) focus:z-60 sr-only rounded-sm bg-white px-4 py-2 text-sm font-semibold focus:not-sr-only focus:fixed focus:left-4 focus:top-4">
        Skip to main content
    </a>

    {{-- Header --}}
    <header id="site-header"
        class="border-(--blue-700)/10 text-(--blue-700) dark:bg-(--blue-850)/96 bg-white/96 dark:text-(--off-white) sticky top-0 z-50 border-b backdrop-blur-md dark:border-white/10">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-5 py-3.5 sm:px-8">
            <x-partials.logo />

            <nav id="menu" aria-label="Main"
                class="max-xl:border-(--blue-700)/10 dark:max-xl:bg-(--blue-850)/96 max-xl:bg-white/96 flex flex-col pt-4 text-sm max-xl:absolute max-xl:inset-x-0 max-xl:top-full max-xl:hidden max-xl:border-t max-xl:px-5 max-xl:pb-4 max-xl:shadow-xl xl:absolute xl:left-1/2 xl:-translate-x-1/2 xl:flex-row xl:items-center xl:gap-6 xl:whitespace-nowrap xl:pt-0 dark:max-xl:border-white/10">
                @foreach ([
        '#about' => 'About',
        '#services' => 'Services',
        '#process' => 'How it works',
        '#announcements' => filled($announcements ?? []) ? 'Updates' : null,
        '#verify' => 'Verify Document',
        '#faq' => 'FAQ',
        '#contact' => 'Contact',
    ] as $href => $label)
                    @continue(!$label)
                    <a href="{{ $href }}"
                        class="focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) max-xl:hover:bg-(--blue-700)/10 max-xl:focus-visible:bg-(--blue-700)/10 max-xl:aria-current:bg-(--blue-700)/10 dark:max-xl:aria-current:bg-white/10 aria-current:after:scale-x-100 dark:after:bg-(--celadon-500) relative rounded-sm px-3 py-3 transition-colors after:absolute after:inset-x-0 after:-bottom-0.5 after:h-0.5 after:origin-left after:scale-x-0 after:bg-current after:transition-transform hover:after:scale-x-100 focus-visible:outline-2 max-xl:after:hidden xl:px-0 xl:py-1 dark:max-xl:hover:bg-white/10 dark:max-xl:focus-visible:bg-white/10">{{ $label }}</a>
                @endforeach

                {{-- Sign in / Create account (mobile menu only) --}}
                <div
                    class="border-(--blue-700)/10 mt-2 flex flex-col gap-2 border-t pt-3 sm:hidden dark:border-white/10">
                    @if ($dashboard ?? null)
                        <a href="{{ $dashboard }}"
                            class="bg-(--blue-700) text-(--off-white) hover:bg-(--celadon-500) hover:text-(--blue-850) dark:bg-(--celadon-500) dark:text-(--blue-850) focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) flex items-center justify-center rounded-sm px-5 py-3 text-sm font-semibold transition hover:-translate-y-0.5 focus-visible:outline-2 focus-visible:outline-offset-2 motion-reduce:hover:translate-y-0 dark:hover:bg-white">Continue
                            to Dashboard</a>
                    @else
                        <a href="{{ route('login') }}"
                            class="focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) max-xl:hover:bg-(--blue-700)/10 relative rounded-sm px-3 py-3 text-center text-sm font-semibold transition-colors focus-visible:outline-2 dark:max-xl:hover:bg-white/10">
                            Sign in
                        </a>
                        <a href="{{ route('register') }}"
                            class="bg-(--blue-700) text-(--off-white) hover:bg-(--celadon-500) hover:text-(--blue-850) dark:bg-(--celadon-500) dark:text-(--blue-850) focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) flex items-center justify-center rounded-sm px-5 py-3 text-sm font-semibold transition hover:-translate-y-0.5 focus-visible:outline-2 focus-visible:outline-offset-2 motion-reduce:hover:translate-y-0 dark:hover:bg-white">
                            Create resident account
                        </a>
                    @endif
                </div>
            </nav>

            <div class="flex items-center gap-3">
                <x-partials.theme-toggle />

                @if ($dashboard ?? null)
                    <a href="{{ $dashboard }}"
                        class="bg-(--blue-700) text-(--off-white) hover:bg-(--celadon-500) hover:text-(--blue-850) dark:bg-(--celadon-500) dark:text-(--blue-850) focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) hidden items-center rounded-sm px-5 py-3 text-sm font-semibold transition hover:-translate-y-0.5 focus-visible:outline-2 focus-visible:outline-offset-2 motion-reduce:hover:translate-y-0 sm:inline-flex dark:hover:bg-white">Continue</a>
                @else
                    <a href="{{ route('login') }}"
                        class="focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) hidden rounded-sm px-2 py-1.5 text-sm font-semibold hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 sm:inline">
                        Sign in
                    </a>
                    <a href="{{ route('register') }}"
                        class="bg-(--blue-700) text-(--off-white) hover:bg-(--celadon-500) hover:text-(--blue-850) dark:bg-(--celadon-500) dark:text-(--blue-850) focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) hidden items-center rounded-sm px-5 py-3 text-sm font-semibold transition hover:-translate-y-0.5 focus-visible:outline-2 focus-visible:outline-offset-2 motion-reduce:hover:translate-y-0 sm:inline-flex dark:hover:bg-white">
                        Create account
                    </a>
                @endif

                <button type="button" id="menu-button" aria-label="Toggle menu" aria-controls="menu"
                    aria-expanded="false"
                    class="hover:bg-(--blue-700)/10 focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) group grid size-11 place-items-center rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 xl:hidden dark:hover:bg-white/10">
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
        <section class="bg-(--blue-850) text-(--off-white) relative isolate overflow-hidden">
            <x-partials.blueprint base="bg-(--celadon-500)" src="{{ asset('img/photos/ynares-hall-front.jpg') }}"
                pos="object-center lg:object-right" class="absolute inset-0 -z-20" />

            <div aria-hidden="true"
                class="bg-linear-to-b from-(--blue-850) via-(--blue-850)/85 to-(--blue-850)/60 lg:bg-linear-to-r lg:from-(--blue-850) lg:via-(--blue-850)/80 absolute inset-0 -z-10 lg:from-35% lg:via-55% lg:to-transparent">
            </div>

            <div
                class="mx-auto grid max-w-7xl items-center gap-10 px-5 py-14 sm:px-8 sm:py-20 lg:grid-cols-[1.1fr_0.9fr] lg:gap-12 lg:py-28">

                {{-- Value proposition & primary actions --}}
                <div class="relative z-10" data-reveal-group="up">

                    <h1
                        class="mt-4 text-balance text-4xl font-bold leading-[1.06] tracking-tight sm:text-6xl xl:text-[4rem]">
                        Verified by Design, <span class="text-(--celadon-500)">Secure Processing</span> of Permits.
                    </h1>

                    <p class="text-(--off-white)/85 mt-6 max-w-xl text-base leading-7">
                        Track staff approvals in real time, request barangay clearances and certificates online, and
                        Verify tamper-proof documents using a cryptographic hash or QR code.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                        <a href="{{ $dashboard ?? route('register') }}"
                            class="bg-(--celadon-500) text-(--blue-850) focus-visible:outline-(--celadon-500) inline-flex items-center justify-center gap-2 rounded-lg px-6 py-3.5 text-sm font-bold shadow-lg transition hover:-translate-y-0.5 hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 motion-reduce:hover:translate-y-0">
                            {{ $dashboard ?? null ? 'Open Citizen Workspace' : 'Apply for a Certificate' }}
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5"
                                viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </a>

                        <a href="#verify"
                            class="focus-visible:outline-(--celadon-500) text-(--off-white) inline-flex items-center justify-center gap-2 rounded-lg border border-white/25 bg-white/10 px-6 py-3.5 text-sm font-semibold backdrop-blur-sm transition hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-offset-2">
                            Verify a Document
                        </a>
                    </div>
                </div>

                {{-- Floating quick-action cards --}}
                <div class="relative z-10 flex flex-col gap-3 lg:items-end" data-reveal-group="right">

                    {{-- Card 1 --}}
                    <a href="{{ $dashboard ?? route('register') }}"
                        class="bg-(--blue-850)/75 hover:border-(--celadon-500)/70 focus-visible:outline-(--celadon-500) text-(--off-white) group flex items-center justify-center gap-3 rounded-full border border-white/15 px-5 py-3 shadow-lg ring-1 ring-white/10 backdrop-blur-md transition duration-200 hover:-translate-y-0.5 focus-visible:outline-2 focus-visible:outline-offset-2 motion-reduce:hover:translate-y-0 lg:mr-2">
                        <span
                            class="text-(--celadon-500) ring-(--celadon-500)/30 grid size-10 shrink-0 place-items-center rounded-full ring-1">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </span>
                        <span class="min-w-0 text-sm font-bold leading-tight">Digital Permit Processing</span>
                    </a>

                    {{-- Card 2 (offset) --}}
                    <a href="{{ $dashboard ?? route('login') }}"
                        class="bg-(--blue-850)/75 hover:border-(--celadon-500)/70 focus-visible:outline-(--celadon-500) text-(--off-white) group flex items-center justify-center gap-3 rounded-full border border-white/15 px-5 py-3 shadow-lg ring-1 ring-white/10 backdrop-blur-md transition duration-200 hover:-translate-y-0.5 focus-visible:outline-2 focus-visible:outline-offset-2 motion-reduce:hover:translate-y-0 lg:mr-14">
                        <span
                            class="text-(--celadon-500) ring-(--celadon-500)/30 grid size-10 shrink-0 place-items-center rounded-full ring-1">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                        <span class="min-w-0 text-sm font-bold leading-tight">Application Monitoring</span>
                    </a>

                    {{-- Card 3 --}}
                    <a href="#verify"
                        class="bg-(--blue-850)/75 hover:border-(--celadon-500)/70 focus-visible:outline-(--celadon-500) text-(--off-white) group flex items-center justify-center gap-3 rounded-full border border-white/15 px-5 py-3 shadow-lg ring-1 ring-white/10 backdrop-blur-md transition duration-200 hover:-translate-y-0.5 focus-visible:outline-2 focus-visible:outline-offset-2 motion-reduce:hover:translate-y-0 lg:mr-5">
                        <span
                            class="text-(--celadon-500) ring-(--celadon-500)/30 grid size-10 shrink-0 place-items-center rounded-full ring-1">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5"
                                viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                        </span>
                        <span class="min-w-0 text-sm font-bold leading-tight">Document Authentication</span>
                    </a>

                </div>
            </div>
        </section>

        {{-- Stats strip --}}
        <section
            class="bg-(--off-white) text-(--blue-700)/80 dark:bg-(--blue-850) dark:text-(--celadon-500)/70 border-(--blue-700)/10 border-b text-center dark:border-t dark:border-white/10">
            <div class="mx-auto max-w-7xl px-5 sm:px-8">
                <dl class="grid grid-cols-2 sm:grid-cols-4 sm:py-6" data-reveal-group="up">
                    <div class="px-3 py-6 sm:px-6 sm:py-2">
                        <dt class="sm:text-1xl text-xl font-bold tracking-tight lg:text-2xl">SHA-256</dt>
                        <dd class="text-current/75 mt-1 text-balance text-xs font-medium">Cryptographic ledger proof
                        </dd>
                    </div>

                    <div class="border-current/15 border-l px-3 py-6 sm:px-6 sm:py-2">
                        <dt class="sm:text-1xl text-xl font-bold tracking-tight lg:text-2xl">Digital Processing</dt>
                        <dd class="text-current/75 mt-1 text-balance text-xs font-medium">Online Permit Applications
                        </dd>
                    </div>

                    <div class="border-current/15 border-t px-3 py-6 sm:border-l sm:border-t-0 sm:px-6 sm:py-2">
                        <dt class="sm:text-1xl text-xl font-bold tracking-tight lg:text-2xl">QR Verification</dt>
                        <dd class="text-current/75 mt-1 text-balance text-xs font-medium">Public Document
                            Authentication</dd>
                    </div>

                    <div class="border-current/15 border-l border-t px-3 py-6 sm:border-t-0 sm:px-6 sm:py-2">
                        <dt class="sm:text-1xl text-xl font-bold tracking-tight lg:text-2xl">E-Governance</dt>
                        <dd class="text-current/75 mt-1 text-balance text-xs font-medium">Barangay San Luis, Antipolo
                            City</dd>
                    </div>
                </dl>
            </div>
        </section>

        {{-- About --}}
        <section id="about"
            class="bg-(--off-white) text-(--blue-700) dark:bg-(--blue-850) dark:text-(--off-white) dark:border-t dark:border-white/10">
            <div class="mx-auto max-w-7xl px-5 py-12 sm:px-8 sm:py-24">
                <div class="grid gap-12 lg:grid-cols-2 lg:gap-16">

                    <div data-reveal="left">
                        <p
                            class="text-(--blue-700) dark:text-(--celadon-500) text-xs font-bold uppercase tracking-wider">
                            About BarangayChain PH</p>
                        <h2 class="mt-3 text-balance text-3xl font-bold leading-[1.1] tracking-tight sm:text-5xl">
                            Modernizing local services for Barangay San Luis.</h2>
                        <p class="text-current/75 mt-6 max-w-xl text-base leading-7">
                            By enabling locals to easily apply for and monitor official documents online,
                            BarangayChain PH modernizes barangay services in Barangay San Luis, Antipolo City. The
                            platform assists in lowering wait times, preventing document forgeries, and promoting more
                            transparent, dependable, and accessible public service delivery through secure digital
                            processing, blockchain-based record protection, and QR code verification.
                        </p>
                    </div>

                    <div class="border-current/15 self-center border-b" data-reveal-group="right">
                        <div class="border-current/15 grid gap-2 border-t py-6 sm:grid-cols-[12rem_1fr] sm:gap-8">
                            <h3 class="font-bold">Online requests</h3>
                            <p class="text-current/75 text-sm leading-6">
                                Submit clearances and certificates digitally instead of queueing at Ynares Hall.
                            </p>
                        </div>

                        <div class="border-current/15 grid gap-2 border-t py-6 sm:grid-cols-[12rem_1fr] sm:gap-8">
                            <h3 class="font-bold">Application tracking</h3>
                            <p class="text-current/75 text-sm leading-6">
                                Follow the status of your request from your account with clear status updates.
                            </p>
                        </div>

                        <div class="border-current/15 grid gap-2 border-t py-6 sm:grid-cols-[12rem_1fr] sm:gap-8">
                            <h3 class="font-bold">Verifiable documents</h3>
                            <p class="text-current/75 text-sm leading-6">
                                Approved documents carry a QR code and a cryptographic hash anchored on the blockchain.
                            </p>
                        </div>
                    </div>

                </div>

                {{-- Mission & Vision --}}
                <div class="mt-12 grid gap-4 md:grid-cols-2" data-reveal-group="up">
                    <article
                        class="border-(--blue-700)/20 dark:border-(--celadon-500)/25 dark:bg-(--blue-870) dark:hover:border-(--celadon-500)/60 rounded-sm border bg-white p-6">
                        <p
                            class="text-(--blue-700) dark:text-(--celadon-500) text-xs font-bold uppercase tracking-wider">
                            Our Mission</p>
                        <p class="text-current/75 mt-3 text-sm leading-6">
                            To provide residents and barangay personnel with an accessible and reliable digital platform
                            that simplifies document processing, reduces paperwork, and strengthens the security and
                            authenticity of official barangay records.
                        </p>
                    </article>

                    <article
                        class="border-(--blue-700)/20 dark:border-(--celadon-500)/25 dark:bg-(--blue-870) dark:hover:border-(--celadon-500)/60 rounded-sm border bg-white p-6">
                        <p
                            class="text-(--blue-700) dark:text-(--celadon-500) text-xs font-bold uppercase tracking-wider">
                            Our Vision</p>
                        <p class="text-current/75 mt-3 text-sm leading-6">
                            To become a model of innovative and transparent local e-governance in Antipolo City, where
                            technology builds public trust, official documents remain verifiable, and every resident
                            receives efficient and quality public service.
                        </p>
                    </article>
                </div>

            </div>
        </section>

        {{-- Services --}}
        <section id="services"
            class="bg-(--blue-860) text-(--off-white) dark:bg-(--blue-870) dark:border-t dark:border-white/10">
            <div class="mx-auto max-w-7xl px-5 py-12 sm:px-8 sm:py-24">
                <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end" data-reveal="up">
                    <div class="max-w-2xl">
                        <p class="text-(--celadon-500) text-xs font-bold uppercase tracking-wider">e-Services
                        </p>
                        <h2 class="mt-3 text-balance text-3xl font-bold leading-[1.1] tracking-tight sm:text-5xl">
                            Choose the Services You Wish to Request.</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <p class="text-(--off-white)/75 text-sm">
                            Requires a verified Barangay San Luis resident account.
                        </p>

                        <a href="{{ route('login') }}"
                            class="text-(--celadon-500) focus-visible:outline-(--celadon-500) shrink-0 text-sm font-semibold underline-offset-4 transition hover:underline focus-visible:outline-2">
                            View More
                        </a>
                    </div>
                </div>

                <div class="mt-10 grid gap-6 md:grid-cols-3" data-reveal-group="up">
                    @foreach ([['Barangay Clearance', 'Official clearance for local employment, business permits, police/NBI clearance, and government transactions.', 'QR-verifiable', 'Valid Gov ID + Proof of San Luis Address'], ['Certificate of Residency', 'Official certification confirming your current residential address within Barangay San Luis, Antipolo City.', 'QR-verifiable', 'Valid Gov ID + Utility Bill or Lease'], ['Certificate of Indigency', 'Issued to qualified residents applying for medical, educational, burial, legal, or DSWD social assistance.', 'Free of charge', 'Valid Gov ID + Purpose Details']] as [$name, $text, $meta, $req])
                        <a href="{{ $dashboard ?? route('register') }}"
                            class="hover:border-(--celadon-500)/60 focus-visible:outline-(--celadon-500) group flex flex-col justify-between rounded-sm border border-white/15 bg-white/5 p-7 transition duration-300 hover:-translate-y-1 focus-visible:outline-2 focus-visible:outline-offset-2 motion-reduce:hover:translate-y-0">
                            <div>
                                <div class="flex items-center justify-between gap-2">
                                    <span
                                        class="text-(--celadon-500) text-xs font-bold uppercase tracking-wider">0{{ $loop->iteration }}</span>
                                    <span
                                        class="text-(--celadon-500) rounded-full bg-white/10 px-2.5 py-0.5 text-xs font-semibold">{{ $meta }}</span>
                                </div>
                                <h3 class="mt-4 text-2xl font-semibold leading-tight">{{ $name }}</h3>
                                <p class="text-(--off-white)/75 mt-3 text-sm leading-6">{{ $text }}</p>
                            </div>

                            <div class="mt-6 border-t border-white/10 pt-4">
                                <p class="text-(--off-white)/60 text-xs">
                                    <strong class="text-(--off-white)/85 font-semibold">Needed:</strong>
                                    {{ $req }}
                                </p>
                                <span
                                    class="text-(--celadon-500) mt-4 inline-flex items-center gap-2 text-sm font-semibold transition-transform group-hover:translate-x-1 motion-reduce:group-hover:translate-x-0">
                                    Request online →
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- How it works --}}
        <section id="process"
            class="bg-(--off-white) text-(--blue-700) dark:bg-(--blue-850) dark:text-(--off-white) dark:border-t dark:border-white/10">
            <div class="mx-auto max-w-7xl px-5 py-12 sm:px-8 sm:py-24">
                <div class="mx-auto max-w-2xl text-center" data-reveal="up">
                    <p class="text-(--blue-700) dark:text-(--celadon-500) text-xs font-bold uppercase tracking-wider">
                        Step-by-Step Guide</p>
                    <h2 class="mt-3 text-balance text-3xl font-bold leading-[1.1] tracking-tight sm:text-5xl">From
                        application to verified document.</h2>
                </div>

                <ol class="relative mx-auto mt-14 max-w-3xl" data-reveal-group="up">
                    <span aria-hidden="true" data-reveal="fade"
                        class="border-(--celadon-500)/60 absolute inset-y-0 left-2 border-l border-dashed md:left-1/2"></span>

                    @foreach ([['Create an account', 'Sign up with your email and basic Barangay San Luis resident profile.'], ['Verify your identity', 'Upload a clear photo of a valid government ID for one-time staff verification.'], ['Submit a request', 'Select your certificate, state your purpose, and track review progress in real time.'], ['Claim & verify anytime', 'Receive your approved certificate stamped with a unique QR code and SHA-256 ledger hash.']] as [$name, $text])
                        <li class="relative pb-12 pl-10 last:pb-0 md:grid md:grid-cols-2 md:gap-x-10 md:pl-0">
                            <span aria-hidden="true"
                                class="bg-(--celadon-500) ring-(--celadon-500)/25 absolute left-2 top-2 size-4 -translate-x-1/2 rounded-full ring-4 md:left-1/2"></span>

                            <div class="{{ $loop->even ? 'md:col-start-1 md:text-right' : 'md:col-start-2' }}">
                                <span class="text-3xl font-bold opacity-40">0{{ $loop->iteration }}</span>
                                <h3 class="text-(--blue-500) dark:text-(--celadon-500) mt-1 text-xl font-bold">
                                    {{ $name }}</h3>
                                <p class="text-current/75 mt-2 text-sm leading-6">{{ $text }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>

                <div class="mt-12 text-center" data-reveal="up">
                    <a href="{{ $dashboard ?? route('register') }}"
                        class="bg-(--blue-700) text-(--off-white) hover:bg-(--celadon-500) hover:text-(--blue-850) dark:bg-(--celadon-500) dark:text-(--blue-850) focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) inline-flex items-center rounded-sm px-5 py-3 text-sm font-semibold transition hover:-translate-y-0.5 focus-visible:outline-2 focus-visible:outline-offset-2 motion-reduce:hover:translate-y-0 dark:hover:bg-white">
                        {{ $dashboard ?? null ? 'Go to your dashboard' : 'Create free resident account' }}
                    </a>
                </div>
            </div>
        </section>

        {{-- Document verification --}}
        <section id="verify"
            class="bg-(--blue-860) text-(--off-white) dark:bg-(--blue-870) dark:border-t dark:border-white/10">
            <div class="mx-auto max-w-7xl px-5 py-12 sm:px-8 sm:py-24">
                <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                    <div data-reveal="left">
                        <p class="text-(--celadon-500) text-xs font-bold uppercase tracking-wider">Public Verification
                            Portal</p>

                        <h2 class="mt-3 text-balance text-3xl font-bold leading-[1.1] tracking-tight sm:text-5xl">
                            Check if a document is authentic.</h2>
                        <p class="text-(--off-white)/80 mt-5 max-w-prose text-base leading-7">
                            Every certificate issued through BarangayChain PH carries a cryptographic hash and QR code.
                            Employers, banks, and government agencies can verify a document immediately—no account
                            required.
                        </p>

                        <div
                            class="bg-linear-to-br to-white/3 mt-7 max-w-xl overflow-hidden rounded-sm border border-white/15 from-white/10 backdrop-blur-sm">
                            <div class="grid items-center gap-5 p-5 sm:grid-cols-[1fr_auto] sm:gap-6 sm:p-6">

                                {{-- Left: message and action --}}
                                <div>
                                    <h3 class="text-(--off-white) text-lg font-semibold leading-snug">
                                        Is your document genuine?
                                    </h3>
                                    <p class="text-(--off-white)/70 mt-1.5 max-w-xs text-sm leading-relaxed">
                                        Confirm that we issued it and that nothing has been changed since. It takes a
                                        few seconds and
                                        needs no account.
                                    </p>

                                    <a href="#"
                                        class="bg-(--celadon-500) text-(--blue-850) focus-visible:outline-(--celadon-500) group mt-4 inline-flex items-center gap-2 rounded-sm px-4 py-2.5 text-sm font-bold transition hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2">
                                        Verify a document
                                        <svg class="size-4 transition group-hover:translate-x-0.5" viewBox="0 0 20 20"
                                            fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M4 10h12M11 5l5 5-5 5" />
                                        </svg>
                                    </a>

                                    <p class="text-(--off-white)/55 mt-3 max-w-xs text-xs leading-relaxed">
                                        On a phone? Scan the QR code in the bottom corner of your document and the
                                        result opens
                                        directly.
                                    </p>
                                </div>

                                {{-- Right: document sketch showing where the QR code is --}}
                                <div class="relative mx-auto w-28 shrink-0 sm:mx-0 sm:w-32" aria-hidden="true">
                                    <svg viewBox="0 0 160 200" class="drop-shadow-[0_10px_20px_rgba(0,0,0,0.35)]"
                                        fill="none">
                                        {{-- Page --}}
                                        <rect x="6" y="4" width="148" height="192" rx="3"
                                            fill="#f4f6f3" />
                                        {{-- Heading and body lines --}}
                                        <rect x="22" y="24" width="64" height="7" rx="1.5"
                                            fill="#1f3a5f" fill-opacity=".85" />
                                        <rect x="22" y="40" width="40" height="3" rx="1.5"
                                            fill="#1f3a5f" fill-opacity=".25" />
                                        <g fill="#1f3a5f" fill-opacity=".14">
                                            <rect x="22" y="62" width="116" height="3" rx="1.5" />
                                            <rect x="22" y="72" width="108" height="3" rx="1.5" />
                                            <rect x="22" y="82" width="116" height="3" rx="1.5" />
                                            <rect x="22" y="92" width="88" height="3" rx="1.5" />
                                            <rect x="22" y="110" width="116" height="3" rx="1.5" />
                                            <rect x="22" y="120" width="100" height="3" rx="1.5" />
                                        </g>
                                        {{-- Signature line --}}
                                        <path d="M22 156c6-8 10 4 16-3s8-2 14-1" stroke="#1f3a5f" stroke-opacity=".5"
                                            stroke-width="1.5" stroke-linecap="round" />
                                        {{-- QR corner highlight --}}
                                        <rect x="104" y="146" width="40" height="40" rx="2.5"
                                            fill="#fff" stroke="#3fb892" stroke-width="2"
                                            stroke-dasharray="4 3" />
                                        <g fill="#1f3a5f">
                                            <rect x="110" y="152" width="10" height="10" />
                                            <rect x="128" y="152" width="10" height="10" />
                                            <rect x="110" y="170" width="10" height="10" />
                                            <rect x="124" y="166" width="4" height="4" />
                                            <rect x="130" y="172" width="8" height="4" />
                                            <rect x="124" y="176" width="4" height="4" />
                                            <rect x="132" y="180" width="6" height="2" />
                                        </g>
                                    </svg>

                                    {{-- Verified seal --}}
                                    <span
                                        class="bg-(--celadon-500) text-(--blue-850) ring-(--blue-850) absolute -right-2.5 -top-2.5 flex size-9 items-center justify-center rounded-full shadow-lg ring-[3px]">
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="m6 12.5 4 4 8-9" />
                                        </svg>
                                    </span>
                                </div>
                            </div>
                        </div>

                    </div>

                    <figure class="bg-(--blue-900) rounded-xl p-3 shadow-2xl ring-1 ring-white/15"
                        data-reveal="right">
                        <div
                            class="mb-2.5 flex items-center justify-between border-b border-white/10 px-2 pb-2 text-xs">
                            <span class="font-semibold">Sample Verification Output</span>
                            <span class="text-(--celadon-500) inline-flex items-center gap-1.5 font-mono text-xs">
                                <span class="bg-(--celadon-500) h-1.5 w-1.5 rounded-full"></span>
                                HASH VERIFIED
                            </span>
                        </div>
                        <img src="{{ asset('img/photos/verify-sample.png') }}"
                            alt="Sample verification result showing a valid Barangay Clearance with its permit number, reference number and applicant name"
                            width="992" height="704" loading="lazy" class="w-full rounded-lg">
                        <figcaption
                            class="text-(--off-white)/70 mt-2.5 flex flex-wrap items-center justify-between gap-2 px-2 text-xs">
                            <span>Immutable Record Proof</span>
                            <span class="text-(--off-white)/90 font-mono">SHA-256 • 0x94a7...3f8c</span>
                        </figcaption>
                    </figure>
                </div>

                {{-- Trust points --}}
                <dl class="mt-12 grid gap-y-8 border-t border-white/15 pt-8 text-center sm:grid-cols-3"
                    data-reveal-group="up">
                    <div class="sm:px-8">
                        <dt class="text-xl font-bold tracking-tight sm:text-2xl">Anti-Counterfeit QR Seal</dt>
                        <dd class="text-(--off-white)/75 mt-2 text-xs font-medium leading-5">
                            Eliminates forged signatures and altered dates on printed barangay clearances.
                        </dd>
                    </div>

                    <div class="sm:border-l sm:border-white/15 sm:px-8">
                        <dt class="text-xl font-bold tracking-tight sm:text-2xl">Blockchain Ledger Anchored</dt>
                        <dd class="text-(--off-white)/75 mt-2 text-xs font-medium leading-5">
                            Each approved permit creates an immutable audit trail verified against barangay
                            records.
                        </dd>
                    </div>

                    <div class="sm:border-l sm:border-white/15 sm:px-8">
                        <dt class="text-xl font-bold tracking-tight sm:text-2xl">Data Privacy Compliant</dt>
                        <dd class="text-(--off-white)/75 mt-2 text-xs font-medium leading-5">
                            Displays only essential verification metadata with partially masked resident
                            details.
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        {{-- Announcements (optional) --}}
        @if (filled($announcements ?? []))
            <section id="announcements" class="bg-(--blue-850) text-(--off-white) dark:border-t dark:border-white/10">
                <div class="mx-auto max-w-7xl px-5 py-12 sm:px-8 sm:py-24">
                    <div class="flex flex-wrap items-end justify-between gap-4" data-reveal="up">
                        <div class="max-w-xl">
                            <p class="text-(--celadon-500) text-xs font-bold uppercase tracking-wider">Community
                                Advisories
                            </p>
                            <h2 class="mt-3 text-balance text-3xl font-bold leading-[1.1] tracking-tight sm:text-5xl">
                                Latest from Barangay San Luis.</h2>
                        </div>
                        <a href="https://www.facebook.com/barangay.sanluis.35" target="_blank"
                            rel="noopener noreferrer"
                            class="text-(--celadon-500) focus-visible:outline-(--celadon-500) rounded-sm text-sm font-semibold underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2">
                            More on Facebook →
                        </a>
                    </div>

                    <div class="mt-10 grid gap-6 md:grid-cols-3" data-reveal-group="up">
                        @foreach (collect($announcements)->take(3) as $item)
                            <article
                                class="hover:border-(--celadon-500)/60 flex flex-col rounded-sm border border-white/15 bg-white/5 p-7">
                                @if (data_get($item, 'date'))
                                    <time class="text-(--celadon-500) text-xs font-bold uppercase tracking-wider"
                                        datetime="{{ Carbon::parse(data_get($item, 'date'))->toDateString() }}">
                                        {{ Carbon::parse(data_get($item, 'date'))->format('F j, Y') }}
                                    </time>
                                @endif
                                <h3 class="mt-3 text-xl font-semibold">{{ data_get($item, 'title') }}</h3>
                                @if ($summary = data_get($item, 'summary'))
                                    <p class="text-(--off-white)/75 mt-3 flex-1 text-sm leading-6">{{ $summary }}
                                    </p>
                                @endif
                                @if ($url = data_get($item, 'url'))
                                    <a href="{{ $url }}"
                                        class="text-(--celadon-500) focus-visible:outline-(--celadon-500) mt-5 w-fit rounded-sm text-sm font-semibold underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2">
                                        Read more
                                    </a>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- FAQ --}}
        <section id="faq"
            class="bg-(--off-white) text-(--blue-700) dark:bg-(--blue-850) dark:text-(--off-white) border-(--blue-700)/10 border-t dark:border-t dark:border-white/10">
            <div
                class="mx-auto grid max-w-7xl gap-10 px-5 py-12 sm:px-8 sm:py-24 lg:grid-cols-[0.85fr_1.15fr] lg:gap-20">
                <div class="lg:sticky lg:top-28 lg:self-start" data-reveal="left">
                    <p class="text-(--blue-700) dark:text-(--celadon-500) text-xs font-bold uppercase tracking-wider">
                        Citizen Help Desk</p>
                    <h2 class="mt-3 text-balance text-3xl font-bold leading-[1.1] tracking-tight sm:text-5xl">
                        Quick <span class="soft">answers.</span>
                    </h2>
                    <p class="text-current/75 mt-5 text-sm leading-6">
                        Need help with your application? Ask our <strong>24/7 AI Assistant</strong> in the
                        bottom-right corner or check our
                        <a href="#contact"
                            class="focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) rounded-sm font-semibold underline underline-offset-2 focus-visible:outline-2 focus-visible:outline-offset-2">office
                            contact details</a>.
                    </p>
                </div>

                <div class="divide-current/15 border-current/15 divide-y border-y" data-reveal-group="up">
                    @foreach ([
        ['How does blockchain make my barangay permit more secure?', 'When Barangay San Luis approves your certificate, the system generates a unique cryptographic hash (digital fingerprint) linked to your document. If anyone alters the text or date on the document, scanning its QR code will immediately flag it as invalid.'],
        ['What do I need before applying online?', 'You need an active email address, accurate personal details, and a clear photo of a valid government-issued ID showing your Barangay San Luis, Antipolo City address.'],
        ['Who is eligible to use BarangayChain PH?', 'The portal is exclusively built for bonafide residents of Barangay San Luis, Antipolo City, as well as third-party verifiers (employers, banks, agencies) checking issued documents.'],
        ['Do I need an account to verify a QR code?', 'No. Anyone receiving a BarangayChain PH certificate can scan the QR code or enter its reference number on this page to verify its authenticity instantly without signing in.'],
        ['How are fees and processing times handled?', 'Certificate fees (if applicable) and processing schedules follow official Barangay San Luis ordinances. Certificates of Indigency for qualified residents are issued free of charge.'],
    ] as [$question, $answer])
                        <details class="group" {{ $loop->first ? 'open' : '' }}>
                            <summary
                                class="hover:text-(--blue-500) dark:hover:text-(--celadon-500) focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) flex cursor-pointer list-none items-center justify-between gap-4 py-6 text-lg font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 [&::-webkit-details-marker]:hidden">
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
        <section id="contact"
            class="bg-(--blue-860) text-(--off-white) dark:bg-(--blue-870) dark:border-t dark:border-white/10">
            <div class="mx-auto grid max-w-7xl gap-12 px-5 pb-10 pt-12 sm:px-8 sm:pt-24 lg:grid-cols-2 lg:gap-16">
                <div class="min-w-0" data-reveal="left">
                    <p class="text-(--celadon-500) text-xs font-bold uppercase tracking-wider">Barangay Hall Directory
                    </p>
                    <h2 class="mt-3 text-balance text-3xl font-bold leading-[1.1] tracking-tight sm:text-5xl">
                        Connect with <span class="soft">Barangay San Luis.</span>
                    </h2>
                    <p class="text-(--off-white)/75 mt-4 max-w-md text-sm leading-6">
                        For in-person assistance, document claiming, or community inquiries, visit Ynares Hall or reach
                        out through our official channels.
                    </p>

                    <dl class="mt-8 space-y-5">
                        @foreach ([
        ['M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z', 'Location', 'J53X+J7G, Sitio Pinagminahan, Antipolo, 1870 Rizal', 'https://maps.app.goo.gl/FWAKAkvL37kM2vyX6'],
        ['M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z', 'Office Hours', 'Monday to Friday, 8:00 AM to 5:00 PM', null],
        ['M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z', 'Telephone', '8-8555-245', 'tel:88555245'],
        ['M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75', 'Email', 'barangaychain@gmail.com', 'mailto:barangaychain@gmail.com'],
        ['M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25', 'Official Facebook', 'Barangay San Luis Official Page', 'https://www.facebook.com/barangay.sanluis.35'],
    ] as [$icon, $name, $value, $link])
                            <div class="flex items-center gap-4">
                                <span
                                    class="bg-(--celadon-500)/15 text-(--celadon-500) ring-(--celadon-500)/30 inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full ring-1">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5"
                                        viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="{{ $icon }}" />
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <dt class="text-(--off-white)/75 text-xs font-semibold">{{ $name }}</dt>
                                    <dd class="wrap-break-word mt-0.5 text-sm font-semibold">
                                        @if ($link)
                                            <a href="{{ $link }}"
                                                @if (str_starts_with($link, 'http')) target="_blank" rel="noopener noreferrer" @endif
                                                class="hover:text-(--celadon-500) focus-visible:outline-(--celadon-500) break-all rounded-sm underline-offset-4 transition-colors hover:underline focus-visible:outline-2 focus-visible:outline-offset-2">{{ $value }}</a>
                                        @else
                                            {{ $value }}
                                        @endif
                                    </dd>
                                </div>
                            </div>
                        @endforeach
                    </dl>
                </div>

                {{-- Contact form (stretches to match the left column) --}}
                <form method="POST" action="{{ route('contact.store') }}" data-reveal="right"
                    class="bg-(--blue-860) flex h-full min-w-0 flex-col rounded-sm border border-white/10 p-6 sm:p-8">
                    @csrf
                    <h3 class="text-xl font-semibold">Send us a message</h3>
                    <p class="text-(--off-white)/70 mt-1 text-xs">We typically respond within 1 business day.</p>

                    @if (session('success'))
                        <p role="status"
                            class="mt-4 rounded-sm border border-emerald-300/30 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-100">
                            {{ session('success') }}
                        </p>
                    @endif

                    @if (session('error'))
                        <p role="alert"
                            class="mt-4 rounded-sm border border-red-300/30 bg-red-500/10 px-3 py-2 text-sm text-red-100">
                            {{ session('error') }}
                        </p>
                    @endif

                    <div class="mt-6 grid flex-1 gap-4 sm:grid-cols-2 sm:grid-rows-[auto_auto_auto_1fr]">
                        <div>
                            <x-form.input name="first_name" id="contact-first-name" label="First Name *"
                                placeholder="Juan" autocomplete="given-name" maxlength="100" required>
                                @error('first_name')
                                    <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                @enderror
                            </x-form.input>
                        </div>

                        <div>
                            <x-form.input name="last_name" id="contact-last-name" label="Last Name *"
                                placeholder="Dela Cruz" autocomplete="family-name" maxlength="100" required>
                                @error('last_name')
                                    <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                @enderror
                            </x-form.input>
                        </div>

                        <div class="sm:col-span-2">
                            <x-form.input name="email" id="contact-email" type="email" label="Email Address *"
                                placeholder="juan@example.com" autocomplete="email" maxlength="254" required>
                                @error('email')
                                    <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                @enderror
                            </x-form.input>
                        </div>

                        <div>
                            <x-form.input name="service" id="contact-service" type="select"
                                label="Topic / Service *" placeholder="Select a service..." required
                                :options="[
                                    'Barangay Clearance' => 'Barangay Clearance',
                                    'Certificate of Residency' => 'Certificate of Residency',
                                    'Certificate of Indigency' => 'Certificate of Indigency',
                                    'Document Verification' => 'Document Verification',
                                    'Other' => 'Other',
                                ]">
                                @error('service')
                                    <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                @enderror
                            </x-form.input>
                        </div>

                        <div>
                            <x-form.input name="subject" id="contact-subject" label="Subject *"
                                placeholder="Brief summary" maxlength="200" required>
                                @error('subject')
                                    <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                @enderror
                            </x-form.input>
                        </div>

                        {{-- Message grows to fill the remaining height --}}
                        <div
                            class="flex min-h-0 flex-col sm:col-span-2 [&>div]:flex [&>div]:flex-1 [&>div]:flex-col [&_.relative]:flex-1 [&_textarea]:h-full">
                            <x-form.input name="message" id="contact-message" type="textarea" label="Message *"
                                placeholder="How can Barangay San Luis help you?" rows="4" maxlength="5000"
                                required>
                                @error('message')
                                    <p class="mt-1 text-xs text-red-300">{{ $message }}</p>
                                @enderror
                            </x-form.input>
                        </div>
                    </div>

                    <button type="submit"
                        class="text-(--blue-900) hover:bg-(--celadon-500) hover:text-(--blue-850) focus-visible:outline-(--celadon-500) mt-6 w-full cursor-pointer rounded-sm bg-white px-5 py-3 text-sm font-bold transition hover:-translate-y-0.5 focus-visible:outline-2 focus-visible:outline-offset-2 motion-reduce:hover:translate-y-0">
                        Send Message
                    </button>
                </form>
            </div>

            {{-- Map card --}}
            <div class="mx-auto max-w-7xl px-5 pb-16 sm:px-8 sm:pb-24">
                <div class="relative rounded-xl border border-white/10 bg-white/5 p-2" data-reveal="zoom">
                    <iframe title="Map of Barangay San Luis, Antipolo City" loading="lazy"
                        class="h-80 w-full rounded-lg sm:h-96"
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3860.912572643502!2d121.1982!3d14.604056!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397bf14228c2a5b%3A0x7acfc6e2d3f77ee7!2sBarangay%20San%20Luis%20Main%20Hall!5e0!3m2!1sen!2sph!4v1795418170568!5m2!1sen!2sph"
                        width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"
                        referrerpolicy="strict-origin-when-cross-origin"></iframe>

                    <a href="https://www.google.com/maps/search/?api=1&query=Barangay+San+Luis+Antipolo+City+Rizal"
                        target="_blank" rel="noopener noreferrer"
                        class="bg-(--blue-900) text-(--celadon-500) hover:bg-(--celadon-500) hover:text-(--blue-850) focus-visible:outline-(--celadon-500) absolute bottom-6 left-1/2 inline-flex -translate-x-1/2 items-center gap-2 rounded-full px-5 py-2.5 text-sm font-semibold shadow-lg ring-1 ring-white/15 transition focus-visible:outline-2 focus-visible:outline-offset-2">
                        Open in Google Maps
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        </section>

    </main>

    {{-- Header menu + active-section highlight (scroll animations now live in resources/js/reveal.js) --}}
    <script>
        const header = document.getElementById('site-header');
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

        // Close the mobile menu when clicking outside the header
        document.addEventListener('click', (e) => {
            if (isOpen() && !header.contains(e.target)) {
                setMenu(false);
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && isOpen()) {
                setMenu(false);
                menuButton.focus();
            }
        });

        // Highlight the current section in the nav
        if ('IntersectionObserver' in window) {
            const spy = new IntersectionObserver(
                (entries) => entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        links.forEach((a) => a.setAttribute('aria-current', a.hash === '#' + entry.target.id));
                    }
                }), {
                    rootMargin: '-40% 0px -55% 0px'
                }
            );

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
