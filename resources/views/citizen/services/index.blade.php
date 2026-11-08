@extends('layouts.citizen')

@use('App\Models\ServiceRequest')

@section('title', 'Barangay Services')

@section('content')
    @php
        $user = auth()->user();
        $verified = $user->isIdentityVerified();
        $identityStatus = $user->identityStatus();

        // Services the user already has an open request for
        $pendingServices = ServiceRequest::query()->whereBelongsTo($user)
            ->whereIn('status', [ServiceRequest::PENDING, ServiceRequest::PROCESSING])
            ->pluck('service_type')
            ->all();
    @endphp

    <div class="space-y-6">
        <x-partials.citizen-page-header
            title="Barangay services"
            text="Explore common barangay services. Verify your identity before submitting an online request."
        />

        @unless ($verified)
            <x-partials.citizen-notice type="amber" title="Identity verification required">
                @if ($identityStatus === 'pending')
                    Your application is being reviewed. You can request services once it is approved.
                @else
                    You must verify your identity before you can request any barangay service.
                @endif

                <a href="{{ route('citizen.identity-verification') }}"
                   class="mt-3 block w-fit rounded-md bg-[#17325c] px-4 py-2 font-semibold text-white hover:bg-[#10244a]">
                    {{ $identityStatus === 'pending' ? 'View status' : 'Verify account' }}
                </a>
            </x-partials.citizen-notice>
        @endunless

        {{-- Services come from config/barangay_services.php (the key is the URL slug) --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach (config('barangay_services.services') as $slug => $service)
                @php $hasPending = in_array($service['type'], $pendingServices, true); @endphp

                <article @class([
                    'rounded-md border border-slate-200 bg-white p-5',
                    'opacity-60' => ! $verified || $hasPending,
                ])>
                    <svg class="h-7 w-7 text-[#17325c]" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <path d="{{ $service['icon'] }}" />
                    </svg>

                    <h2 class="mt-3 font-bold text-slate-900">{{ $service['title'] }}</h2>
                    <p class="mt-2 min-h-12 text-sm leading-6 text-slate-600">{{ $service['description'] }}</p>

                    @if (! $verified)
                        <span class="mt-4 inline-flex rounded bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                            Verification required
                        </span>
                    @elseif ($hasPending)
                        <button type="button" disabled
                                class="mt-4 inline-flex cursor-not-allowed items-center justify-center rounded-md bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-500">
                            Request pending
                        </button>
                        <a href="{{ route('citizen.applications') }}"
                           class="mt-2 block text-xs font-semibold text-[#17325c] underline">
                            View my applications
                        </a>
                    @else
                        <a href="{{ route('citizen.'.$slug) }}"
                           class="mt-4 inline-flex items-center justify-center rounded-md bg-[#17325c] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#10244a] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#17325c] focus-visible:ring-offset-2">
                            Request service
                        </a>
                    @endif
                </article>
            @endforeach
        </div>

        <x-partials.citizen-notice title="Need help with a request?">
            Contact your barangay office to confirm current requirements, fees, and processing times.

            <a href="{{ route('citizen.applications') }}" class="mt-2 block font-semibold underline">
                View my applications
            </a>
        </x-partials.citizen-notice>
    </div>
@endsection