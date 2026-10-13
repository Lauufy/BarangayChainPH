@extends('layouts.citizen')

@section('title', 'Barangay Services')

@section('content')
    @php
        $verified = auth()->user()->isIdentityVerified();
        $status = auth()->user()->identityStatus();
    @endphp

    <div class="space-y-6">
        <x-partials.citizen-page-header title="Barangay services"
            text="Common services from your barangay. Online requests are not enabled yet; please contact the barangay office to apply." />

        @unless ($verified)
            <x-partials.citizen-notice type="amber" title="Identity verification required">
                {{ $status === 'pending'
                    ? 'Your application is being reviewed. You can request services once it is approved.'
                    : 'You must verify your identity before you can request any barangay service.' }}
                <a href="{{ route('citizen.identity-verification') }}" class="mt-3 block w-fit rounded-md bg-[#17325c] px-4 py-2 font-semibold text-white hover:bg-[#10244a]">
                    {{ $status === 'pending' ? 'View status' : 'Verify account' }}
                </a>
            </x-partials.citizen-notice>
        @endunless

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ([
                ['Barangay clearance', 'Request a clearance for employment, business, or other official purposes.', 'M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 0 1 1 1v13H4V6a1 1 0 0 1 1-1Z'],
                ['Certificate of residency', 'Request proof of residency within the barangay.', 'M3 10.5 12 3l9 7.5M5 9v11h14V9M9 20v-6h6v6'],
                ['Certificate of indigency', 'Ask the barangay about eligibility and requirements for this certificate.', 'M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2ZM14 3v5h5M9 13h6M9 17h6'],
            ] as [$title, $description, $icon])
                <article @class(['rounded-md border border-slate-200 bg-white p-5', 'opacity-60' => ! $verified])>
                    <svg class="h-7 w-7 text-[#17325c]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="{{ $icon }}"/></svg>
                    <h2 class="mt-3 font-bold text-slate-900">{{ $title }}</h2>
                    <p class="mt-2 min-h-12 text-sm leading-6 text-slate-600">{{ $description }}</p>
                    <span @class([
                        'mt-4 inline-flex rounded px-2.5 py-1 text-xs font-semibold',
                        'bg-amber-50 text-amber-800' => $verified,
                        'bg-slate-100 text-slate-600' => ! $verified,
                    ])>{{ $verified ? 'In-person request' : 'Verification required' }}</span>
                </article>
            @endforeach
        </div>

        <x-partials.citizen-notice title="Need help with a request?">
            Visit or contact your barangay office to confirm current requirements, fees, and processing times.
            <a href="{{ route('citizen.applications') }}" class="mt-2 block font-semibold underline">View my applications</a>
        </x-partials.citizen-notice>
    </div>
@endsection