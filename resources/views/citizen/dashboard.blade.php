@extends('layouts.citizen')

@section('title', 'Citizen Dashboard')

@section('content')
    @php
        $profile = auth()->user()->citizenProfile;
        $addressParts = array_filter([
            $profile?->house_number,
            $profile?->street,
            $profile?->barangay,
            $profile?->city_municipality,
            $profile?->province,
        ]);
    @endphp

    <section class="relative overflow-hidden rounded-4xl border border-emerald-900/5 bg-linear-to-br from-[#e7f1e7] via-[#f1f5e9] to-[#f8f5e8] px-6 py-8 sm:px-10 sm:py-10 lg:px-12 lg:py-12">
        <div class="pointer-events-none absolute -right-20 -top-28 h-80 w-80 rounded-full border border-emerald-900/5"></div>
        <div class="pointer-events-none absolute -right-10 -top-16 h-60 w-60 rounded-full border border-emerald-900/5"></div>
        <div class="relative grid items-center gap-9 lg:grid-cols-[1.2fr_0.8fr] lg:gap-12">
            <div class="max-w-2xl">
                <p class="inline-flex items-center gap-2 rounded-full border border-emerald-900/10 bg-white/70 px-3 py-1.5 text-xs font-bold uppercase tracking-[0.12em] text-emerald-900">
                    <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                    Barangay San Luis · Antipolo City
                </p>
                <h1 class="mt-5 text-3xl font-extrabold leading-tight tracking-tight text-[#173a2e] sm:text-4xl lg:text-5xl">Maasikaso at maaasahang serbisyo, para sa bawat San Luiseño.</h1>
                <p class="mt-4 max-w-xl text-sm leading-7 text-slate-700 sm:text-base">Welcome to your community portal. Find barangay services, get guidance on identity verification, and keep your account information close at hand.</p>
                <div class="mt-7 flex flex-wrap gap-3">
                    <a href="{{ route('citizen.services') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#176344] px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#124f36]">
                        Explore barangay services
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    </a>
                    <a href="{{ route('citizen.applications') }}" class="inline-flex items-center justify-center rounded-xl border border-emerald-900/15 bg-white/75 px-5 py-3 text-sm font-bold text-emerald-950 transition hover:bg-white">My applications</a>
                </div>
            </div>

            <aside class="relative rounded-2xl border border-white/80 bg-white/85 p-5 shadow-[0_20px_60px_-35px_rgba(23,58,46,0.55)] backdrop-blur sm:p-6">
                <div class="flex items-center gap-4 border-b border-slate-100 pb-5">
                    <img src="{{ asset('img/branding/barangayChainPH-logo.png') }}" alt="" class="h-14 w-14 rounded-full border border-slate-100 object-contain p-0.5">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.13em] text-emerald-800">Citizen services</p>
                        <h2 class="mt-1 text-lg font-bold text-[#173a2e]">How can we help?</h2>
                    </div>
                </div>
                <nav aria-label="Quick links" class="mt-2 divide-y divide-slate-100">
                    <a href="{{ route('citizen.services') }}" class="group flex items-center justify-between gap-4 py-3.5">
                        <span><span class="block text-sm font-semibold text-slate-800 group-hover:text-emerald-800">Barangay services</span><span class="mt-1 block text-xs text-slate-500">Explore certificates and clearances</span></span>
                        <span class="text-lg text-emerald-700 transition group-hover:translate-x-1" aria-hidden="true">→</span>
                    </a>
                    <a href="{{ route('citizen.identity-verification') }}" class="group flex items-center justify-between gap-4 py-3.5">
                        <span><span class="block text-sm font-semibold text-slate-800 group-hover:text-emerald-800">Identity verification</span><span class="mt-1 block text-xs text-slate-500">Learn about in-person verification</span></span>
                        <span class="text-lg text-emerald-700 transition group-hover:translate-x-1" aria-hidden="true">→</span>
                    </a>
                    <a href="{{ route('citizen.profile') }}" class="group flex items-center justify-between gap-4 py-3.5">
                        <span><span class="block text-sm font-semibold text-slate-800 group-hover:text-emerald-800">Your citizen profile</span><span class="mt-1 block text-xs text-slate-500">Review your account details</span></span>
                        <span class="text-lg text-emerald-700 transition group-hover:translate-x-1" aria-hidden="true">→</span>
                    </a>
                </nav>
            </aside>
        </div>
    </section>

    <section id="services" class="mt-12 scroll-mt-24 sm:mt-14">
        <div class="mb-5 flex items-end justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-800">Online and in person</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-[#173a2e]">Services for the community</h2>
            </div>
            <span class="hidden text-xs text-slate-500 sm:block">Explore common services and how to request them</span>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <article id="requests" class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:p-6">
                <div class="flex items-start justify-between">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 0 1 1 1v13H4V6a1 1 0 0 1 1-1Z"/><path d="M8 15h3"/></svg>
                    </span>
                </div>
                <h3 class="mt-5 font-bold text-[#09172D]">Permits &amp; clearances</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Explore barangay certificates and learn how to request them in person.</p>
                <a href="{{ route('citizen.services') }}" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-emerald-800 hover:text-emerald-900">Browse services <span aria-hidden="true">→</span></a>
            </article>

            <article id="identity" class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:p-6">
                <div class="flex items-start justify-between">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 3 5 6v5c0 4.5 3 7.5 7 10 4-2.5 7-5.5 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/></svg>
                    </span>
                </div>
                <h3 class="mt-5 font-bold text-[#09172D]">Digital identity</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Learn about the current in-person identity-verification process.</p>
                <a href="{{ route('citizen.identity-verification') }}" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-emerald-800 hover:text-emerald-900">View identity verification <span aria-hidden="true">→</span></a>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:p-6">
                <div class="flex items-start justify-between">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-50 text-violet-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path d="M14 3v5h5M9 13h6M9 17h6"/></svg>
                    </span>
                </div>
                <h3 class="mt-5 font-bold text-[#09172D]">My documents</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Review how to request barangay documents and where issued records will appear.</p>
                <a href="{{ route('citizen.documents') }}" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-emerald-800 hover:text-emerald-900">Open documents <span aria-hidden="true">→</span></a>
            </article>
        </div>
    </section>

    <section id="profile" class="mt-12 scroll-mt-24 sm:mt-14">
        <div class="mb-4">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-700">Your account</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-[#173a2e]">Your citizen profile</h2>
        </div>
        <div class="grid gap-4 lg:grid-cols-[1.15fr_0.85fr]">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-start gap-4">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-lg font-bold text-emerald-700">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    <div class="min-w-0">
                        <h3 class="font-bold text-[#09172D]">{{ auth()->user()->name }}</h3>
                        <p class="mt-1 break-all text-sm text-slate-500">{{ auth()->user()->email }}</p>
                    </div>
                </div>
                <dl class="mt-6 grid gap-4 border-t border-slate-100 pt-5 sm:grid-cols-2">
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Phone</dt><dd class="mt-1 text-sm font-medium text-slate-800">{{ $profile?->phone ?: 'Not added' }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Date of birth</dt><dd class="mt-1 text-sm font-medium text-slate-800">{{ $profile?->date_of_birth?->format('M j, Y') ?? 'Not added' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Address</dt><dd class="mt-1 text-sm font-medium text-slate-800">{{ $addressParts ? implode(', ', $addressParts) : 'Add your address to complete your profile' }}</dd></div>
                </dl>
                <p class="mt-5 rounded-xl bg-slate-50 px-4 py-3 text-xs leading-5 text-slate-500">Need to update your information? Contact your barangay office for profile assistance.</p>
            </article>

            <aside class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-5 sm:p-6">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-emerald-700 shadow-sm">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 3 5 6v5c0 4.5 3 7.5 7 10 4-2.5 7-5.5 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/></svg>
                </div>
                <h3 class="mt-4 font-bold text-[#09172D]">Your information stays protected</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Your portal is tied to your account. Sign out when you finish using a shared device.</p>
                <a href="{{ route('privacy') }}" class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-emerald-800 hover:text-emerald-900">Read our privacy notice <span aria-hidden="true">→</span></a>
            </aside>
        </div>
    </section>
@endsection
