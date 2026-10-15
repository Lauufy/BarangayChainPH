<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Barangay San Luis | Antipolo City</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=bricolage-grotesque:600,700,800|public-sans:400,500,600,700&display=swap"
          rel="stylesheet">

    <link rel="icon" type="image/png" href="{{ asset('img/branding/barangayChainPH-logo.png') }}">

    <style>
        html { scroll-behavior: smooth; scroll-padding-top: 5rem; }
        body { font-family: 'Public Sans', ui-sans-serif, system-ui, sans-serif; }
        .display { font-family: 'Bricolage Grotesque', 'Public Sans', ui-sans-serif, sans-serif; letter-spacing: -0.025em; }
        .req-row:hover .req-arrow { transform: translateX(4px); }

        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            .req-row:hover .req-arrow { transform: none; }
        }
    </style>
</head>

@php
    $authed = auth()->check();

    $home = route(match (auth()->user()?->role) {
        'admin' => 'admin.dashboard',
        'staff' => 'staff.dashboard',
        'citizen' => 'citizen.dashboard',
        default => 'home',
    });

    // Where the request links go: dashboard when logged in, login otherwise.
    $target = $authed ? $home : route('login');

    $primary = 'bg-[#1D3FBB] text-white transition hover:bg-[#16318F]';
    $field = 'mt-1.5 w-full rounded-md border border-[#C9D2EA] bg-white px-4 py-3 text-sm text-[#0F1B3D] outline-none transition hover:border-[#1D3FBB]/60 focus:border-[#1D3FBB] focus:ring-4 focus:ring-[#1D3FBB]/15';
    $label = 'text-sm font-semibold text-[#0F1B3D]';

    $nav = [
        '#services' => 'Services',
        '#how' => 'How it works',
        '#announcements' => 'Announcements',
        '#about' => 'About',
        '#contact' => 'Contact',
    ];

    $requests = [
        ['Barangay Clearance', 'For employment, business, and other requirements'],
        ['Barangay Certificate', 'Official certificates and barangay documents'],
        ['Certificate of Residency', 'Proof that you live in the barangay'],
        ['Community Programs', 'Programs and services open to residents'],
    ];

    $steps = [
        ['Create your account', 'Register once with your details so the barangay can identify you.'],
        ['Choose a service', 'Pick the document you need and fill in the request form.'],
        ['Track your request', 'Check the status of your transactions from your account.'],
    ];

    $announcements = [
        ['Official notice', '#1D3FBB', 'Barangay updates', 'Important announcements and official notices from Barangay San Luis.'],
        ['Community', '#0E7A4F', 'Community activities', 'Programs, activities, and events happening in the barangay.'],
        ['Advisory', '#CE1126', 'Public information', 'Reminders and public advisories for residents.'],
    ];

    $publicInfo = [
        ['Public documents', 'Available barangay documents'],
        ['Public advisories', 'Official notices and advisories'],
        ['Forms and downloads', 'Forms and other resources'],
        ['Barangay information', 'About the barangay'],
    ];

    $values = [
        ['Good governance', 'Responsible, transparent, and accountable service.'],
        ['Citizen first', 'Services designed around residents’ needs.'],
        ['Digital access', 'Technology that makes services easier to reach.'],
        ['Respectful service', 'Fair and reliable help for every resident.'],
    ];

    // name => [label, type, autocomplete, required]
    $nameFields = [
        'first_name' => ['First name', 'text', 'given-name', true],
        'last_name' => ['Last name', 'text', 'family-name', true],
    ];

    $otherFields = [
        'email' => ['Email address', 'email', 'email', true],
        'contact_number' => ['Contact number', 'tel', 'tel', false],
    ];

    $subjects = [
        'barangay_clearance' => 'Barangay Clearance',
        'certificate' => 'Barangay Certificate',
        'residency' => 'Certificate of Residency',
        'complaint' => 'Complaint / Report',
        'general_inquiry' => 'General Inquiry',
    ];

    $h2 = 'display text-3xl font-extrabold md:text-4xl';
@endphp

