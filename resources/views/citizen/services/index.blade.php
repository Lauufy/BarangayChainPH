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

        // Theme tokens (follow the light / dark toggle)
        $ink = 'text-(--gray-900) dark:text-(--gray-50)';
        $body = 'text-(--gray-600) dark:text-(--gray-300)';
        $muted = 'text-(--gray-500) dark:text-(--gray-400)';
        $link = 'font-semibold text-(--blue-700) underline dark:text-(--blue-300)';
        $btn = 'inline-flex items-center justify-center rounded-md bg-(--blue-600) px-4 py-2 text-sm font-semibold text-(--white) transition hover:bg-(--blue-700) focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-(--blue-600) focus-visible:ring-offset-2';
    @endphp

    <div class="space-y-6">

        {{-- Header --}}
        <header class="space-y-2 border-b border-(--gray-200) pb-4 dark:border-(--gray-700)">
            <nav class="{{ $muted }} flex items-center gap-2 text-xs" aria-label="Breadcrumb">
                <a href="{{ route('citizen.dashboard') }}" class="hover:text-(--blue-700) dark:hover:text-(--blue-300)">Home</a>
                <span aria-hidden="true">›</span>
                <span class="font-semibold text-(--blue-700) dark:text-(--blue-300)">Services</span>
            </nav>

            <h1 class="text-2xl font-bold tracking-tight text-(--blue-900) dark:text-(--blue-300) sm:text-3xl">Barangay services</h1>
            <p class="{{ $body }} text-sm">Explore common barangay services. Verify your identity before submitting an online request.</p>
        </header>

        @unless ($verified)
            <x-partials.citizen-notice type="amber" title="Identity verification required">
                @if ($identityStatus === 'pending')
                    Your application is being reviewed. You can request services once it is approved.
                @else
                    You must verify your identity before you can request any barangay service.
                @endif

                <a href="{{ route('citizen.identity-verification') }}" class="{{ $btn }} mt-3 block w-fit">
                    {{ $identityStatus === 'pending' ? 'View status' : 'Verify account' }}
                </a>
            </x-partials.citizen-notice>
        @endunless

        {{-- Services come from config/barangay_services.php (the key is the URL slug) --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach (config('barangay_services.services') as $slug => $service)
                @php $hasPending = in_array($service['type'], $pendingServices, true); @endphp

                <article @class([
                    'rounded-md border border-(--gray-200) bg-(--white) p-5 dark:border-(--gray-700) dark:bg-(--gray-800)',
                    'opacity-60' => ! $verified || $hasPending,
                ])>
                    <svg class="h-7 w-7 text-(--blue-600) dark:text-(--blue-300)" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <path d="{{ $service['icon'] }}" />
                    </svg>

                    <h2 class="{{ $ink }} mt-3 font-bold">{{ $service['title'] }}</h2>
                    <p class="{{ $body }} mt-2 min-h-12 text-sm leading-6">{{ $service['description'] }}</p>

                    @if (! $verified)
                        <span class="mt-4 inline-flex rounded bg-(--gray-100) px-2.5 py-1 text-xs font-semibold text-(--gray-600) dark:bg-(--gray-700) dark:text-(--gray-300)">
                            Verification required
                        </span>
                    @elseif ($hasPending)
                        <button type="button" disabled
                                class="mt-4 inline-flex cursor-not-allowed items-center justify-center rounded-md bg-(--gray-200) px-4 py-2 text-sm font-semibold text-(--gray-500) dark:bg-(--gray-700) dark:text-(--gray-400)">
                            Request pending
                        </button>
                        <a href="{{ route('citizen.applications') }}" class="{{ $link }} mt-2 block text-xs">
                            View my applications
                        </a>
                    @else
                        <a href="{{ route('citizen.'.$slug) }}" class="{{ $btn }} mt-4">
                            Request service
                        </a>
                    @endif
                </article>
            @endforeach
        </div>

        <x-partials.citizen-notice title="Need help with a request?">
            Contact your barangay office to confirm current requirements, fees, and processing times.

            <a href="{{ route('citizen.applications') }}" class="{{ $link }} mt-2 block">
                View my applications
            </a>
        </x-partials.citizen-notice>
    </div>
@endsection