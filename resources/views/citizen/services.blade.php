@extends('layouts.citizen')

@section('title', 'Barangay Services')

@section('content')
    <div class="space-y-6">
        <x-partials.citizen-page-header
            title="Barangay services"
            text="Explore common barangay services. Verify your identity before submitting an online request."
        />

        @unless (auth()->user()->isIdentityVerified())
            <x-partials.citizen-notice type="amber" title="Identity verification required">
                @if (auth()->user()->identityStatus() === 'pending')
                    Your application is being reviewed. You can request services once it is approved.
                @else
                    You must verify your identity before you can request any barangay service.
                @endif

                <a href="{{ route('citizen.identity-verification') }}"
                   class="mt-3 block w-fit rounded-md bg-[#17325c] px-4 py-2 font-semibold text-white hover:bg-[#10244a]">
                    @if (auth()->user()->identityStatus() === 'pending')
                        View status
                    @else
                        Verify account
                    @endif
                </a>
            </x-partials.citizen-notice>
        @endunless

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            {{-- Barangay clearance --}}
            <article @class([
                'rounded-md border border-slate-200 bg-white p-5',
                'opacity-60' => ! auth()->user()->isIdentityVerified(),
            ])>
                <svg class="h-7 w-7 text-[#17325c]" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <path d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 0 1 1 1v13H4V6a1 1 0 0 1 1-1Z" />
                </svg>

                <h2 class="mt-3 font-bold text-slate-900">Barangay clearance</h2>
                <p class="mt-2 min-h-12 text-sm leading-6 text-slate-600">Request a clearance for employment, business, or other official purposes.</p>

                @if (auth()->user()->isIdentityVerified())
                    <a href="{{ route('citizen.barangay-clearance') }}"
                       class="mt-4 inline-flex items-center justify-center rounded-md bg-[#17325c] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#10244a] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#17325c] focus-visible:ring-offset-2">
                        Request service
                    </a>
                @else
                    <span class="mt-4 inline-flex rounded bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                        Verification required
                    </span>
                @endif
            </article>

            {{-- Certificate of residency --}}
            <article @class([
                'rounded-md border border-slate-200 bg-white p-5',
                'opacity-60' => ! auth()->user()->isIdentityVerified(),
            ])>
                <svg class="h-7 w-7 text-[#17325c]" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <path d="M3 10.5 12 3l9 7.5M5 9v11h14V9M9 20v-6h6v6" />
                </svg>

                <h2 class="mt-3 font-bold text-slate-900">Certificate of residency</h2>
                <p class="mt-2 min-h-12 text-sm leading-6 text-slate-600">Request proof of residency within the barangay.</p>

                @if (auth()->user()->isIdentityVerified())
                    <a href="{{ route('citizen.certificate-of-residency') }}"
                       class="mt-4 inline-flex items-center justify-center rounded-md bg-[#17325c] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#10244a] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#17325c] focus-visible:ring-offset-2">
                        Request service
                    </a>
                @else
                    <span class="mt-4 inline-flex rounded bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                        Verification required
                    </span>
                @endif
            </article>

            {{-- Certificate of indigency --}}
            <article @class([
                'rounded-md border border-slate-200 bg-white p-5',
                'opacity-60' => ! auth()->user()->isIdentityVerified(),
            ])>
                <svg class="h-7 w-7 text-[#17325c]" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <path d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2ZM14 3v5h5M9 13h6M9 17h6" />
                </svg>

                <h2 class="mt-3 font-bold text-slate-900">Certificate of indigency</h2>
                <p class="mt-2 min-h-12 text-sm leading-6 text-slate-600">Ask the barangay about eligibility and requirements for this certificate.</p>

                @if (auth()->user()->isIdentityVerified())
                    <a href="{{ route('citizen.certificate-of-indigency') }}"
                       class="mt-4 inline-flex items-center justify-center rounded-md bg-[#17325c] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#10244a] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#17325c] focus-visible:ring-offset-2">
                        Request service
                    </a>
                @else
                    <span class="mt-4 inline-flex rounded bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                        Verification required
                    </span>
                @endif
            </article>
        </div>

        <x-partials.citizen-notice title="Need help with a request?">
            Contact your barangay office to confirm current requirements, fees, and processing times.

            <a href="{{ route('citizen.applications') }}" class="mt-2 block font-semibold underline">
                View my applications
            </a>
        </x-partials.citizen-notice>
    </div>
@endsection