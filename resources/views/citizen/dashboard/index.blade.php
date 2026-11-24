@extends('layouts.citizen')

@section('title', 'Citizen Dashboard')
@section('flush', true) {{-- tells layouts.citizen to skip its main padding --}}

@section('content')
    @php
        /* ------------------------------------------------------------------
         | Data
         * ----------------------------------------------------------------- */
        $user = auth()->user();
        $profile = $user->citizenProfile;
        $firstName = rtrim((string) str($user->name)->before(' '), '.'); // "Prof. Carlo" -> "Prof"
        $identity = $user->identityStatus();
        $verified = $identity === 'verified';

        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

        // Identity: [badge text, badge classes, message]
        [$statusText, $statusBadge, $statusMessage] = match ($identity) {
            'verified' => [
                'Verified Citizen',
                'bg-(--green-600) text-(--white)',
                'Your identity is verified. You have full access to all online barangay services.',
            ],
            'pending' => [
                'Pending Review',
                'bg-(--amber-500) text-(--gray-900)',
                'Barangay staff are currently reviewing your submitted ID and details.',
            ],
            'rejected' => [
                'Action Required',
                'bg-(--red-600) text-(--white)',
                'Your identity verification was not approved. Please review the remarks.',
            ],
            default => [
                'Unverified',
                'bg-(--gray-600) text-(--white)',
                'Verify your identity to unlock online certificate and permit requests.',
            ],
        };

        // Application status: [badge classes, dot class, label]
        $applicationStatus = fn(string $status) => match ($status) {
            'approved' => [
                'bg-(--green-600)/15 text-(--green-600) dark:bg-(--green-600) dark:text-(--white)',
                'bg-(--green-600)',
                'Approved',
            ],
            'rejected' => [
                'bg-(--red-600)/15 text-(--red-600) dark:bg-(--red-600) dark:text-(--white)',
                'bg-(--red-600)',
                'Rejected',
            ],
            'processing' => [
                'bg-(--amber-500)/20 text-(--gray-900) dark:bg-(--amber-500)',
                'bg-(--amber-500)',
                'Processing',
            ],
            'cancelled' => [
                'bg-(--gray-600)/15 text-(--gray-600) dark:bg-(--gray-600) dark:text-(--white)',
                'bg-(--gray-600)',
                'Cancelled',
            ],
            default => ['bg-(--amber-500)/20 text-(--gray-900) dark:bg-(--amber-500)', 'bg-(--amber-500)', 'Pending'],
        };

        // Shortcuts: [title, description, href, icon path]
        $actions = [
            [
                'Request a service',
                'Submit a new certificate or clearance request.',
                route('citizen.services'),
                'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
            ],
            [
                'My applications',
                'Track real-time progress of your requests.',
                route('citizen.applications'),
                'M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0118 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3l1.5 1.5 3-3.75',
            ],
            [
                'My documents',
                'Download approved & issued digital files.',
                route('citizen.documents'),
                'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z',
            ],
        ];

        $services = $services ?? [
            [
                'name' => 'Barangay clearance',
                'description' => 'For employment, applications, and other official transactions.',
            ],
            [
                'name' => 'Certificate of residency',
                'description' => 'Official proof of residence in Barangay San Luis.',
            ],
            [
                'name' => 'Certificate of indigency',
                'description' => 'For residents applying for medical, educational, or financial assistance.',
            ],
        ];

        // Profile details: [label, value]
        $details = [
            ['Phone number', $user->phone],
            ['Date of birth', $profile?->date_of_birth?->format('M j, Y')],
            ['Residential address', $profile?->full_address],
        ];
        $missing = collect($details)->filter(fn($row) => blank($row[1]))->count();
        $percent = (int) round(((count($details) - $missing) / count($details)) * 100);

        // Account readiness steps: [title, text, done?, href]
        $steps = [
            [
                'Verify your identity',
                'Upload a valid government ID for verification.',
                $verified,
                route('citizen.identity-verification'),
            ],
            [
                'Complete your profile',
                'Add your phone number, birth date, and address.',
                $missing === 0,
                route('citizen.profile'),
            ],
            [
                'Request a service',
                'Select a certificate and submit your application.',
                false,
                route('citizen.services'),
            ],
        ];
        $ready = $verified && $missing === 0;
        $doneCount = (int) $verified + (int) ($missing === 0);

        $stats = $stats ?? [];
        $recentRequests = $recentRequests ?? collect();
        $announcements = collect($announcements ?? []);
        $hasImportant = $announcements->contains(fn($a) => data_get($a, 'important'));

        // Status strip: identity + profile, then up to two counts from $stats
        $cells = array_merge(
            [['Identity Status', $statusText], ['Profile Completion', $percent . '%']],
            collect($stats)->take(2)->map(fn($count, $name) => [$name, $count])->values()->all(),
        );

        // Contacts: [icon path, label, value, link]
        $contacts = [
            [
                'M15 10.5a3 3 0 11-6 0 3 3 0 016 0zM19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z',
                'Location',
                'J53X+J7G, Sitio Pinagminahan, Antipolo, 1870 Rizal',
                'https://maps.app.goo.gl/FWAKAkvL37kM2vyX6',
            ],
            [
                'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
                'Office hours',
                'Monday to Friday, 8:00 AM – 5:00 PM',
                null,
            ],
            [
                'M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z',
                'Telephone',
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

        // Small right arrow icon
        $arrow = fn(string $class = 'h-4 w-4') => '<svg class="' .
            $class .
            '" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>';

        /* ------------------------------------------------------------------
         | Shared class strings
         * ----------------------------------------------------------------- */
        // Focus rings (page theme / always-dark sections)
        $focus =
            'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500)';
        $focusDark = 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--celadon-500)';

        // Buttons and links
        $btnBase =
            'inline-flex min-h-11 items-center justify-center gap-2 rounded-sm px-5 py-2.5 text-sm transition hover:-translate-y-0.5 motion-reduce:hover:translate-y-0';
        $btn = "{$btnBase} {$focus} bg-(--blue-700) font-semibold text-(--off-white) shadow-xs hover:bg-(--celadon-500) hover:text-(--blue-850) dark:bg-(--celadon-500) dark:text-(--blue-850) dark:hover:bg-white";
        $btnDark = "{$btnBase} {$focusDark} bg-(--celadon-500) font-bold text-(--blue-850) shadow-md hover:bg-white";
        $btnGhost = "{$btnBase} {$focusDark} border border-white/25 bg-white/10 font-semibold text-(--off-white) backdrop-blur-sm hover:border-white/40 hover:bg-white/15";

        $linkBase =
            'inline-flex min-h-9 w-fit items-center gap-1.5 rounded-sm text-sm font-semibold underline underline-offset-4 transition-colors';
        $textLink = "{$linkBase} {$focus} text-(--blue-700) decoration-(--blue-700)/40 hover:text-(--blue-500) hover:decoration-current dark:text-(--celadon-500) dark:decoration-(--celadon-500)/40 dark:hover:text-white";
        $textLinkDark = "{$linkBase} {$focusDark} text-(--celadon-500) decoration-(--celadon-500)/40 hover:text-white hover:decoration-current";

        // Typography
        $muted = 'text-current/70';
        $eyebrow = 'text-xs font-bold uppercase tracking-widest text-(--blue-700) dark:text-(--celadon-500)';
        $eyebrowDark = 'text-xs font-bold uppercase tracking-widest text-(--celadon-500)';
        $h2 = 'mt-2 text-balance text-3xl font-bold leading-[1.12] tracking-tight sm:text-4xl lg:text-5xl';
        $h2s = 'mt-2 text-balance text-2xl font-bold leading-[1.15] tracking-tight sm:text-3xl';

        // Surfaces
        $card = 'rounded-sm border border-(--blue-700)/15 bg-white shadow-xs dark:border-white/10 dark:bg-(--blue-870)';
        $cardDark = 'rounded-sm border border-white/15 bg-white/[0.04] backdrop-blur-xs';
        $panelDark = 'rounded-sm border bg-(--blue-870)/85 p-6 shadow-xl backdrop-blur-md sm:p-7';
        $notice =
            'rounded-sm border border-(--amber-500)/50 bg-[color-mix(in_srgb,var(--amber-500)_14%,transparent)] p-4';
        $rowHover = 'transition-colors hover:bg-(--blue-700)/[0.04] dark:hover:bg-white/[0.04]';

        // Section backgrounds
        $light =
            'bg-(--off-white) text-(--blue-700) dark:border-t dark:border-white/10 dark:bg-(--blue-850) dark:text-(--off-white)';
        $deep = 'bg-(--blue-860) text-(--off-white) dark:border-t dark:border-white/10 dark:bg-(--blue-870)';

        // Layout
        $inner = 'mx-auto max-w-7xl px-5 py-12 sm:px-8 sm:py-20';
        $head = 'flex flex-col gap-4 md:flex-row md:items-end md:justify-between';
    @endphp

    <div class="min-w-0 overflow-x-clip">

        {{-- ========== Hero ========== --}}
        <section class="bg-(--blue-850) text-(--off-white) relative isolate overflow-hidden">
            <x-partials.blueprint base="bg-(--celadon-500)" src="{{ asset('img/photos/ynares-hall-front.jpg') }}"
                pos="object-center lg:object-right" class="absolute inset-0 -z-20" />
            <div aria-hidden="true"
                class="bg-linear-to-b from-(--blue-850) via-(--blue-850)/90 to-(--blue-850)/75 lg:bg-linear-to-r lg:from-(--blue-850) lg:via-(--blue-850)/85 lg:to-(--blue-850)/40 absolute inset-0 -z-10 lg:from-35% lg:via-60%">
            </div>

            <div class="mx-auto max-w-7xl px-5 py-12 sm:px-8 sm:py-16 lg:py-20">
                <div class="grid items-center gap-10 lg:grid-cols-[1.25fr_0.9fr] lg:gap-12">

                    {{-- Greeting and primary actions --}}
                    <div class="relative z-10 min-w-0">
                        <div
                            class="inline-flex items-center gap-2.5 rounded-full border border-white/15 bg-white/5 px-3 py-1 text-xs backdrop-blur-sm">
                            <span class="text-(--off-white)/80 font-medium">Citizen Portal</span>
                            <span aria-hidden="true" class="text-white/30">•</span>
                            <span
                                class="{{ $statusBadge }} rounded-full px-2 py-0.5 text-[11px] font-bold tracking-wide">{{ $statusText }}</span>
                        </div>

                        <h1
                            class="mt-5 text-balance text-4xl font-bold leading-[1.08] tracking-tight sm:text-5xl xl:text-[3.5rem]">
                            {{ $greeting }}, <span class="text-(--celadon-500)">{{ $firstName }}.</span>
                        </h1>

                        <p class="text-(--off-white)/80 mt-4 max-w-xl text-base leading-7">
                            Request official Barangay San Luis certificates, monitor your application progress, and
                            manage your digital citizen records.
                        </p>

                        <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                            <a href="{{ route('citizen.services') }}" class="{{ $btnDark }} w-full sm:w-auto">
                                Request a service {!! $arrow() !!}
                            </a>
                            <a href="{{ route('citizen.applications') }}" class="{{ $btnGhost }} w-full sm:w-auto">
                                My applications
                            </a>
                        </div>
                    </div>

                    {{-- Account readiness (or verified summary) --}}
                    <div class="relative z-10 min-w-0">
                        @unless ($ready)
                            <div id="getting-started" class="{{ $panelDark }} border-white/20">
                                <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-4">
                                    <div>
                                        <p class="{{ $eyebrowDark }}">Account Readiness</p>
                                        <h2 class="mt-1 text-lg font-bold">Complete before you apply</h2>
                                    </div>
                                    <span
                                        class="text-(--celadon-500) rounded-full bg-white/10 px-3 py-1 text-xs font-bold tabular-nums">{{ $doneCount }}/2
                                        Done</span>
                                </div>

                                <ol class="mt-5 space-y-4">
                                    @foreach ($steps as [$name, $text, $done, $href])
                                        <li class="flex items-start gap-3.5">
                                            @if ($done)
                                                <span
                                                    class="bg-(--green-600) mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full text-white">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor"
                                                        stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M4.5 12.75l6 6 9-13.5" />
                                                    </svg>
                                                </span>
                                            @else
                                                <span
                                                    class="border-(--celadon-500)/60 bg-(--celadon-500)/10 text-(--celadon-500) mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full border text-xs font-bold">{{ $loop->iteration }}</span>
                                            @endif

                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                    <h3
                                                        class="{{ $done ? 'text-(--off-white)/60 line-through' : '' }} text-sm font-bold">
                                                        {{ $name }}</h3>
                                                    @unless ($done)
                                                        <a href="{{ $href }}" class="{{ $textLinkDark }} text-xs">Complete
                                                            {!! $arrow('h-3.5 w-3.5') !!}</a>
                                                    @endunless
                                                </div>
                                                <p class="text-(--off-white)/70 mt-0.5 text-xs leading-5">{{ $text }}
                                                </p>
                                            </div>
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        @else
                            <div class="{{ $panelDark }} border-(--celadon-500)/30">
                                <div class="flex items-start gap-3.5">
                                    <span
                                        class="bg-(--green-600)/20 text-(--celadon-500) ring-(--celadon-500)/40 flex size-10 shrink-0 items-center justify-center rounded-full ring-1">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </span>
                                    <div>
                                        <p class="{{ $eyebrowDark }}">Account Ready</p>
                                        <h2 class="mt-1 text-lg font-bold">Verified &amp; Eligible</h2>
                                        <p class="text-(--off-white)/75 mt-1.5 text-xs leading-5">{{ $statusMessage }}</p>
                                        <a href="{{ route('citizen.documents') }}" class="{{ $textLinkDark }} mt-3 text-xs">
                                            View issued documents {!! $arrow('h-3.5 w-3.5') !!}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endunless
                    </div>

                </div>
            </div>
        </section>

        {{-- ========== Status strip ========== --}}
        @include('citizen.dashboard.partials.status-strip')

        {{-- ========== Recent applications + shortcuts ========== --}}
        <section aria-labelledby="recent-requests-title" class="{{ $light }}">
            <div class="mx-auto grid max-w-7xl gap-10 px-5 py-12 sm:px-8 sm:py-20 lg:grid-cols-[1.4fr_1fr] lg:gap-14">

                {{-- Recent applications --}}
                <div class="min-w-0">
                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <p class="{{ $eyebrow }}">Your activity</p>
                            <h2 id="recent-requests-title" class="{{ $h2s }}">Recent applications</h2>
                        </div>
                        <a href="{{ route('citizen.applications') }}" class="{{ $textLink }} shrink-0">View all</a>
                    </div>

                    @if ($recentRequests->isNotEmpty())
                        <div
                            class="{{ $card }} divide-(--blue-700)/10 mt-6 divide-y overflow-hidden dark:divide-white/10">
                            @foreach ($recentRequests as $application)
                                @php [$badgeClass, $dotClass, $statusLabel] = $applicationStatus($application->status); @endphp
                                <a href="{{ route('citizen.applications.show', $application) }}"
                                    class="{{ $rowHover }} {{ $focus }} group flex flex-wrap items-center justify-between gap-4 px-5 py-4 sm:px-6">
                                    <span class="min-w-0">
                                        <span
                                            class="block font-bold group-hover:underline">{{ $application->serviceTitle() }}</span>
                                        <span class="{{ $muted }} mt-1 flex flex-wrap items-center gap-x-2 text-xs">
                                            <span class="font-mono font-medium">{{ $application->reference_number }}</span>
                                            <span aria-hidden="true">•</span>
                                            <span>Submitted {{ $application->created_at->format('M j, Y') }}</span>
                                        </span>
                                    </span>
                                    <span class="flex items-center gap-3">
                                        <span
                                            class="{{ $badgeClass }} inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold">
                                            <span class="{{ $dotClass }} size-1.5 rounded-full"></span>
                                            {{ $statusLabel }}
                                        </span>
                                        {!! $arrow('h-4 w-4 opacity-40 transition-transform group-hover:translate-x-0.5 group-hover:opacity-100') !!}
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="{{ $card }} mt-6 flex flex-col items-center p-10 text-center">
                            <span
                                class="bg-(--blue-700)/10 text-(--blue-700) dark:bg-(--celadon-500)/15 dark:text-(--celadon-500) flex size-12 items-center justify-center rounded-full">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5"
                                    viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                            </span>
                            <p class="mt-4 text-base font-bold">No applications yet</p>
                            <p class="{{ $muted }} mt-1 max-w-sm text-sm">Need a Barangay Clearance or Certificate
                                of Residency? Submit your first request online.</p>
                            <a href="{{ route('citizen.services') }}" class="{{ $btn }} mt-6">Request a
                                service</a>
                        </div>
                    @endif
                </div>

                {{-- Shortcuts --}}
                <div class="min-w-0">
                    <p class="{{ $eyebrow }}">Quick access</p>
                    <h2 class="{{ $h2s }}">Shortcuts</h2>

                    <div class="mt-6 space-y-3">
                        @foreach ($actions as [$name, $text, $href, $icon])
                            <a href="{{ $href }}"
                                class="{{ $card }} {{ $rowHover }} {{ $focus }} hover:border-(--blue-700)/40 dark:hover:border-(--celadon-500)/50 group flex items-center gap-4 p-4 transition-all">
                                <span
                                    class="bg-(--blue-700)/10 text-(--blue-700) group-hover:bg-(--blue-700) dark:bg-(--celadon-500)/15 dark:text-(--celadon-500) dark:group-hover:bg-(--celadon-500) dark:group-hover:text-(--blue-850) grid size-11 shrink-0 place-items-center rounded-sm transition-colors group-hover:text-white">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75"
                                        viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                                    </svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-bold">{{ $name }}</span>
                                    <span class="{{ $muted }} mt-0.5 block text-xs">{{ $text }}</span>
                                </span>
                                {!! $arrow(
                                    'h-4 w-4 shrink-0 opacity-50 transition-transform group-hover:translate-x-1 group-hover:opacity-100 motion-reduce:group-hover:translate-x-0',
                                ) !!}
                            </a>
                        @endforeach
                    </div>
                </div>

            </div>
        </section>

        {{-- ========== Services ========== --}}
        <section id="services" aria-labelledby="services-title" class="{{ $deep }}">
            <div class="{{ $inner }}">
                <div class="{{ $head }}">
                    <div class="max-w-2xl">
                        <p class="{{ $eyebrowDark }}">e-Services</p>
                        <h2 id="services-title" class="{{ $h2 }}">Request a barangay document.</h2>
                    </div>
                    <a href="{{ route('citizen.services') }}" class="{{ $textLinkDark }} shrink-0">Browse all
                        services</a>
                </div>

                @unless ($verified)
                    <div class="{{ $notice }} mt-8 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                        <div class="flex items-center gap-3">
                            <svg class="text-(--amber-500) h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                                stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                            <p class="text-sm font-medium">Identity verification is required before submitting a
                                certificate request.</p>
                        </div>
                        <a href="{{ route('citizen.identity-verification') }}" class="{{ $textLinkDark }} shrink-0">Verify
                            now {!! $arrow('h-3.5 w-3.5') !!}</a>
                    </div>
                @endunless

                <div class="mt-8 grid gap-6 md:grid-cols-3">
                    @foreach ($services as $service)
                        @php $requirements = data_get($service, 'requirements', []); @endphp
                        <a href="{{ route('citizen.services') }}"
                            class="{{ $cardDark }} {{ $focusDark }} hover:border-(--celadon-500)/70 group relative flex flex-col justify-between overflow-hidden p-7 transition duration-300 hover:-translate-y-1 hover:bg-white/[0.07] motion-reduce:hover:translate-y-0">
                            <span aria-hidden="true"
                                class="bg-(--celadon-500) absolute inset-x-0 top-0 h-1 scale-x-0 transition-transform duration-300 group-hover:scale-x-100"></span>

                            <div>
                                <div class="flex items-center justify-between">
                                    <span class="text-(--celadon-500) font-mono text-xs font-bold tracking-wider">SERVICE
                                        {{ sprintf('%02d', $loop->iteration) }}</span>
                                    <span
                                        class="text-(--off-white)/70 rounded-full border border-white/15 px-2.5 py-0.5 text-[11px] font-medium">Online</span>
                                </div>

                                <h3 class="mt-4 text-xl font-bold leading-snug sm:text-2xl">
                                    {{ data_get($service, 'name') }}</h3>
                                <p class="text-(--off-white)/75 mt-2.5 text-sm leading-6">
                                    {{ data_get($service, 'description') }}</p>
                            </div>

                            <div class="mt-6 border-t border-white/10 pt-4">
                                @if (is_array($requirements) && $requirements)
                                    <p class="text-(--off-white)/65 mb-3 text-xs leading-5">
                                        <strong class="text-(--off-white)/90 font-semibold">Requirements:</strong>
                                        {{ implode(', ', $requirements) }}
                                    </p>
                                @endif
                                <span
                                    class="text-(--celadon-500) inline-flex items-center gap-2 text-sm font-bold transition-transform group-hover:translate-x-1 motion-reduce:group-hover:translate-x-0">
                                    Start request {!! $arrow() !!}
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>

                <p class="text-(--off-white)/55 mt-6 text-xs leading-5">
                    * Requirements, applicable fees, and processing times are subject to verification by the Barangay
                    San Luis office.
                </p>
            </div>
        </section>

        {{-- ========== Announcements ========== --}}
        <section id="announcements" aria-labelledby="news-title" class="{{ $light }}">
            <div class="{{ $inner }}">
                <div class="{{ $head }}">
                    <div class="max-w-xl">
                        <p class="{{ $eyebrow }}">Community advisories</p>
                        <h2 id="news-title" class="{{ $h2 }}">Latest from Barangay San Luis.</h2>
                    </div>

                    @if ($hasImportant)
                        <div class="border-(--blue-700)/25 dark:bg-(--blue-870) inline-flex w-full overflow-hidden rounded-sm border bg-white text-sm font-semibold sm:w-auto dark:border-white/20"
                            role="group" aria-label="Filter announcements">
                            <button type="button" data-news-filter="all" aria-pressed="true"
                                class="{{ $focus }} bg-(--blue-700) text-(--off-white) dark:bg-(--celadon-500) dark:text-(--blue-850) flex-1 px-4 py-2 transition-colors sm:flex-none">All</button>
                            <button type="button" data-news-filter="important" aria-pressed="false"
                                class="{{ $focus }} hover:bg-(--blue-700)/10 flex-1 px-4 py-2 transition-colors sm:flex-none dark:hover:bg-white/10">Important</button>
                        </div>
                    @endif
                </div>

                @if ($announcements->isNotEmpty())
                    <div id="news-list" class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($announcements as $item)
                            @php $important = (bool) data_get($item, 'important'); @endphp
                            <article data-important="{{ (int) $important }}"
                                class="{{ $card }} {{ $important ? 'border-t-(--amber-500)' : 'border-t-(--blue-700) dark:border-t-(--celadon-500)' }} min-w-0 border-t-4 p-6">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="{{ $eyebrow }}">{{ data_get($item, 'date') }}</p>
                                    @if ($important)
                                        <span
                                            class="bg-(--amber-500) text-(--gray-900) rounded-full px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wide">Important</span>
                                    @endif
                                </div>
                                <h3 class="wrap-break-word mt-3 text-lg font-bold leading-snug sm:text-xl">
                                    {{ data_get($item, 'title') }}</h3>
                                @if (data_get($item, 'description'))
                                    <p class="{{ $muted }} wrap-break-word mt-2.5 text-sm leading-6">
                                        {{ data_get($item, 'description') }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="{{ $card }} mt-8 p-10 text-center">
                        <p class="font-bold">No announcements right now.</p>
                        <p class="{{ $muted }} mt-1 text-sm">Official barangay advisories and public notices will
                            appear here.</p>
                    </div>
                @endif
            </div>
        </section>

        {{-- ========== Profile + help ========== --}}
        <section class="{{ $deep }}">
            <div class="mx-auto grid max-w-7xl gap-10 px-5 py-12 sm:px-8 sm:py-20 lg:grid-cols-2 lg:gap-12">

                {{-- Profile --}}
                <div id="profile" class="{{ $cardDark }} flex min-w-0 flex-col justify-between p-6 sm:p-8">
                    <div>
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="{{ $eyebrowDark }}">Your account</p>
                                <h2 class="{{ $h2s }}">Citizen profile</h2>
                            </div>
                            <span
                                class="bg-(--celadon-500)/15 text-(--celadon-500) rounded-full px-3 py-1 text-xs font-bold tabular-nums">{{ $percent }}%
                                Complete</span>
                        </div>

                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-white/15" role="progressbar"
                            aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"
                            aria-label="Profile completion">
                            <div class="bg-(--celadon-500) h-full rounded-full transition-all duration-700"
                                style="width: {{ $percent }}%"></div>
                        </div>

                        <dl class="mt-6 divide-y divide-white/10 border-y border-white/10">
                            @foreach ($details as [$name, $value])
                                <div class="grid gap-1 py-3.5 sm:grid-cols-[10rem_1fr] sm:gap-4">
                                    <dt
                                        class="text-(--off-white)/65 text-xs font-semibold uppercase tracking-wider sm:pt-0.5">
                                        {{ $name }}</dt>
                                    <dd class="wrap-break-word min-w-0 text-sm font-semibold">
                                        @if ($value)
                                            {{ $value }}
                                        @else
                                            <a href="{{ route('citizen.profile') }}"
                                                class="{{ $textLinkDark }} text-xs">+ Add
                                                {{ str($name)->lower() }}</a>
                                        @endif
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>

                    <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
                        <a href="{{ route('citizen.profile') }}" class="{{ $btnDark }}">Update profile</a>
                        @if ($missing)
                            <p class="text-(--amber-500) text-xs font-semibold">{{ $missing }}
                                {{ str('detail')->plural($missing) }} missing</p>
                        @else
                            <p class="text-(--off-white)/70 text-xs">All profile fields complete.</p>
                        @endif
                    </div>
                </div>

                {{-- Barangay hall directory --}}
                <div id="help" class="{{ $cardDark }} flex min-w-0 flex-col justify-between p-6 sm:p-8">
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="{{ $eyebrowDark }}">Barangay Hall directory</p>
                            <span id="office-status"
                                class="hidden rounded-full px-2.5 py-0.5 text-xs font-semibold"></span>
                        </div>
                        <h2 class="{{ $h2s }}">Need assistance?</h2>
                        <p class="text-(--off-white)/75 mt-2 text-sm leading-6">
                            Reach out or visit Ynares Hall for inquiries regarding certificates, fees, and account
                            verification.
                        </p>

                        <dl class="mt-6 space-y-4">
                            @foreach ($contacts as [$icon, $name, $value, $link])
                                <div class="flex items-start gap-3.5">
                                    <span
                                        class="bg-(--celadon-500)/15 text-(--celadon-500) ring-(--celadon-500)/30 mt-0.5 inline-flex size-9 shrink-0 items-center justify-center rounded-sm ring-1">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75"
                                            viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="{{ $icon }}" />
                                        </svg>
                                    </span>
                                    <div class="min-w-0">
                                        <dt class="text-(--off-white)/60 text-xs font-semibold">{{ $name }}</dt>
                                        <dd class="wrap-break-word mt-0.5 text-sm font-semibold">
                                            @if ($link)
                                                <a href="{{ $link }}"
                                                    class="{{ $focusDark }} hover:text-(--celadon-500) break-all rounded-sm underline-offset-4 transition-colors hover:underline">{{ $value }}</a>
                                            @else
                                                {{ $value }}
                                            @endif
                                        </dd>
                                    </div>
                                </div>
                            @endforeach
                        </dl>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-x-6 gap-y-2 border-t border-white/10 pt-5">
                        <a href="{{ route('citizen.identity-verification') }}"
                            class="{{ $textLinkDark }} text-xs">Identity verification guide</a>
                        <a href="{{ route('privacy') }}" class="{{ $textLinkDark }} text-xs">Privacy notice</a>
                    </div>
                </div>

            </div>
        </section>
    </div>

    <script src="https://www.tuqlas.com/chatbot.js" data-key="tq_live_b008f3e486ad59592451ce7d324b4c54c94ccd3a"
        data-api="https://www.tuqlas.com" defer></script>

    <script>
        (function() {
            const activeCls = ['bg-(--blue-700)', 'text-(--off-white)', 'dark:bg-(--celadon-500)',
                'dark:text-(--blue-850)'
            ];
            const idleCls = ['hover:bg-(--blue-700)/10', 'dark:hover:bg-white/10'];

            // Announcement filter. One delegated listener, so it survives the layout's 5-second content refresh.
            document.addEventListener('click', function(event) {
                const btn = event.target.closest('[data-news-filter]');
                if (!btn) return;

                const important = btn.dataset.newsFilter === 'important';

                document.querySelectorAll('[data-news-filter]').forEach(function(b) {
                    const active = b === btn;
                    b.setAttribute('aria-pressed', active);
                    activeCls.forEach(function(c) {
                        b.classList.toggle(c, active);
                    });
                    idleCls.forEach(function(c) {
                        b.classList.toggle(c, !active);
                    });
                });

                document.querySelectorAll('#news-list [data-important]').forEach(function(item) {
                    item.hidden = important && item.dataset.important !== '1';
                });
            });

            // Office hours badge (Philippine time). Re-checked every 5 seconds because
            // the layout's content refresh resets the badge to its empty server version.
            function updateOfficeStatus() {
                const badge = document.getElementById('office-status');
                if (!badge) return;

                const parts = new Intl.DateTimeFormat('en-US', {
                    timeZone: 'Asia/Manila',
                    weekday: 'short',
                    hour: 'numeric',
                    hourCycle: 'h23',
                }).formatToParts(new Date());

                const day = parts.find(function(p) {
                    return p.type === 'weekday';
                })?.value;
                const hour = Number(parts.find(function(p) {
                    return p.type === 'hour';
                })?.value);
                const open = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'].includes(day) && hour >= 8 && hour < 17;

                badge.textContent = open ? 'Office open now' : 'Office closed now';
                badge.className = 'rounded-full px-2.5 py-0.5 text-xs font-semibold text-(--white) ' +
                    (open ? 'bg-(--green-600)' : 'bg-(--gray-600)');
            }

            updateOfficeStatus();
            setInterval(updateOfficeStatus, 5000);
        })();
    </script>
@endsection

@section('footer')
    @include('components.partials.c-footer')
@endsection
