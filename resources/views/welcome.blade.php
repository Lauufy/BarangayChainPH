<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Barangay San Luis | Antipolo City</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet">

    <link rel="icon" type="image/png" href="{{ asset('img/branding/barangayChainPH-logo.png') }}">

    <style>
        html {
            scroll-behavior: smooth;
        }
    </style>
</head>

<body class="bg-[#020A18] font-sans text-white antialiased">

    {{-- GOVERNMENT BAR --}}
    <div class="border-b border-white/10 bg-[#09172D]">
        <div class="mx-auto max-w-7xl px-6">
            <div class="flex h-9 items-center justify-between">
                <span class="text-xs font-medium text-blue-100/70">
                    Official Barangay Website
                </span>

                <div class="hidden text-xs text-blue-100/50 sm:block">
                    Republic of the Philippines | Antipolo City, Rizal
                </div>
            </div>
        </div>
    </div>

    {{-- NAVIGATION --}}
    <header class="sticky top-0 z-50 border-b border-white/10 bg-[#020A18]/90 backdrop-blur">
        <div class="mx-auto max-w-7xl px-6">
            <div class="flex h-20 items-center justify-between gap-6">

                {{-- BRAND --}}
                <a href="{{ url('/') }}" class="flex shrink-0 items-center gap-3">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-full border-2 border-emerald-500/60 bg-[#09172D]">
                        <img src="{{ asset('img/branding/barangayChainPH-logo.png') }}" alt="Barangay San Luis Logo"
                            class="h-full w-full rounded-full object-cover">
                    </div>

                    <div class="leading-tight">
                        <p class="text-[9px] font-black uppercase tracking-[0.18em] text-emerald-400">
                            Barangay Government
                        </p>
                        <h1 class="text-lg font-black text-white">SAN LUIS</h1>
                        <p class="text-[10px] text-slate-400">Antipolo City, Rizal</p>
                    </div>
                </a>

                {{-- NAV LINKS --}}
                <nav class="hidden items-center gap-1 lg:flex">
                    <a href="#home"
                        class="px-3 py-3 text-sm font-semibold text-slate-300 transition hover:text-white">
                        Home
                    </a>
                    <a href="{{ route('staff.identity-verification.index') }}">Identity Verification</a>
                    <a href="#about"
                        class="px-3 py-3 text-sm font-semibold text-slate-300 transition hover:text-white">
                        About
                    </a>
                    <a href="#services"
                        class="px-3 py-3 text-sm font-semibold text-slate-300 transition hover:text-white">
                        Services
                    </a>
                    <a href="#announcements"
                        class="px-3 py-3 text-sm font-semibold text-slate-300 transition hover:text-white">
                        Announcements
                    </a>
                    <a href="#contact"
                        class="px-3 py-3 text-sm font-semibold text-slate-300 transition hover:text-white">
                        Contact
                    </a>
                </nav>

                {{-- LOGIN / REGISTER --}}
                <div class="flex shrink-0 items-center gap-2">
                    <a href="{{ route('login') }}"
                        class="hidden rounded-lg border border-white/15 px-4 py-2.5 text-sm font-bold text-white transition hover:border-emerald-400 hover:text-emerald-300 sm:inline-flex">
                        Login
                    </a>
                    <a href="{{ route('register') }}"
                        class="inline-flex rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-500 sm:px-5">
                        Register
                    </a>
                </div>

            </div>
        </div>
    </header>

    <main>

        {{-- HERO --}}
        <section id="home" class="relative overflow-hidden border-b border-white/10 bg-[#020A18]">
            <div class="mx-auto max-w-7xl px-6">
                <div class="grid items-center gap-12 py-16 md:py-20 lg:grid-cols-2 lg:gap-16">

                    {{-- LEFT CONTENT --}}
                    <div>
                        <div class="mb-6 flex items-center gap-3">
                            <div class="h-1 w-10 rounded-full bg-emerald-400"></div>
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-400">
                                Official Barangay Portal
                            </p>
                        </div>

                        <h2 class="max-w-3xl text-5xl font-black leading-tight tracking-tight text-white md:text-6xl">
                            Welcome to
                            <span class="block text-emerald-400">Barangay San Luis</span>
                        </h2>

                        <p class="mt-7 max-w-xl text-lg leading-8 text-blue-100/65">
                            The official online portal of Barangay San Luis, Antipolo City.
                            Access public information, barangay services, announcements,
                            and community assistance in one place.
                        </p>

                        <div class="mt-9 flex flex-wrap items-center gap-3">
                            <a href="#services"
                                class="inline-flex items-center rounded-lg bg-emerald-600 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-emerald-950/20 transition hover:bg-emerald-500">
                                Access Online Services
                            </a>

                            <a href="#announcements"
                                class="inline-flex items-center rounded-lg border border-white/15 px-6 py-3.5 text-sm font-bold text-slate-200 transition hover:border-emerald-400 hover:text-emerald-300">
                                View Announcements
                            </a>
                        </div>

                        {{-- BASIC INFORMATION --}}
                        <div class="mt-12 grid max-w-xl grid-cols-3 border-t border-white/10 pt-7">
                            <div class="pr-3">
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                                    Location
                                </p>
                                <p class="mt-2 text-sm font-bold text-white">
                                    Antipolo City
                                </p>
                            </div>

                            <div class="border-l border-white/10 px-3 sm:pl-5">
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                                    Government
                                </p>
                                <p class="mt-2 text-sm font-bold text-white">
                                    Barangay San Luis
                                </p>
                            </div>

                            <div class="border-l border-white/10 pl-3 sm:pl-5">
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                                    Service
                                </p>
                                <p class="mt-2 text-sm font-bold text-white">
                                    Online Portal
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- RIGHT GOVERNMENT CARD --}}
                    <div class="hidden lg:block">
                        <div
                            class="mx-auto max-w-md overflow-hidden rounded-lg border border-white/10 bg-[#071326] shadow-2xl shadow-black/30">
                            <div class="bg-[#09172D] px-7 py-4">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-xs font-bold uppercase tracking-widest text-white">
                                        Barangay Government
                                    </span>
                                    <span class="text-[10px] font-bold text-emerald-400">
                                        OFFICIAL PORTAL
                                    </span>
                                </div>
                            </div>

                            <div class="px-8 py-12 text-center">
                                <div
                                    class="mx-auto flex h-36 w-36 items-center justify-center rounded-full border-4 border-emerald-500/60 bg-[#09172D] p-2">
                                    <img src="{{ asset('img/branding/barangayChainPH-logo.png') }}"
                                        alt="Barangay San Luis Logo" class="h-full w-full rounded-full object-cover">
                                </div>

                                <p class="mt-8 text-[10px] font-black uppercase tracking-[0.2em] text-emerald-400">
                                    Republic of the Philippines
                                </p>
                                <h3 class="mt-2 text-2xl font-black text-white">
                                    Barangay San Luis
                                </h3>
                                <p class="mt-2 text-sm text-slate-400">
                                    Antipolo City, Rizal
                                </p>
                            </div>

                            <div class="grid grid-cols-2 border-t border-white/10">
                                <div class="border-r border-white/10 px-7 py-5">
                                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-500">
                                        Government Unit
                                    </p>
                                    <p class="mt-1 text-sm font-bold text-white">Barangay</p>
                                </div>

                                <div class="px-7 py-5">
                                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-500">
                                        Digital Access
                                    </p>
                                    <p class="mt-1 text-sm font-bold text-white">Online Services</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        {{-- PUBLIC NOTICE --}}
        <section class="border-b border-white/10 bg-[#071326]">
            <div class="mx-auto max-w-7xl px-6">
                <div class="flex flex-col gap-3 py-5 md:flex-row md:items-center">
                    <div class="flex items-center gap-3">
                        <span
                            class="rounded bg-emerald-600 px-3 py-1.5 text-[10px] font-black uppercase tracking-widest text-white">
                            Notice
                        </span>
                        <span class="text-sm font-medium text-slate-300">
                            Official barangay announcements and public information.
                        </span>
                    </div>

                    <a href="#announcements"
                        class="text-sm font-bold text-emerald-400 transition hover:text-emerald-300 md:ml-auto">
                        View Announcements →
                    </a>
                </div>
            </div>
        </section>

        {{-- ABOUT --}}
        <section id="about" class="border-b border-white/10 bg-[#020A18] py-20 md:py-24">
            <div class="mx-auto max-w-7xl px-6">
                <div class="grid gap-12 lg:grid-cols-2 lg:gap-16">

                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-400">
                            About the Barangay
                        </p>

                        <h2 class="mt-4 text-4xl font-black leading-tight text-white md:text-5xl">
                            Public service built around
                            <span class="text-emerald-400">the community.</span>
                        </h2>

                        <p class="mt-6 max-w-xl leading-7 text-blue-100/65">
                            Barangay San Luis is committed to providing accessible, efficient,
                            transparent, and citizen-centered public services.
                        </p>

                        <p class="mt-4 max-w-xl leading-7 text-blue-100/65">
                            Through digital government services, residents can access
                            information and submit selected barangay requests online.
                        </p>
                    </div>

                    <div class="grid overflow-hidden rounded-lg border border-white/10 bg-[#071326] sm:grid-cols-2">
                        <div class="border-b border-white/10 p-8 sm:border-r">
                            <p class="text-sm font-black text-emerald-400">01</p>
                            <h3 class="mt-5 text-lg font-black text-white">Good Governance</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-400">
                                Responsible, transparent, and accountable public service.
                            </p>
                        </div>

                        <div class="border-b border-white/10 p-8">
                            <p class="text-sm font-black text-emerald-400">02</p>
                            <h3 class="mt-5 text-lg font-black text-white">Citizen First</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-400">
                                Services designed around the needs of residents.
                            </p>
                        </div>

                        <div class="border-b border-white/10 p-8 sm:border-b-0 sm:border-r">
                            <p class="text-sm font-black text-emerald-400">03</p>
                            <h3 class="mt-5 text-lg font-black text-white">Digital Government</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-400">
                                Technology that improves access to public services.
                            </p>
                        </div>

                        <div class="p-8">
                            <p class="text-sm font-black text-emerald-400">04</p>
                            <h3 class="mt-5 text-lg font-black text-white">Public Service</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-400">
                                Fair, reliable, and respectful service to the community.
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        {{-- SERVICES --}}
        <section id="services" class="border-b border-white/10 bg-[#050E1F] py-20 md:py-24">
            <div class="mx-auto max-w-7xl px-6">
                <div class="max-w-2xl">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-400">
                        Citizen Services
                    </p>

                    <h2 class="mt-3 text-4xl font-black tracking-tight text-white md:text-5xl">
                        Online Barangay Services
                    </h2>

                    <p class="mt-5 leading-7 text-blue-100/65">
                        Access selected barangay services and submit requests through the
                        online government portal.
                    </p>
                </div>

                <div
                    class="mt-12 grid overflow-hidden rounded-lg border border-white/10 bg-[#071326] md:grid-cols-2 lg:grid-cols-4">

                    {{-- CLEARANCE --}}
                    <article
                        class="border-b border-white/10 p-8 transition hover:bg-[#09172D] lg:border-b-0 lg:border-r">
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2zM9 8h6M9 12h6M9 16h4" />
                            </svg>
                        </div>

                        <h3 class="mt-6 text-lg font-black text-white">Barangay Clearance</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-400">
                            Request an official barangay clearance online.
                        </p>

                        <a href="#"
                            class="mt-6 inline-block text-sm font-bold text-emerald-400 transition hover:text-emerald-300">
                            Request Service →
                        </a>
                    </article>

                    {{-- CERTIFICATES --}}
                    <article
                        class="border-b border-white/10 p-8 transition hover:bg-[#09172D] lg:border-b-0 lg:border-r">
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 3h12v18H6zM9 7h6M9 11h6M9 15h4" />
                            </svg>
                        </div>

                        <h3 class="mt-6 text-lg font-black text-white">Barangay Certificates</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-400">
                            Request official certificates and barangay documents.
                        </p>

                        <a href="#"
                            class="mt-6 inline-block text-sm font-bold text-emerald-400 transition hover:text-emerald-300">
                            Request Service →
                        </a>
                    </article>

                    {{-- RESIDENCY --}}
                    <article
                        class="border-b border-white/10 p-8 transition hover:bg-[#09172D] md:border-r lg:border-b-0">
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 11l9-7 9 7M5 10v9h14v-9M9 19v-5h6v5" />
                            </svg>
                        </div>

                        <h3 class="mt-6 text-lg font-black text-white">Certificate of Residency</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-400">
                            Obtain proof of residency from the barangay.
                        </p>

                        <a href="#"
                            class="mt-6 inline-block text-sm font-bold text-emerald-400 transition hover:text-emerald-300">
                            Request Service →
                        </a>
                    </article>

                    {{-- COMMUNITY --}}
                    <article class="p-8 transition hover:bg-[#09172D]">
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 21s-7-4.35-7-10a7 7 0 0114 0c0 5.65-7 10-7 10z" />
                            </svg>
                        </div>

                        <h3 class="mt-6 text-lg font-black text-white">Community Programs</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-400">
                            Learn about programs and services available to residents.
                        </p>

                        <a href="#contact"
                            class="mt-6 inline-block text-sm font-bold text-emerald-400 transition hover:text-emerald-300">
                            Learn More →
                        </a>
                    </article>

                </div>
            </div>
        </section>

        {{-- ANNOUNCEMENTS --}}
        <section id="announcements" class="bg-[#020A18] py-20 md:py-24">
            <div class="mx-auto max-w-7xl px-6">
                <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-400">
                            Public Information
                        </p>
                        <h2 class="mt-3 text-4xl font-black text-white md:text-5xl">
                            Latest Announcements
                        </h2>
                    </div>

                    <p class="max-w-md text-sm leading-6 text-slate-400">
                        Official announcements, public notices, community activities,
                        and important updates.
                    </p>
                </div>

                <div class="mt-12 grid gap-6 lg:grid-cols-3">
                    <article
                        class="rounded-lg border border-white/10 bg-[#071326] p-7 transition hover:border-emerald-500/50 hover:shadow-lg hover:shadow-black/30">
                        <span class="text-[10px] font-black uppercase tracking-widest text-emerald-400">
                            Official Notice
                        </span>
                        <h3 class="mt-4 text-xl font-black text-white">Barangay Updates</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-400">
                            Important announcements and official notices from Barangay San Luis.
                        </p>
                        <a href="#"
                            class="mt-6 inline-block text-sm font-bold text-emerald-400 transition hover:text-emerald-300">
                            Read More →
                        </a>
                    </article>

                    <article
                        class="rounded-lg border border-white/10 bg-[#071326] p-7 transition hover:border-emerald-500/50 hover:shadow-lg hover:shadow-black/30">
                        <span class="text-[10px] font-black uppercase tracking-widest text-emerald-400">
                            Community
                        </span>
                        <h3 class="mt-4 text-xl font-black text-white">Community Activities</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-400">
                            Information about programs, activities, and events within the community.
                        </p>
                        <a href="#"
                            class="mt-6 inline-block text-sm font-bold text-emerald-400 transition hover:text-emerald-300">
                            Read More →
                        </a>
                    </article>

                    <article
                        class="rounded-lg border border-white/10 bg-[#071326] p-7 transition hover:border-emerald-500/50 hover:shadow-lg hover:shadow-black/30">
                        <span class="text-[10px] font-black uppercase tracking-widest text-emerald-400">
                            Public Advisory
                        </span>
                        <h3 class="mt-4 text-xl font-black text-white">Public Information</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-400">
                            Important reminders and public advisories for barangay residents.
                        </p>
                        <a href="#"
                            class="mt-6 inline-block text-sm font-bold text-emerald-400 transition hover:text-emerald-300">
                            Read More →
                        </a>
                    </article>
                </div>
            </div>
        </section>

        {{-- TRANSPARENCY --}}
        <section class="border-y border-white/10 bg-[#050E1F] py-20">
            <div class="mx-auto max-w-7xl px-6">
                <div class="grid gap-12 lg:grid-cols-2 lg:items-center">

                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-400">
                            Open Government
                        </p>

                        <h2 class="mt-3 text-3xl font-black text-white md:text-4xl">
                            Transparency & Public Information
                        </h2>

                        <p class="mt-5 max-w-xl leading-7 text-blue-100/65">
                            Providing residents with access to public information, documents,
                            forms, advisories, and barangay resources.
                        </p>
                    </div>

                    <div class="grid overflow-hidden rounded-lg border border-white/10 bg-[#071326] sm:grid-cols-2">
                        <a href="#"
                            class="border-b border-white/10 p-7 transition hover:bg-[#09172D] sm:border-r">
                            <h3 class="text-sm font-black text-white">Public Documents</h3>
                            <p class="mt-2 text-xs leading-5 text-slate-400">
                                Access available public documents.
                            </p>
                        </a>

                        <a href="#" class="border-b border-white/10 p-7 transition hover:bg-[#09172D]">
                            <h3 class="text-sm font-black text-white">Public Advisories</h3>
                            <p class="mt-2 text-xs leading-5 text-slate-400">
                                View official notices and advisories.
                            </p>
                        </a>

                        <a href="#"
                            class="border-b border-white/10 p-7 transition hover:bg-[#09172D] sm:border-b-0 sm:border-r">
                            <h3 class="text-sm font-black text-white">Forms & Downloads</h3>
                            <p class="mt-2 text-xs leading-5 text-slate-400">
                                Access available forms and resources.
                            </p>
                        </a>

                        <a href="#" class="p-7 transition hover:bg-[#09172D]">
                            <h3 class="text-sm font-black text-white">Barangay Information</h3>
                            <p class="mt-2 text-xs leading-5 text-slate-400">
                                Learn more about the barangay.
                            </p>
                        </a>
                    </div>

                </div>
            </div>
        </section>

        {{-- CALL TO ACTION --}}
        <section class="border-b border-white/10 bg-[#09172D]">
            <div class="mx-auto max-w-4xl px-6 py-20 text-center">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-400">
                    Digital Public Service
                </p>

                <h2 class="mt-4 text-3xl font-black text-white md:text-4xl">
                    Access barangay services online.
                </h2>

                <p class="mx-auto mt-5 max-w-2xl leading-7 text-blue-100/65">
                    Create an account to submit requests, access available services,
                    and manage your barangay transactions online.
                </p>

                <a href="#services"
                    class="mt-8 inline-flex rounded-lg bg-emerald-600 px-8 py-3.5 text-sm font-bold text-white shadow-lg shadow-emerald-950/20 transition hover:bg-emerald-500">
                    Explore Online Services
                </a>
            </div>
        </section>

        {{-- CONTACT --}}
        <section id="contact" class="bg-[#020A18] py-20">
            <div class="mx-auto max-w-7xl px-6">

                <div class="max-w-2xl">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-400">
                        Get in Touch
                    </p>

                    <h2 class="mt-3 text-4xl font-black tracking-tight text-white md:text-5xl">
                        Contact Barangay San Luis
                    </h2>

                    <p class="mt-4 leading-7 text-blue-100/65">
                        Reach out to the barangay office for inquiries, concerns, or assistance with
                        online services.
                    </p>
                </div>

                <div class="mt-8 grid gap-8 lg:grid-cols-2 lg:items-stretch">

                    {{-- Contact Form --}}
                    <div class="overflow-hidden rounded-lg border border-white/10 bg-[#071326]">
                        <div class="bg-[#09172D] px-8 py-5">
                            <span class="text-xs font-bold uppercase tracking-widest text-white">
                                Send a Message
                            </span>
                        </div>

                        <form method="POST" action="#" class="p-8">
                            @csrf

                            <div class="grid gap-6 sm:grid-cols-2">
                                <div>
                                    <label for="first_name"
                                        class="text-xs font-medium uppercase tracking-wide text-slate-300">
                                        First Name
                                    </label>

                                    <input type="text" id="first_name" name="first_name"
                                        value="{{ old('first_name') }}" autocomplete="given-name" required
                                        class="mt-2 w-full rounded-lg border border-white/10 bg-[#0B2347] px-4 py-3.5 text-sm text-white outline-none transition placeholder:text-blue-100/50 hover:border-white/20 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/10">

                                    @error('first_name')
                                        <p class="mt-1 text-xs font-medium text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="last_name"
                                        class="text-xs font-medium uppercase tracking-wide text-slate-300">
                                        Last Name
                                    </label>

                                    <input type="text" id="last_name" name="last_name"
                                        value="{{ old('last_name') }}" autocomplete="family-name" required
                                        class="mt-2 w-full rounded-lg border border-white/10 bg-[#0B2347] px-4 py-3.5 text-sm text-white outline-none transition placeholder:text-blue-100/50 hover:border-white/20 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/10">

                                    @error('last_name')
                                        <p class="mt-1 text-xs font-medium text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="mt-6">
                                <label for="email"
                                    class="text-xs font-medium uppercase tracking-wide text-slate-300">
                                    Email Address
                                </label>

                                <input type="email" id="email" name="email" value="{{ old('email') }}"
                                    autocomplete="email" required
                                    class="mt-2 w-full rounded-lg border border-white/10 bg-[#0B2347] px-4 py-3.5 text-sm text-white outline-none transition placeholder:text-blue-100/50 hover:border-white/20 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/10">

                                @error('email')
                                    <p class="mt-1 text-xs font-medium text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mt-6">
                                <label for="contact_number"
                                    class="text-xs font-medium uppercase tracking-wide text-slate-300">
                                    Contact Number
                                </label>

                                <input type="tel" id="contact_number" name="contact_number"
                                    value="{{ old('contact_number') }}" autocomplete="tel"
                                    class="mt-2 w-full rounded-lg border border-white/10 bg-[#0B2347] px-4 py-3.5 text-sm text-white outline-none transition placeholder:text-blue-100/50 hover:border-white/20 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/10">

                                @error('contact_number')
                                    <p class="mt-1 text-xs font-medium text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mt-6">
                                <label for="subject"
                                    class="text-xs font-medium uppercase tracking-wide text-slate-300">
                                    Subject
                                </label>

                                <select id="subject" name="subject" required
                                    class="scheme-dark mt-2 w-full rounded-lg border border-white/10 bg-[#0B2347] px-4 py-3.5 text-sm text-white outline-none transition hover:border-white/20 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/10">
                                    <option value="">Select a concern</option>
                                    <option value="barangay_clearance" @selected(old('subject') === 'barangay_clearance')>Barangay Clearance
                                    </option>
                                    <option value="certificate" @selected(old('subject') === 'certificate')>Barangay Certificate
                                    </option>
                                    <option value="residency" @selected(old('subject') === 'residency')>Certificate of Residency
                                    </option>
                                    <option value="complaint" @selected(old('subject') === 'complaint')>Complaint / Report</option>
                                    <option value="general_inquiry" @selected(old('subject') === 'general_inquiry')>General Inquiry
                                    </option>
                                </select>

                                @error('subject')
                                    <p class="mt-1 text-xs font-medium text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mt-6">
                                <label for="message"
                                    class="text-xs font-medium uppercase tracking-wide text-slate-300">
                                    Message
                                </label>

                                <textarea id="message" name="message" rows="4" required
                                    class="mt-2 w-full resize-none rounded-lg border border-white/10 bg-[#0B2347] px-4 py-3.5 text-sm text-white outline-none transition placeholder:text-blue-100/50 hover:border-white/20 focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/10">{{ old('message') }}</textarea>

                                @error('message')
                                    <p class="mt-1 text-xs font-medium text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit"
                                class="mt-8 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-emerald-950/20 transition hover:bg-emerald-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-[#071326] sm:w-auto">
                                Submit Message

                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </form>
                    </div>

                    {{-- Google Maps (filter makes the map dark) --}}
                    <div
                        class="h-100 lg:min-h-125 w-full overflow-hidden rounded-lg border border-white/10 bg-[#071326] lg:h-full">
                        <iframe
                            src="https://maps.google.com/maps?q=Barangay%20San%20Luis%2C%20Antipolo%20City%2C%20Rizal&z=15&output=embed"
                            class="filter-[invert(90%)_hue-rotate(180deg)_contrast(90%)] block h-full w-full border-0"
                            allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                            title="Map location of Barangay San Luis, Antipolo City">
                        </iframe>
                    </div>

                </div>
            </div>
        </section>

        <x-govern-footer />

    </main>

    {{-- Popup messages --}}
    <x:feedbacks.toast />

</body>

</html>
