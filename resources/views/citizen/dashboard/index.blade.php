@extends('layouts.citizen')

@section('title', 'Citizen Dashboard')
@section('flush', true) {{-- tells layouts.citizen to skip its main padding --}}

@section('content')
    @php
        $user = auth()->user();
        $profile = $user->citizenProfile;
        $firstName = str($user->name)->before(' ');
        $identity = $user->identityStatus();
        $verified = $identity === 'verified';
        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

        // text, badge classes, message, action label
        [$statusText, $statusBadge, $statusMessage, $statusAction] = [
            'verified' => ['Verified', 'bg-(--green-600) text-(--white)', 'Your identity is verified. You can use all barangay services.', null],
            'pending' => ['Pending review', 'bg-(--amber-500) text-(--gray-900)', 'The barangay is reviewing your documents.', 'View status'],
            'rejected' => ['Rejected', 'bg-(--red-600) text-(--white)', 'Your identity verification was not approved.', 'See why and resubmit'],
        ][$identity] ?? ['Not verified', 'bg-(--gray-600) text-(--white)', 'Verify your identity to request barangay services.', 'Verify your account'];

        // title, description, href, icon path, chips
        $actions = [
            ['Request a service', 'Choose a certificate and submit it online, without the extra trip to the office.', route('citizen.services'),
                'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
                ['Clearance', 'Residency', 'Indigency']],
            ['My applications', 'Check the status and updates of your requests anytime.', route('citizen.applications'),
                'M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0118 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3l1.5 1.5 3-3.75',
                ['Status updates', 'Track requests']],
            ['My documents', 'View and download the documents issued to you by the barangay.', route('citizen.documents'),
                'M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5zM6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z',
                ['QR verified', 'Issued copies']],
        ];

        $navItems = [['services', 'Services'], ['announcements', 'Announcements'], ['profile', 'Profile'], ['help', 'Help']];

        $services = $services ?? [
            ['name' => 'Barangay clearance', 'description' => 'For employment, applications, and other official transactions.'],
            ['name' => 'Certificate of residency', 'description' => 'Proof of residence in Barangay San Luis.'],
            ['name' => 'Certificate of indigency', 'description' => 'For residents applying for assistance.'],
        ];

        $details = [
            ['Phone', $user->phone],
            ['Date of birth', $profile?->date_of_birth?->format('M j, Y')],
            ['Address', $profile?->full_address],
        ];

        $missing = collect($details)->filter(fn($row) => blank($row[1]))->count();
        $percent = (int) round((count($details) - $missing) / count($details) * 100);

        $steps = [
            ['01', 'Verify your identity', 'Submit your profile and a valid ID.', $verified, route('citizen.identity-verification')],
            ['02', 'Complete your profile', 'Add your phone, birth date, and address.', $missing === 0, route('citizen.profile')],
            ['03', 'Request a service', 'Choose a certificate and submit.', false, route('citizen.services')],
        ];

        $ready = $verified && $missing === 0;
        $doneCount = (int) $verified + (int) ($missing === 0);

        $stats = $stats ?? [];
        $recentRequests = $recentRequests ?? collect();
        $announcements = collect($announcements ?? []);
        $hasImportant = $announcements->contains(fn($a) => data_get($a, 'important'));

        $contacts = [
            ['Location', 'Barangay San Luis, Antipolo City, Rizal 1870', null],
            ['Office hours', 'Monday to Friday, 8:00 AM to 5:00 PM', null],
            ['Phone', '8-8555-245', 'tel:88555245'],
            ['Email', 'barangaychain@gmail.com', 'mailto:barangaychain@gmail.com'],
        ];

        $chev = 'M8.25 4.5l7.5 7.5-7.5 7.5';
        $arrow = fn($class = 'h-4 w-4') => '<svg class="' . $class . '" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="' . $chev . '"/></svg>';

        // Shared class strings (same tokens as the home page)
        $btn = 'inline-flex min-h-11 items-center justify-center gap-2 rounded-md bg-(--blue-600) px-5 py-3 text-sm font-semibold text-(--white) transition duration-200 hover:-translate-y-0.5 hover:bg-(--blue-700) hover:shadow-md';
        $btnOutline = 'inline-flex min-h-11 items-center justify-center rounded-md border border-(--blue-600) px-5 py-3 text-sm font-semibold text-(--blue-700) dark:text-(--blue-300) transition-colors duration-200 hover:bg-[color-mix(in_srgb,var(--blue-600)_10%,transparent)]';
        $eyebrow = 'text-sm font-semibold text-(--green-600) dark:text-(--green-300)';
        $h2 = 'mt-2 text-2xl font-bold tracking-tight text-(--gray-900) dark:text-(--gray-50) sm:text-3xl';
        $textLink = 'inline-flex min-h-10 w-fit items-center gap-2 text-sm font-semibold text-(--blue-700) dark:text-(--blue-300) underline decoration-(--green-500) decoration-2 underline-offset-4 transition-colors hover:text-(--blue-800) dark:hover:text-(--blue-200)';
        $ink = 'text-(--gray-900) dark:text-(--gray-50)';
        $muted = 'text-(--gray-600) dark:text-(--gray-300)';
        $focus = 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--blue-600)';
        $glass = 'shadow-[0_6px_24px_-8px_rgba(23,50,92,0.22)] dark:shadow-[0_6px_28px_-8px_rgba(59,130,246,0.18)] bg-[color-mix(in_srgb,var(--white)_80%,transparent)] dark:bg-[color-mix(in_srgb,var(--gray-800)_80%,transparent)] backdrop-blur-xl';
        $tint = 'shadow-[0_0_40px_-10px_rgba(23,50,92,0.25)] dark:shadow-[0_0_40px_-10px_rgba(59,130,246,0.18)] bg-[color-mix(in_srgb,var(--blue-900)_8%,transparent)] dark:bg-[color-mix(in_srgb,var(--blue-900)_40%,transparent)]';
        $divide = 'divide-(--gray-200) dark:divide-(--gray-700)';

        // The layout adds no padding for this page (see @section('flush')), so sections are simply full width
        $bleed = 'relative w-full';
        $inner = 'mx-auto max-w-7xl px-5 py-12 sm:px-8 sm:py-16 lg:py-20';
        $head = 'flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between';
    @endphp

    <div class="min-w-0 overflow-x-clip">

        {{-- Hero (same as the home page) --}}
        <section class="{{ $bleed }} isolate overflow-hidden bg-(--gray-50) dark:bg-(--gray-900)">
            <x-partials.blueprint src="{{ asset('img/photos/ynares-hall-front.jpg') }}" class="absolute inset-0 -z-20" />
            <div aria-hidden="true"
                class="bg-linear-to-r from-(--gray-50) dark:from-(--gray-900) absolute inset-0 -z-10 via-[color-mix(in_srgb,var(--gray-50)_90%,transparent)] to-[color-mix(in_srgb,var(--gray-50)_10%,transparent)] lg:via-[color-mix(in_srgb,var(--gray-50)_70%,transparent)] lg:to-transparent dark:via-[color-mix(in_srgb,var(--gray-900)_90%,transparent)] dark:to-[color-mix(in_srgb,var(--gray-900)_10%,transparent)] dark:lg:via-[color-mix(in_srgb,var(--gray-900)_70%,transparent)]"></div>
            <div aria-hidden="true"
                class="bg-linear-to-t from-(--gray-50) dark:from-(--gray-900) absolute inset-x-0 bottom-0 -z-10 h-1/3 to-transparent"></div>

            <div class="mx-auto max-w-7xl px-5 pb-10 pt-12 sm:px-8 sm:pt-16 lg:pt-20">
                <div class="grid items-start gap-8 lg:grid-cols-[1.4fr_1fr] lg:gap-14">
                    <div class="min-w-0">
                        <p class="{{ $eyebrow }} uppercase">Barangay San Luis, Antipolo City</p>
                        <h1 class="{{ $ink }} mt-3 max-w-2xl text-4xl font-bold leading-tight tracking-tight sm:text-5xl lg:text-6xl">
                            {{ $greeting }}, <span class="text-(--green-600) dark:text-(--green-300)">{{ $firstName }}.</span>
                        </h1>
                        <p class="{{ $muted }} mt-6 max-w-md text-base leading-7">
                            Request barangay certificates, check your applications, and keep your account details up to date in one place.
                        </p>
                        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                            <a href="{{ route('citizen.services') }}" class="{{ $btn }} w-full sm:w-auto">Request a service</a>
                            <a href="{{ route('citizen.applications') }}" class="{{ $btnOutline }} w-full sm:w-auto">My applications</a>
                        </div>
                    </div>

                    {{-- Account and identity status --}}
                    <div class="min-w-0 rounded-2xl bg-[color-mix(in_srgb,var(--white)_90%,transparent)] p-5 shadow-[0_8px_28px_-8px_rgba(23,50,92,0.28)] backdrop-blur-md sm:p-6 lg:mt-2 dark:bg-[color-mix(in_srgb,var(--gray-800)_90%,transparent)] dark:shadow-[0_8px_28px_-8px_rgba(59,130,246,0.22)]">
                        <div class="flex min-w-0 items-center gap-3">
                            <img src="{{ asset('img/branding/barangaychain-lg2.png') }}" alt=""
                                class="border-(--gray-200) dark:border-(--gray-700) h-12 w-12 shrink-0 rounded-full border bg-(--white) object-contain p-0.5">
                            <div class="min-w-0">
                                <p class="{{ $ink }} truncate font-semibold">{{ $user->name }}</p>
                                <p class="{{ $muted }} break-all text-xs sm:text-sm">{{ $user->email }}</p>
                            </div>
                        </div>

                        <div class="border-(--gray-200) dark:border-(--gray-700) mt-5 border-t pt-5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="{{ $ink }} text-sm font-semibold">Identity status</p>
                                <span class="{{ $statusBadge }} rounded-full px-2.5 py-1 text-xs font-semibold">{{ $statusText }}</span>
                            </div>
                            <p class="{{ $muted }} mt-3 text-sm leading-6">{{ $statusMessage }}</p>
                            @if ($statusAction)
                                <a href="{{ route('citizen.identity-verification') }}" class="{{ $btn }} mt-4 w-full sm:w-auto">{{ $statusAction }}</a>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Quick action cards --}}
                <div class="mt-12 grid gap-3 lg:mt-16 lg:grid-cols-3">
                    @foreach ($actions as [$name, $text, $href, $icon, $chips])
                        <a href="{{ $href }}"
                            class="{{ $focus }} group relative flex h-full min-h-48 flex-col overflow-hidden rounded-2xl bg-[color-mix(in_srgb,var(--white)_85%,transparent)] p-5 shadow-[0_8px_28px_-8px_rgba(23,50,92,0.28)] backdrop-blur-xl transition duration-300 hover:-translate-y-1 hover:shadow-[0_14px_36px_-8px_rgba(23,50,92,0.38)] dark:bg-[color-mix(in_srgb,var(--gray-800)_85%,transparent)] dark:shadow-[0_8px_28px_-8px_rgba(59,130,246,0.22)] dark:hover:shadow-[0_14px_36px_-8px_rgba(59,130,246,0.32)]">
                            <h2 class="{{ $ink }} text-xl font-extrabold tracking-tight">{{ $name }}</h2>
                            <p class="{{ $muted }} mt-1.5 max-w-60 text-[13px] leading-5">{{ $text }}</p>

                            <ul class="mt-3 flex flex-wrap gap-1.5 pr-14">
                                @foreach ($chips as $chip)
                                    <li class="text-(--blue-700) dark:text-(--blue-300) rounded-full bg-[color-mix(in_srgb,var(--blue-600)_10%,transparent)] px-2.5 py-0.5 text-[11px] font-semibold">{{ $chip }}</li>
                                @endforeach
                            </ul>

                            <span class="mt-auto pt-4 text-2xl font-semibold text-[color-mix(in_srgb,var(--gray-500)_50%,transparent)] dark:text-[color-mix(in_srgb,var(--gray-400)_50%,transparent)]">{{ sprintf('%02d', $loop->iteration) }}</span>

                            {{-- Corner cut-out with icon --}}
                            <span aria-hidden="true"
                                class="bg-(--gray-50) dark:bg-(--gray-900) text-(--blue-700) dark:text-(--blue-300) group-hover:text-(--blue-800) dark:group-hover:text-(--blue-200) absolute bottom-0 right-0 flex h-20 w-20 items-end justify-end rounded-tl-full p-4 transition-colors duration-300">
                                <svg class="h-8 w-8 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" stroke-width="1.25" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                                </svg>
                            </span>
                        </a>
                    @endforeach
                </div>

                @if ($stats)
                    <dl class="{{ $glass }} {{ $divide }} mt-3 grid grid-cols-2 divide-x divide-y overflow-hidden rounded-2xl md:grid-cols-4 md:divide-y-0">
                        @foreach ($stats as $name => $count)
                            <div class="hover:bg-(--gray-100) dark:hover:bg-(--gray-800) px-4 py-4 transition-colors sm:px-6">
                                <dt class="{{ $muted }} text-sm font-semibold">{{ $name }}</dt>
                                <dd class="{{ $ink }} mt-0.5 text-2xl font-bold">{{ $count }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </div>
        </section>

        <section aria-labelledby="recent-requests-title" class="{{ $bleed }}">
            <div class="{{ $inner }} py-8 sm:py-10">
                <div class="{{ $head }}">
                    <div>
                        <p class="{{ $eyebrow }} uppercase">Your activity</p>
                        <h2 id="recent-requests-title" class="{{ $h2 }}">Recent applications</h2>
                    </div>
                    <a href="{{ route('citizen.applications') }}" class="{{ $textLink }}">View all applications</a>
                </div>

                <div class="mt-6 grid gap-3">
                    @forelse ($recentRequests as $application)
                        @php
                            [$badgeClass, $statusLabel] = match ($application->status) {
                                'approved' => ['bg-(--green-600) text-(--white)', 'Approved'],
                                'rejected' => ['bg-(--red-600) text-(--white)', 'Rejected'],
                                'processing' => ['bg-(--amber-500) text-(--gray-900)', 'Processing'],
                                'cancelled' => ['bg-(--gray-600) text-(--white)', 'Cancelled'],
                                default => ['bg-(--amber-500) text-(--gray-900)', 'Pending'],
                            };
                        @endphp
                        <a href="{{ route('citizen.applications.show', $application) }}"
                            class="{{ $glass }} flex flex-wrap items-center justify-between gap-3 rounded-xl p-4 transition hover:-translate-y-0.5 sm:px-6">
                            <span class="min-w-0">
                                <span class="{{ $ink }} block font-semibold">{{ $application->serviceTitle() }}</span>
                                <span class="{{ $muted }} mt-1 block text-sm">{{ $application->reference_number }} · Submitted {{ $application->created_at->format('M j, Y') }}</span>
                            </span>
                            <span class="{{ $badgeClass }} rounded-full px-3 py-1 text-xs font-semibold">{{ $statusLabel }}</span>
                        </a>
                    @empty
                        <p class="{{ $glass }} {{ $muted }} rounded-xl p-5 text-sm">You have not submitted any applications yet.</p>
                    @endforelse
                </div>
            </div>
        </section>

        {{-- Getting started --}}
        @unless ($ready)
            <section id="getting-started" aria-labelledby="ready-title" class="{{ $bleed }}">
                <div class="{{ $inner }}">
                    <div class="{{ $head }}">
                        <div class="max-w-xl">
                            <p class="{{ $eyebrow }} uppercase">Getting started</p>
                            <h2 id="ready-title" class="{{ $h2 }}">Before you apply.</h2>
                        </div>
                        <p class="{{ $muted }} text-sm font-semibold">{{ $doneCount }} of 2 required steps done</p>
                    </div>

                    <div class="bg-(--gray-200) dark:bg-(--gray-700) mt-4 h-2 overflow-hidden rounded-full" role="progressbar"
                        aria-valuenow="{{ $doneCount }}" aria-valuemin="0" aria-valuemax="2" aria-label="Required steps completed">
                        <div class="bg-(--green-600) h-full rounded-full transition-all duration-700" style="width: {{ $doneCount * 50 }}%"></div>
                    </div>

                    <ol class="{{ $glass }} {{ $divide }} mt-8 grid divide-y overflow-hidden rounded-md md:grid-cols-3 md:divide-x md:divide-y-0">
                        @foreach ($steps as [$number, $name, $text, $done, $href])
                            <li class="hover:bg-(--gray-100) dark:hover:bg-(--gray-800) group relative p-5 transition-colors duration-300 sm:p-7">
                                <span aria-hidden="true" class="{{ $done ? 'bg-(--green-600)' : ['bg-(--blue-600)', 'bg-[color-mix(in_srgb,var(--blue-600)_70%,transparent)]', 'bg-[color-mix(in_srgb,var(--blue-600)_45%,transparent)]'][$loop->index] }} absolute inset-x-0 top-0 h-1"></span>
                                <div class="flex items-center justify-between gap-3">
                                    <p class="{{ $ink }} group-hover:text-(--blue-700) dark:group-hover:text-(--blue-300) text-3xl font-bold tracking-tight transition-colors sm:text-4xl">{{ $number }}</p>
                                    @if ($done)
                                        <span class="bg-(--green-600) text-(--white) rounded-full px-2.5 py-1 text-xs font-semibold">✓ Done</span>
                                    @endif
                                </div>
                                <h3 class="{{ $ink }} mt-5 text-lg font-bold">{{ $name }}</h3>
                                <p class="{{ $muted }} mt-2 text-sm leading-6">{{ $text }}</p>
                                @unless ($done)
                                    <a href="{{ $href }}" class="{{ $textLink }} mt-4">Go to this step<span class="sr-only">: {{ $name }}</span></a>
                                @endunless
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>
        @endunless

        {{-- Section navigation --}}
        <x-navigation.section-nav :items="$navItems" label="Dashboard sections"
            class="{{ $ready ? 'mt-10 sm:mt-20' : '' }}" />

        {{-- Services --}}
        <section id="services" aria-labelledby="services-title" class="{{ $bleed }} {{ $tint }} relative z-10 scroll-mt-4">
            <div class="{{ $inner }}">
                <div class="{{ $head }}">
                    <div class="max-w-xl">
                        <p class="{{ $eyebrow }} uppercase">Online services</p>
                        <h2 id="services-title" class="{{ $h2 }}">What can we help you with?</h2>
                    </div>
                    <a href="{{ route('citizen.services') }}" class="{{ $textLink }}">View all services</a>
                </div>

                <ul class="{{ $glass }} {{ $divide }} border-t-(--blue-600) mt-8 divide-y overflow-hidden rounded-md border-t-4">
                    @foreach ($services as $service)
                        @php $requirements = data_get($service, 'requirements', []); @endphp
                        <li>
                            <details class="group">
                                <summary class="{{ $focus }} hover:bg-(--gray-100) dark:hover:bg-(--gray-800) flex cursor-pointer list-none items-center gap-3 p-4 transition-colors sm:gap-4 sm:p-6 [&::-webkit-details-marker]:hidden">
                                    <span class="text-(--green-600) dark:text-(--green-300) w-7 shrink-0 text-sm font-semibold sm:w-8">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="{{ $ink }} block text-base font-bold sm:text-lg">{{ data_get($service, 'name') }}</span>
                                        <span class="{{ $muted }} mt-1 block text-sm">{{ data_get($service, 'description') }}</span>
                                    </span>
                                    {!! $arrow('text-(--blue-700) dark:text-(--blue-300) h-5 w-5 shrink-0 transition-transform group-open:rotate-90') !!}
                                </summary>

                                <div class="border-(--gray-200) dark:border-(--gray-700) bg-[color-mix(in_srgb,var(--gray-100)_60%,transparent)] dark:bg-[color-mix(in_srgb,var(--gray-900)_50%,transparent)] border-t px-5 py-5 text-sm sm:pl-20">
                                    @if (is_array($requirements) && $requirements)
                                        <p class="{{ $ink }} font-semibold">Requirements</p>
                                        <ul class="text-(--gray-700) dark:text-(--gray-200) mt-1 list-disc space-y-0.5 pl-5">
                                            @foreach ($requirements as $requirement)
                                                <li>{{ $requirement }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    @unless ($verified)
                                        <p class="text-(--gray-700) dark:text-(--gray-200) mt-2">Your identity must be verified before you can submit this request.</p>
                                    @endunless
                                    <a href="{{ route('citizen.services') }}" class="{{ $btn }} mt-4 w-full sm:w-auto">Request this service</a>
                                </div>
                            </details>
                        </li>
                    @endforeach
                </ul>

                <p class="{{ $muted }} mt-4 text-sm leading-6">
                    Requirements, fees, and processing times are subject to confirmation by the barangay office.
                </p>
            </div>
        </section>

        {{-- Announcements --}}
        <section id="announcements" aria-labelledby="news-title" class="{{ $bleed }} scroll-mt-4">
            <div class="{{ $inner }}">
                <div class="{{ $head }}">
                    <div class="max-w-xl">
                        <p class="{{ $eyebrow }} uppercase">Announcements and updates</p>
                        <h2 id="news-title" class="{{ $h2 }}">Latest from the barangay.</h2>
                    </div>

                    @if ($hasImportant)
                        <div class="border-(--gray-300) dark:border-(--gray-600) inline-flex w-full overflow-hidden rounded-md border text-sm font-semibold sm:w-auto" role="group" aria-label="Filter announcements">
                            <button type="button" data-news-filter="all" aria-pressed="true" class="{{ $focus }} bg-(--blue-600) text-(--white) flex-1 px-4 py-2 sm:flex-none">All</button>
                            <button type="button" data-news-filter="important" aria-pressed="false" class="{{ $focus }} text-(--gray-700) dark:text-(--gray-200) hover:bg-(--gray-100) dark:hover:bg-(--gray-800) flex-1 px-4 py-2 sm:flex-none">Important</button>
                        </div>
                    @endif
                </div>

                @if ($announcements->isNotEmpty())
                    <div id="news-list" class="mt-8 grid gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3">
                        @foreach ($announcements as $item)
                            @php $important = (bool) data_get($item, 'important'); @endphp
                            <article data-important="{{ (int) $important }}"
                                class="{{ $glass }} flex min-w-0 flex-col rounded-md border-t-4 p-5 sm:p-6 {{ $important ? 'border-t-(--amber-500)' : 'border-t-(--blue-600)' }}">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-(--green-600) dark:text-(--green-300) text-sm font-semibold">{{ data_get($item, 'date') }}</p>
                                    @if ($important)
                                        <span class="bg-(--amber-500) text-(--gray-900) rounded-full px-2.5 py-0.5 text-xs font-semibold">Important</span>
                                    @endif
                                </div>
                                <h3 class="{{ $ink }} mt-2 wrap-break-word text-lg font-bold">{{ data_get($item, 'title') }}</h3>
                                @if (data_get($item, 'description'))
                                    <p class="{{ $muted }} mt-3 wrap-break-word text-sm leading-6">{{ data_get($item, 'description') }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="{{ $glass }} border-t-(--blue-600) mt-8 rounded-md border-t-4 p-8 text-center">
                        <p class="{{ $ink }} font-bold">No announcements right now.</p>
                        <p class="{{ $muted }} mt-1 text-sm">New barangay notices will be posted here.</p>
                    </div>
                @endif
            </div>
        </section>

        {{-- Profile --}}
        <section id="profile" aria-labelledby="profile-title" class="{{ $bleed }} {{ $tint }} relative z-10 scroll-mt-4">
            <div class="{{ $inner }}">
                <div class="{{ $head }}">
                    <div class="max-w-xl">
                        <p class="{{ $eyebrow }} uppercase">Your account</p>
                        <h2 id="profile-title" class="{{ $h2 }}">Keep your details up to date.</h2>
                    </div>
                    <a href="{{ route('citizen.profile') }}" class="{{ $textLink }}">Edit profile</a>
                </div>

                <div class="mt-6 flex items-center justify-between text-sm">
                    <p class="{{ $muted }} font-semibold">Profile completion</p>
                    <p class="{{ $ink }} font-bold">{{ $percent }}%</p>
                </div>
                <div class="bg-(--gray-200) dark:bg-(--gray-700) mt-2 h-2 overflow-hidden rounded-full" role="progressbar"
                    aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100" aria-label="Profile completion">
                    <div class="bg-(--green-600) h-full rounded-full transition-all duration-700" style="width: {{ $percent }}%"></div>
                </div>

                <dl class="{{ $glass }} {{ $divide }} mt-8 grid divide-y overflow-hidden rounded-md sm:grid-cols-2 sm:divide-y-0 md:grid-cols-3 md:divide-x">
                    @foreach ($details as [$name, $value])
                        <div class="min-w-0 p-5 sm:p-6">
                            <dt class="{{ $muted }} text-sm font-semibold">{{ $name }}</dt>
                            @if ($value)
                                <dd class="{{ $ink }} mt-1 wrap-break-word font-bold">{{ $value }}</dd>
                            @else
                                <dd class="mt-1">
                                    <a href="{{ route('citizen.profile') }}" class="{{ $textLink }}">Add {{ str($name)->lower() }}</a>
                                </dd>
                            @endif
                        </div>
                    @endforeach
                </dl>

                @if ($missing)
                    <p class="border-(--amber-500) text-(--gray-900) mt-4 rounded-md border bg-[color-mix(in_srgb,var(--amber-500)_18%,transparent)] p-4 text-sm font-semibold dark:text-(--gray-50)">
                        {{ $missing }} {{ str('detail')->plural($missing) }} missing from your profile.
                        <a href="{{ route('citizen.profile') }}" class="{{ $textLink }} ml-1">Complete your profile</a>
                    </p>
                @else
                    <p class="{{ $muted }} mt-4 text-sm">Your profile details are complete.</p>
                @endif
            </div>
        </section>

        {{-- Help --}}
        <section id="help" aria-labelledby="support-title" class="{{ $bleed }} scroll-mt-4">
            <div class="{{ $inner }}">
                <div class="grid gap-8 lg:grid-cols-[1fr_1.2fr] lg:gap-16">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-3">
                            <p class="{{ $eyebrow }} uppercase">Get in touch</p>
                            <span id="office-status" class="hidden rounded-full px-2.5 py-0.5 text-xs font-semibold"></span>
                        </div>
                        <h2 id="support-title" class="{{ $h2 }}">Need help?</h2>
                        <p class="{{ $muted }} mt-3 max-w-md text-sm leading-6">
                            Visit the barangay office for help with requests, requirements, fees, and profile changes.
                        </p>
                        <div class="mt-5 flex flex-wrap gap-x-6 gap-y-2">
                            <a href="{{ route('citizen.identity-verification') }}" class="{{ $textLink }}">Identity verification guide</a>
                            <a href="{{ route('privacy') }}" class="{{ $textLink }}">Privacy notice</a>
                        </div>
                    </div>

                    <dl class="{{ $glass }} border-t-(--green-600) grid gap-5 rounded-md border-t-4 p-5 sm:grid-cols-2 sm:p-6">
                        @foreach ($contacts as [$name, $value, $link])
                            <div class="min-w-0">
                                <dt class="{{ $muted }} text-sm font-semibold">{{ $name }}</dt>
                                <dd class="text-(--gray-700) dark:text-(--gray-200) mt-0.5 wrap-break-word text-sm font-semibold">
                                    @if ($link)
                                        <a href="{{ $link }}" class="hover:text-(--blue-700) dark:hover:text-(--blue-300) underline-offset-4 transition-colors hover:underline">{{ $value }}</a>
                                    @else
                                        {{ $value }}
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>
        </section>
    </div>

    <script>
        // Announcement filter
        const filters = document.querySelectorAll('[data-news-filter]');
        const news = document.querySelectorAll('#news-list [data-important]');
        const activeCls = ['bg-(--blue-600)', 'text-(--white)'];
        const idleCls = ['text-(--gray-700)', 'dark:text-(--gray-200)'];

        filters.forEach(btn => btn.addEventListener('click', () => {
            const important = btn.dataset.newsFilter === 'important';

            filters.forEach(b => {
                const active = b === btn;
                b.setAttribute('aria-pressed', active);
                activeCls.forEach(c => b.classList.toggle(c, active));
                idleCls.forEach(c => b.classList.toggle(c, !active));
            });

            news.forEach(item => {
                item.hidden = important && item.dataset.important !== '1';
            });
        }));

        // Office hours (Philippine time)
        const badge = document.getElementById('office-status');

        if (badge) {
            const parts = new Intl.DateTimeFormat('en-US', {
                timeZone: 'Asia/Manila',
                weekday: 'short',
                hour: 'numeric',
                hourCycle: 'h23'
            }).formatToParts(new Date());

            const day = parts.find(p => p.type === 'weekday')?.value;
            const hour = Number(parts.find(p => p.type === 'hour')?.value);
            const open = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'].includes(day) && hour >= 8 && hour < 17;

            badge.textContent = open ? 'Office open now' : 'Office closed now';
            badge.className = 'rounded-full px-2.5 py-0.5 text-xs font-semibold text-(--white) ' +
                (open ? 'bg-(--green-600)' : 'bg-(--gray-600)');
        }
    </script>
@endsection