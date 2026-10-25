@extends('layouts.citizen')

@section('title', 'Citizen Dashboard')

@section('content')
@php
    $user = auth()->user();
    $profile = $user->citizenProfile;

    // Identity status: text and color
    $statuses = [
        'verified' => ['Verified', 'text-green-800'],
        'pending' => ['Pending review', 'text-amber-700'],
        'rejected' => ['Rejected', 'text-red-700'],
    ];
    [$statusText, $statusColor] = $statuses[$user->identityStatus()] ?? ['Not verified', 'text-slate-600'];

    // Services: icon, name
    $services = [
        ['M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 0 1 1 1v13H4V6a1 1 0 0 1 1-1Z', 'Barangay clearance'],
        ['M3 10.5 12 3l9 7.5M5 9v11h14V9M9 20v-6h6v6', 'Certificate of residency'],
        ['M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2ZM14 3v5h5M9 13h6M9 17h6', 'Certificate of indigency'],
    ];

    // Profile rows: label, value
    $details = [
        ['Phone', $profile?->phone ?: 'Not added'],
        ['Date of birth', $profile?->date_of_birth?->format('M j, Y') ?? 'Not added'],
        ['Address', $profile?->full_address ?: 'Add your address to complete your profile'],
    ];

    // Classes used many times
    $card = 'rounded-md border border-slate-200 bg-white p-6';
    $heading = 'border-l-2 border-green-800 pl-3 font-bold text-blue-950';
    $link = 'text-sm font-semibold text-blue-900 underline underline-offset-4 hover:text-blue-700';
@endphp
    <div class="space-y-6">

        {{-- Hero --}}
        <section class="grid overflow-hidden rounded-md border border-slate-200 bg-white lg:grid-cols-[1.4fr_1fr]">

            {{-- Welcome --}}
            <div class="bg-linear-to-br from-blue-900 via-blue-950 to-green-950 p-8 text-white sm:p-10">
                <p class="flex items-center gap-3 text-xs font-bold uppercase tracking-[0.18em] text-blue-200">
                    <span class="h-px w-8 bg-green-500"></span> Barangay San Luis, Antipolo City
                </p>
                <h1 class="mt-4 text-3xl font-semibold leading-tight tracking-tight sm:text-4xl">
                    Maasikaso at maaasahang serbisyo, para sa bawat San Luiseño.
                </h1>
                <p class="mt-4 max-w-lg text-sm leading-6 text-blue-100">
                    Find barangay services, check your identity verification, and keep your account details up to date.
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('citizen.services') }}"
                        class="bg-white px-5 py-2.5 text-sm font-semibold text-blue-950 hover:bg-blue-50">View services</a>
                    <a href="{{ route('citizen.applications') }}"
                        class="border border-white/40 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/10">My applications</a>
                </div>
            </div>

            {{-- Account and identity status --}}
            <div class="flex flex-col justify-center gap-5 p-8">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('img/branding/barangayChainPH-logo.png') }}" alt=""
                        class="h-12 w-12 rounded-full border border-slate-200 bg-white object-contain p-0.5">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-blue-950">{{ $user->name }}</p>
                        <p class="truncate text-sm text-slate-500">{{ $user->email }}</p>
                    </div>
                </div>

                <div class="border-t border-slate-200 pt-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Identity status</p>
                    <p class="mt-1 text-lg font-bold {{ $statusColor }}">{{ $statusText }}</p>

                    @unless ($user->isIdentityVerified())
                        <a href="{{ route('citizen.identity-verification') }}" class="{{ $link }} mt-2 inline-block">
                            {{ $user->identityStatus() === 'pending' ? 'View status' : 'Verify your account' }}
                        </a>
                    @endunless
                </div>
            </div>
        </section>

        {{-- Services and announcements --}}
        <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">

            <section class="{{ $card }}">
                <h2 class="{{ $heading }}">Barangay services</h2>

                <div class="mt-3 divide-y divide-slate-200">
                    @foreach ($services as [$icon, $name])
                        <a href="{{ route('citizen.services') }}" class="group flex items-center gap-4 py-4">
                            <svg class="h-6 w-6 shrink-0 text-green-800" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path d="{{ $icon }}" />
                            </svg>
                            <span class="flex-1 text-sm font-semibold text-blue-950 group-hover:text-blue-700">{{ $name }}</span>
                            <span class="text-slate-400 group-hover:text-blue-900">→</span>
                        </a>
                    @endforeach
                </div>

                <a href="{{ route('citizen.documents') }}" class="{{ $link }} mt-4 inline-block">Go to my documents</a>
            </section>

            <section class="{{ $card }}">
                <h2 class="{{ $heading }}">Announcements and notices</h2>

                {{-- Pass $announcements from the controller: [['title' => '...', 'date' => '...'], ...] --}}
                <ul class="mt-3 divide-y divide-slate-200">
                    @forelse ($announcements ?? [] as $item)
                        <li class="py-3">
                            <p class="text-sm font-medium text-blue-950">{{ $item['title'] }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $item['date'] }}</p>
                        </li>
                    @empty
                        <li class="py-3 text-sm text-slate-500">No announcements right now. Check back later.</li>
                    @endforelse
                </ul>
            </section>
        </div>

        {{-- Profile and support --}}
        <div class="grid gap-6 md:grid-cols-2">

            <section class="{{ $card }}">
                <div class="flex items-center justify-between">
                    <h2 class="{{ $heading }}">Your profile</h2>
                    <a href="{{ route('citizen.profile') }}" class="{{ $link }}">Open profile</a>
                </div>

                <dl class="mt-4 divide-y divide-slate-200 text-sm">
                    @foreach ($details as [$name, $value])
                        <div class="py-3">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $name }}</dt>
                            <dd class="mt-1 font-medium text-blue-950">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section class="{{ $card }}">
                <h2 class="{{ $heading }}">Citizen support</h2>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Visit your barangay office for help with requests, requirements, fees, and profile changes.
                </p>
                <div class="mt-4 flex flex-wrap gap-4">
                    <a href="{{ route('citizen.identity-verification') }}" class="{{ $link }}">Identity verification guide</a>
                    <a href="{{ route('privacy') }}" class="{{ $link }}">Privacy notice</a>
                </div>
            </section>
        </div>
    </div>
@endsection