@extends('layouts.citizen')

@section('title', 'Citizen Dashboard')

@section('content')
    @php
        $user = auth()->user();
        $profile = $user->citizenProfile;
        $firstName = str($user->name)->before(' ');
        $identity = $user->identityStatus();
        $verified = $identity === 'verified';
        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

        [$statusText, $statusBadge, $statusMessage, $statusAction] = [
            'verified' => ['Verified', 'bg-emerald-600 text-white', 'Your identity is verified. You can use all barangay services.', null],
            'pending' => ['Pending review', 'bg-amber-400 text-slate-900', 'The barangay is reviewing your documents.', 'View status'],
            'rejected' => ['Rejected', 'bg-red-600 text-white', 'Your identity verification was not approved.', 'See why and resubmit'],
        ][$identity] ?? ['Not verified', 'bg-slate-600 text-white', 'Verify your identity to request barangay services.', 'Verify your account'];

        $actions = [
            ['Request a service', 'Choose a certificate and submit', route('citizen.services')],
            ['My applications', 'Check the status of your requests', route('citizen.applications')],
            ['My documents', 'View your issued documents', route('citizen.documents')],
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
        $announcements = collect($announcements ?? []);
        $hasImportant = $announcements->contains(fn($a) => data_get($a, 'important'));

        $contacts = [
            ['Location', 'Barangay San Luis, Antipolo City, Rizal 1870', null],
            ['Office hours', 'Monday to Friday, 8:00 AM to 5:00 PM', null],
            ['Phone', '8-8555-245', 'tel:88555245'],
            ['Email', 'barangaychain@gmail.com', 'mailto:barangaychain@gmail.com'],
        ];

        $arrow = fn($class = 'h-4 w-4') => '<svg class="' . $class . '" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>';

        $btn = 'inline-flex min-h-11 items-center justify-center gap-2 rounded-md px-5 py-3 text-sm font-semibold text-white transition hover:shadow-md';
        $button = "$btn bg-blue-950 hover:bg-blue-800";
        $accent = "$btn bg-emerald-700 hover:bg-emerald-600";
        $ghost = 'inline-flex min-h-11 items-center justify-center rounded-md border border-white/50 px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-white hover:text-blue-950';
        $textLink = 'inline-flex min-h-10 items-center gap-2 text-sm font-semibold text-blue-900 underline decoration-emerald-600 decoration-2 underline-offset-4 hover:text-emerald-700';
        $label = 'text-sm font-semibold text-blue-800';
        $title = 'mt-2 text-2xl font-bold tracking-tight text-blue-950 sm:text-3xl';
        $card = 'rounded-md border border-slate-200 bg-white';
        $focus = 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600';

        $band = 'relative left-1/2 w-screen max-w-[100vw] -translate-x-1/2';
        $gray = 'border-y border-slate-200 bg-slate-50';
        $inner = 'mx-auto max-w-7xl px-5 py-12 sm:px-8 sm:py-16 lg:py-20';
        $head = 'flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between';
    @endphp

    <div class="min-w-0 overflow-x-clip">

        {{-- Hero --}}
        <section class="{{ $band }} isolate overflow-hidden bg-blue-950">
            <img src="{{ asset('img/photos/ynares-hall-front.jpg') }}" alt="" width="1214" height="724"
                class="absolute inset-0 -z-20 h-full w-full object-cover object-center sm:object-right">
            <div aria-hidden="true" class="absolute inset-0 -z-10 bg-linear-to-r from-blue-950 via-blue-950/85 to-blue-950/50"></div>

            <div class="mx-auto grid max-w-7xl items-center gap-8 px-5 pb-36 pt-12 sm:px-8 sm:pb-40 sm:pt-20 lg:grid-cols-[1.3fr_1fr] lg:gap-14 lg:pb-44">
                <div class="min-w-0 lg:ml-6">
                    <p class="text-sm font-semibold text-emerald-300">Barangay San Luis, Antipolo City</p>
                    <h1 class="mt-3 text-3xl font-bold leading-tight tracking-tight text-white sm:text-5xl">
                        {{ $greeting }}, {{ $firstName }}.
                    </h1>
                    <p class="mt-5 max-w-xl text-sm leading-7 text-blue-100 sm:text-lg">
                        Request barangay certificates, check your applications, and keep your account details up to date in one place.
                    </p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                        <a href="{{ route('citizen.services') }}" class="{{ $accent }} w-full sm:w-auto">Request a service</a>
                        <a href="{{ route('citizen.applications') }}" class="{{ $ghost }} w-full sm:w-auto">My applications</a>
                    </div>
                </div>

                {{-- Account and identity status --}}
                <div class="min-w-0 rounded-md bg-[#ffffffa1] p-5 text-slate-800 backdrop-blur-sm sm:mx-6 sm:p-6">
                    <div class="flex min-w-0 items-center gap-3">
                        <img src="{{ asset('img/branding/barangayChainPH-logo.png') }}" alt=""
                            class="h-12 w-12 shrink-0 rounded-full border border-slate-200 bg-white object-contain p-0.5">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-900">{{ $user->name }}</p>
                            <p class="break-all text-xs text-slate-700 sm:text-sm">{{ $user->email }}</p>
                        </div>
                    </div>

                    <div class="mt-5 border-t border-slate-400/50 pt-5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-800">Identity status</p>
                            <span class="{{ $statusBadge }} rounded px-2.5 py-1 text-xs font-semibold sm:text-sm">{{ $statusText }}</span>
                        </div>
                        <p class="mt-3 text-sm leading-6 text-slate-800">{{ $statusMessage }}</p>
                        @if ($statusAction)
                            <a href="{{ route('citizen.identity-verification') }}" class="{{ $button }} mt-4 w-full sm:w-auto">{{ $statusAction }}</a>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        {{-- Quick actions --}}
        <section aria-label="Quick actions" class="{{ $band }} relative z-10 -mt-28 sm:-mt-24 lg:-mt-36">
            <div class="mx-auto max-w-7xl px-5 sm:px-8">
                <div class="mx-0 overflow-hidden rounded-md border border-t-4 border-slate-200 border-t-emerald-500 bg-white shadow-lg sm:mx-6">
                    <div class="grid divide-y divide-slate-200 md:grid-cols-3 md:divide-x md:divide-y-0">
                        @foreach ($actions as [$name, $caption, $href])
                            <a href="{{ $href }}" class="{{ $focus }} group flex items-center justify-between gap-4 p-5 transition-colors hover:bg-emerald-50 sm:p-6">
                                <div class="min-w-0">
                                    <p class="text-base font-bold text-blue-950 sm:text-lg">{{ $name }}</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ $caption }}</p>
                                </div>
                                {!! $arrow('h-6 w-6 shrink-0 text-emerald-700 transition-transform group-hover:translate-x-1') !!}
                            </a>
                        @endforeach
                    </div>

                    @if ($stats)
                        <dl class="grid grid-cols-2 divide-x divide-y divide-slate-200 border-t border-slate-200 bg-slate-50 md:grid-cols-4 md:divide-y-0">
                            @foreach ($stats as $name => $count)
                                <div class="px-4 py-4 transition-colors hover:bg-emerald-50 sm:px-6">
                                    <dt class="text-sm font-semibold text-slate-600">{{ $name }}</dt>
                                    <dd class="mt-0.5 text-2xl font-bold text-blue-950">{{ $count }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                </div>
            </div>
        </section>

        {{-- Getting started --}}
        @unless ($ready)
            <section id="getting-started" aria-labelledby="ready-title" class="{{ $band }}">
                <div class="{{ $inner }}">
                    <div class="{{ $head }}">
                        <div class="max-w-xl">
                            <p class="{{ $label }}">Getting started</p>
                            <h2 id="ready-title" class="{{ $title }}">Before you apply.</h2>
                        </div>
                        <p class="text-sm font-semibold text-slate-700">{{ $doneCount }} of 2 required steps done</p>
                    </div>

                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-200" role="progressbar"
                        aria-valuenow="{{ $doneCount }}" aria-valuemin="0" aria-valuemax="2" aria-label="Required steps completed">
                        <div class="h-full rounded-full bg-emerald-600 transition-all duration-700" style="width: {{ $doneCount * 50 }}%"></div>
                    </div>

                    <ol class="mt-8 grid divide-y divide-slate-300 overflow-hidden rounded-md border border-slate-300 bg-white md:grid-cols-3 md:divide-x md:divide-y-0">
                        @foreach ($steps as [$number, $name, $text, $done, $href])
                            <li class="group relative p-5 transition-colors hover:bg-emerald-50 sm:p-7">
                                <span aria-hidden="true" class="{{ $done ? 'bg-emerald-600' : ['bg-blue-950', 'bg-blue-900', 'bg-emerald-800'][$loop->index] }} absolute inset-x-0 top-0 h-1"></span>
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-3xl font-bold tracking-tight text-blue-950 sm:text-4xl">{{ $number }}</p>
                                    @if ($done)
                                        <span class="rounded bg-emerald-600 px-2 py-1 text-xs font-semibold text-white">✓ Done</span>
                                    @endif
                                </div>
                                <h3 class="mt-5 text-lg font-bold text-blue-950">{{ $name }}</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $text }}</p>
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
        <x-navigation.section-nav :items="$navItems" label="Dashboard sections" bleed
            class="{{ $ready ? 'mt-10 sm:mt-20' : '' }}" />

        {{-- Services --}}
        <section id="services" aria-labelledby="services-title" class="{{ $band }} {{ $gray }} scroll-mt-4 border-t-0">
            <div class="{{ $inner }}">
                <div class="{{ $head }}">
                    <div class="max-w-xl">
                        <p class="{{ $label }}">Online services</p>
                        <h2 id="services-title" class="{{ $title }}">What can we help you with?</h2>
                    </div>
                    <a href="{{ route('citizen.services') }}" class="{{ $textLink }}">View all services</a>
                </div>

                <ul class="{{ $card }} mt-8 divide-y divide-slate-200 border-t-4 border-t-blue-950">
                    @foreach ($services as $service)
                        @php $requirements = data_get($service, 'requirements', []); @endphp
                        <li>
                            <details class="group">
                                <summary class="{{ $focus }} flex cursor-pointer list-none items-center gap-3 p-4 transition-colors hover:bg-slate-50 sm:gap-4 sm:p-6 [&::-webkit-details-marker]:hidden">
                                    <span class="w-7 shrink-0 text-sm font-semibold text-emerald-700 sm:w-8">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-base font-bold text-blue-950 sm:text-lg">{{ data_get($service, 'name') }}</span>
                                        <span class="mt-1 block text-sm text-slate-600">{{ data_get($service, 'description') }}</span>
                                    </span>
                                    {!! $arrow('h-5 w-5 shrink-0 text-blue-950 transition-transform group-open:rotate-90') !!}
                                </summary>

                                <div class="border-t border-slate-200 bg-slate-50 px-5 py-5 text-sm sm:pl-20">
                                    @if (is_array($requirements) && $requirements)
                                        <p class="font-semibold text-blue-950">Requirements</p>
                                        <ul class="mt-1 list-disc space-y-0.5 pl-5 text-slate-700">
                                            @foreach ($requirements as $requirement)
                                                <li>{{ $requirement }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    @unless ($verified)
                                        <p class="mt-2 text-slate-700">Your identity must be verified before you can submit this request.</p>
                                    @endunless
                                    <a href="{{ route('citizen.services') }}" class="{{ $button }} mt-4 w-full sm:w-auto">Request this service</a>
                                </div>
                            </details>
                        </li>
                    @endforeach
                </ul>

                <p class="mt-4 text-sm leading-6 text-slate-600">
                    Requirements, fees, and processing times are subject to confirmation by the barangay office.
                </p>
            </div>
        </section>

        {{-- Announcements --}}
        <section id="announcements" aria-labelledby="news-title" class="{{ $band }} scroll-mt-4">
            <div class="{{ $inner }}">
                <div class="{{ $head }}">
                    <div class="max-w-xl">
                        <p class="{{ $label }}">Announcements and updates</p>
                        <h2 id="news-title" class="{{ $title }}">Latest from the barangay.</h2>
                    </div>

                    @if ($hasImportant)
                        <div class="inline-flex w-full overflow-hidden rounded-md border border-slate-300 text-sm font-semibold sm:w-auto" role="group" aria-label="Filter announcements">
                            <button type="button" data-news-filter="all" aria-pressed="true" class="{{ $focus }} flex-1 bg-blue-950 px-4 py-2 text-white sm:flex-none">All</button>
                            <button type="button" data-news-filter="important" aria-pressed="false" class="{{ $focus }} flex-1 bg-white px-4 py-2 text-slate-800 hover:bg-slate-50 sm:flex-none">Important</button>
                        </div>
                    @endif
                </div>

                @if ($announcements->isNotEmpty())
                    <div id="news-list" class="mt-8 grid gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3">
                        @foreach ($announcements as $item)
                            @php $important = (bool) data_get($item, 'important'); @endphp
                            <article data-important="{{ (int) $important }}"
                                class="{{ $card }} flex min-w-0 flex-col border-t-4 p-5 sm:p-6 {{ $important ? 'border-t-amber-500 bg-amber-50' : 'border-t-blue-950' }}">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-emerald-700">{{ data_get($item, 'date') }}</p>
                                    @if ($important)
                                        <span class="rounded bg-amber-400 px-2 py-0.5 text-xs font-semibold text-slate-900">Important</span>
                                    @endif
                                </div>
                                <h3 class="mt-2 wrap-break-word text-lg font-bold text-blue-950">{{ data_get($item, 'title') }}</h3>
                                @if (data_get($item, 'description'))
                                    <p class="mt-3 wrap-break-word text-sm leading-6 text-slate-600">{{ data_get($item, 'description') }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="{{ $card }} mt-8 border-t-4 border-t-blue-950 p-8 text-center">
                        <p class="font-bold text-blue-950">No announcements right now.</p>
                        <p class="mt-1 text-sm text-slate-600">New barangay notices will be posted here.</p>
                    </div>
                @endif
            </div>
        </section>

        {{-- Profile --}}
        <section id="profile" aria-labelledby="profile-title" class="{{ $band }} {{ $gray }} scroll-mt-4">
            <div class="{{ $inner }}">
                <div class="{{ $head }}">
                    <div class="max-w-xl">
                        <p class="{{ $label }}">Your account</p>
                        <h2 id="profile-title" class="{{ $title }}">Keep your details up to date.</h2>
                    </div>
                    <a href="{{ route('citizen.profile') }}" class="{{ $textLink }}">Edit profile</a>
                </div>

                <div class="mt-6 flex items-center justify-between text-sm">
                    <p class="font-semibold text-slate-700">Profile completion</p>
                    <p class="font-bold text-blue-950">{{ $percent }}%</p>
                </div>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200" role="progressbar"
                    aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100" aria-label="Profile completion">
                    <div class="h-full rounded-full bg-emerald-600 transition-all duration-700" style="width: {{ $percent }}%"></div>
                </div>

                <dl class="mt-8 grid divide-y divide-slate-300 overflow-hidden rounded-md border border-slate-300 bg-white sm:grid-cols-2 sm:divide-y-0 md:grid-cols-3 md:divide-x">
                    @foreach ($details as [$name, $value])
                        <div class="min-w-0 p-5 sm:p-6">
                            <dt class="text-sm font-semibold text-slate-600">{{ $name }}</dt>
                            @if ($value)
                                <dd class="mt-1 wrap-break-word font-bold text-blue-950">{{ $value }}</dd>
                            @else
                                <dd class="mt-1">
                                    <a href="{{ route('citizen.profile') }}" class="{{ $textLink }}">Add {{ str($name)->lower() }}</a>
                                </dd>
                            @endif
                        </div>
                    @endforeach
                </dl>

                @if ($missing)
                    <p class="mt-4 rounded border border-amber-300 bg-amber-50 p-4 text-sm font-semibold text-amber-900">
                        {{ $missing }} {{ str('detail')->plural($missing) }} missing from your profile.
                        <a href="{{ route('citizen.profile') }}" class="{{ $textLink }} ml-1">Complete your profile</a>
                    </p>
                @else
                    <p class="mt-4 text-sm text-slate-600">Your profile details are complete.</p>
                @endif
            </div>
        </section>

        {{-- Help --}}
        <section id="help" aria-labelledby="support-title" class="{{ $band }} scroll-mt-4">
            <div class="{{ $inner }}">
                <div class="grid gap-8 lg:grid-cols-[1fr_1.2fr] lg:gap-16">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-3">
                            <p class="{{ $label }}">Get in touch</p>
                            <span id="office-status" class="hidden rounded px-2.5 py-0.5 text-xs font-semibold"></span>
                        </div>
                        <h2 id="support-title" class="{{ $title }}">Need help?</h2>
                        <p class="mt-3 max-w-md text-sm leading-6 text-slate-600">
                            Visit the barangay office for help with requests, requirements, fees, and profile changes.
                        </p>
                        <div class="mt-5 flex flex-wrap gap-x-6 gap-y-2">
                            <a href="{{ route('citizen.identity-verification') }}" class="{{ $textLink }}">Identity verification guide</a>
                            <a href="{{ route('privacy') }}" class="{{ $textLink }}">Privacy notice</a>
                        </div>
                    </div>

                    <dl class="{{ $card }} grid gap-5 border-t-4 border-t-emerald-500 bg-slate-50 p-5 sm:grid-cols-2 sm:p-6">
                        @foreach ($contacts as [$name, $value, $link])
                            <div class="min-w-0">
                                <dt class="text-sm font-semibold text-slate-600">{{ $name }}</dt>
                                <dd class="mt-0.5 wrap-break-word text-sm font-semibold text-blue-950">
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
            </div>
        </section>
    </div>

    <script>
        // Announcement filter
        const filters = document.querySelectorAll('[data-news-filter]');
        const news = document.querySelectorAll('#news-list [data-important]');

        filters.forEach(btn => btn.addEventListener('click', () => {
            const important = btn.dataset.newsFilter === 'important';

            filters.forEach(b => {
                const active = b === btn;
                b.setAttribute('aria-pressed', active);
                b.classList.toggle('bg-blue-950', active);
                b.classList.toggle('text-white', active);
                b.classList.toggle('bg-white', !active);
                b.classList.toggle('text-slate-800', !active);
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
            badge.className = 'rounded px-2.5 py-0.5 text-xs font-semibold text-white ' +
                (open ? 'bg-emerald-600' : 'bg-slate-600');
        }
    </script>
@endsection