<body class="bg-[#F6F8FC] text-[#0F1B3D] antialiased">

    {{-- HEADER --}}
    <header class="sticky top-0 z-50 border-b border-[#DDE3F3] bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-5">
            <x-partials.logo />

            <nav class="hidden items-center gap-7 text-sm font-semibold text-[#3B4868] lg:flex" aria-label="Main">
                @foreach ($nav as $href => $text)
                    <a href="{{ $href }}" class="hover:text-[#1D3FBB]">{{ $text }}</a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2">
                @if ($authed)
                    <a href="{{ $home }}" class="inline-flex rounded-md px-4 py-2 text-sm font-semibold {{ $primary }}">
                        Continue
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="hidden rounded-md px-4 py-2 text-sm font-semibold text-[#1D3FBB] hover:bg-[#E6ECFA] sm:inline-flex">
                        Log in
                    </a>
                    <a href="{{ route('register') }}" class="inline-flex rounded-md px-4 py-2 text-sm font-semibold {{ $primary }}">
                        Create account
                    </a>
                @endif

                {{-- Mobile menu --}}
                <details class="relative lg:hidden">
                    <summary class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-md border border-[#C9D2EA] bg-white"
                             aria-label="Open menu">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                    </summary>

                    <div class="absolute right-0 mt-2 w-56 rounded-lg border border-[#DDE3F3] bg-white p-2 text-sm font-semibold shadow-xl">
                        @foreach ($nav as $href => $text)
                            <a href="{{ $href }}" class="block rounded-md px-3 py-2.5 hover:bg-[#E6ECFA]">{{ $text }}</a>
                        @endforeach

                        <a href="{{ $target }}"
                           class="mt-1 block rounded-md border-t border-[#DDE3F3] px-3 py-2.5 text-[#1D3FBB]">
                            {{ $authed ? 'Continue' : 'Log in' }}
                        </a>
                    </div>
                </details>
            </div>
        </div>
    </header>

    <main>

        {{-- HERO --}}
        <section id="home" class="relative overflow-hidden">
            <div class="pointer-events-none absolute -left-24 -top-24 h-72 w-72 rounded-full bg-[#FFC933]/40 blur-sm" aria-hidden="true"></div>

            <div class="relative mx-auto grid max-w-6xl items-center gap-12 px-5 py-14 md:py-20 lg:grid-cols-[1.1fr_1fr] lg:gap-16">
                <div>
                    <p class="inline-flex items-center gap-2 rounded-full bg-white px-3.5 py-1.5 text-sm font-semibold text-[#3B4868] ring-1 ring-[#DDE3F3]">
                        <span class="h-2 w-2 rounded-full bg-[#CE1126]"></span>
                        Official website of Barangay San Luis
                    </p>

                    <h1 class="display mt-6 text-5xl font-extrabold leading-[1.02] md:text-6xl lg:text-7xl">
                        Barangay requests, without the line.
                    </h1>

                    <p class="mt-6 max-w-lg text-lg leading-8 text-[#3B4868]">
                        Request clearances and certificates, read public notices, and reach the
                        barangay office from your phone.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ $authed ? $home : route('register') }}"
                           class="inline-flex rounded-md px-6 py-3.5 text-sm font-bold {{ $primary }}">
                            {{ $authed ? 'Continue to dashboard' : 'Create an account' }}
                        </a>
                        <a href="#announcements"
                           class="inline-flex rounded-md border border-[#B9C4E4] bg-white px-6 py-3.5 text-sm font-bold text-[#0F1B3D] transition hover:border-[#1D3FBB]">
                            Read announcements
                        </a>
                    </div>
                </div>

                {{-- REQUEST PICKER --}}
                <div id="services" class="relative">
                    <div class="absolute inset-0 translate-x-3 translate-y-3 rounded-2xl bg-[#FFC933]" aria-hidden="true"></div>

                    <div class="relative overflow-hidden rounded-2xl bg-[#1D3FBB] text-white">
                        <div class="px-7 pb-3 pt-7">
                            <h2 class="display text-2xl font-bold">What do you need today?</h2>
                            <p class="mt-1 text-sm text-blue-100">
                                {{ $authed ? 'Continue to your dashboard to start a request.' : 'Log in to start a request.' }}
                            </p>
                        </div>

                        <ul class="divide-y divide-white/15 px-3 pb-3">
                            @foreach ($requests as [$title, $desc])
                                <li>
                                    <a href="{{ $target }}"
                                       class="req-row flex items-center justify-between gap-4 rounded-lg px-4 py-4 transition hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-[#FFC933]">
                                        <span>
                                            <span class="block text-base font-bold">{{ $title }}</span>
                                            <span class="mt-0.5 block text-sm text-blue-100">{{ $desc }}</span>
                                        </span>
                                        <svg class="req-arrow h-5 w-5 shrink-0 text-[#FFC933] transition-transform"
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 12h14m-6-6l6 6-6 6" />
                                        </svg>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        {{-- HOW IT WORKS --}}
        <section id="how" class="border-y border-[#DDE3F3] bg-white py-16 md:py-20">
            <div class="mx-auto max-w-6xl px-5">
                <h2 class="{{ $h2 }} max-w-xl">Three steps from account to request.</h2>

                <ol class="mt-10 grid gap-8 md:grid-cols-3">
                    @foreach ($steps as [$title, $desc])
                        <li class="flex gap-4">
                            <span class="display flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#FFC933] text-lg font-extrabold">
                                {{ $loop->iteration }}
                            </span>
                            <div>
                                <h3 class="display text-xl font-bold">{{ $title }}</h3>
                                <p class="mt-1.5 leading-7 text-[#3B4868]">{{ $desc }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        {{-- ANNOUNCEMENTS --}}
        <section id="announcements" class="py-16 md:py-24">
            <div class="mx-auto max-w-6xl px-5">
                <div class="grid gap-10 lg:grid-cols-[1fr_2fr]">
                    <div>
                        <h2 class="{{ $h2 }}">Announcements</h2>
                        <p class="mt-4 max-w-sm leading-7 text-[#3B4868]">
                            Notices, advisories, and community activities from the barangay office.
                        </p>
                    </div>

                    <div class="divide-y divide-[#DDE3F3] rounded-xl border border-[#DDE3F3] bg-white">
                        @foreach ($announcements as [$tag, $color, $title, $desc])
                            <a href="#" class="group grid gap-2 p-6 transition hover:bg-[#F6F8FC] sm:grid-cols-[9rem_1fr] sm:gap-6">
                                <span class="text-sm font-bold" style="color: {{ $color }}">{{ $tag }}</span>
                                <span>
                                    <span class="display block text-xl font-bold group-hover:text-[#1D3FBB]">{{ $title }}</span>
                                    <span class="mt-1 block leading-7 text-[#3B4868]">{{ $desc }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- TRANSPARENCY --}}
        <section class="bg-[#0F1B3D] py-16 text-white md:py-20">
            <div class="mx-auto grid max-w-6xl gap-10 px-5 lg:grid-cols-2 lg:items-center">
                <div>
                    <h2 class="{{ $h2 }}">Public information, open to everyone.</h2>
                    <p class="mt-4 max-w-md leading-7 text-blue-100/80">
                        Find documents, forms, and advisories without needing an account.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($publicInfo as [$title, $desc])
                        <a href="#" class="rounded-lg border border-white/15 p-5 transition hover:border-[#FFC933] hover:bg-white/5">
                            <span class="display block text-lg font-bold">{{ $title }}</span>
                            <span class="mt-1 block text-sm text-blue-100/70">{{ $desc }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ABOUT --}}
        <section id="about" class="py-16 md:py-24">
            <div class="mx-auto grid max-w-6xl gap-10 px-5 lg:grid-cols-2 lg:gap-16">
                <div>
                    <h2 class="{{ $h2 }}">Public service built around the community.</h2>
                    <p class="mt-5 max-w-lg leading-7 text-[#3B4868]">
                        Barangay San Luis provides accessible, efficient, and transparent public services.
                        Through this portal, residents can find information and submit selected requests online.
                    </p>
                </div>

                <dl class="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                    @foreach ($values as [$title, $desc])
                        <div class="border-t-2 border-[#FFC933] pt-4">
                            <dt class="display text-lg font-bold">{{ $title }}</dt>
                            <dd class="mt-1 leading-7 text-[#3B4868]">{{ $desc }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>

        {{-- CONTACT --}}
        <section id="contact" class="border-t border-[#DDE3F3] bg-white py-16 md:py-24">
            <div class="mx-auto max-w-6xl px-5">
                <div class="max-w-2xl">
                    <h2 class="{{ $h2 }}">Contact the barangay office</h2>
                    <p class="mt-4 leading-7 text-[#3B4868]">
                        Send a question, concern, or report. For requests that need an account, you can also
                        <a href="{{ route('register') }}" class="font-semibold text-[#1D3FBB] underline underline-offset-4">create one here</a>.
                    </p>
                </div>

                <div class="mt-10 grid gap-8 lg:grid-cols-2 lg:items-stretch">
                    <form method="POST" action="#" class="rounded-xl border border-[#DDE3F3] bg-[#F6F8FC] p-6 sm:p-8">
                        @csrf

                        <div class="grid gap-5 sm:grid-cols-2">
                            @foreach ($nameFields as $name => [$text, $type, $autocomplete, $required])
                                <div>
                                    <label for="{{ $name }}" class="{{ $label }}">{{ $text }}</label>
                                    <input type="{{ $type }}" id="{{ $name }}" name="{{ $name }}" value="{{ old($name) }}"
                                           autocomplete="{{ $autocomplete }}" required class="{{ $field }}">
                                    @error($name)
                                        <p class="mt-1 text-xs font-medium text-[#CE1126]">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>

                        @foreach ($otherFields as $name => [$text, $type, $autocomplete, $required])
                            <div class="mt-5">
                                <label for="{{ $name }}" class="{{ $label }}">
                                    {{ $text }}
                                    @unless ($required)
                                        <span class="font-normal text-[#5A6788]">(optional)</span>
                                    @endunless
                                </label>
                                <input type="{{ $type }}" id="{{ $name }}" name="{{ $name }}" value="{{ old($name) }}"
                                       autocomplete="{{ $autocomplete }}" @required($required) class="{{ $field }}">
                                @error($name)
                                    <p class="mt-1 text-xs font-medium text-[#CE1126]">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach

                        <div class="mt-5">
                            <label for="subject" class="{{ $label }}">What is this about?</label>
                            <select id="subject" name="subject" required class="{{ $field }}">
                                <option value="">Select a concern</option>
                                @foreach ($subjects as $value => $text)
                                    <option value="{{ $value }}" @selected(old('subject') === $value)>{{ $text }}</option>
                                @endforeach
                            </select>
                            @error('subject')
                                <p class="mt-1 text-xs font-medium text-[#CE1126]">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mt-5">
                            <label for="message" class="{{ $label }}">Message</label>
                            <textarea id="message" name="message" rows="4" required class="{{ $field }} resize-none">{{ old('message') }}</textarea>
                            @error('message')
                                <p class="mt-1 text-xs font-medium text-[#CE1126]">{{ $message }}</p>
                            @enderror
                        </div>

                        <button class="mt-7 inline-flex w-full items-center justify-center rounded-md px-6 py-3.5 text-sm font-bold focus:outline-none focus-visible:ring-4 focus-visible:ring-[#1D3FBB]/30 sm:w-auto {{ $primary }}">
                            Send message
                        </button>
                    </form>

                    <div class="h-96 w-full overflow-hidden rounded-xl border border-[#DDE3F3] lg:h-full lg:min-h-128">
                        <iframe
                            src="https://maps.google.com/maps?q=Barangay%20San%20Luis%2C%20Antipolo%20City%2C%20Rizal&z=15&output=embed"
                            class="block h-full w-full border-0" allowfullscreen loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            title="Map location of Barangay San Luis, Antipolo City"></iframe>
                    </div>
                </div>
            </div>
        </section>

        <x-govern-footer />
    </main>

    <x:feedbacks.toast />
</body>

</html>