@extends('layouts.citizen')

@section('title', $title)

@section('content')
    <div class="space-y-6">
        <a href="{{ route('citizen.services') }}"
            class="inline-flex items-center gap-2 text-sm font-semibold text-[#17325c] transition hover:text-[#10244a] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#17325c] focus-visible:ring-offset-2">
            <span aria-hidden="true">←</span>
            Back to services
        </a>

        <section class="rounded-md border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-700">Barangay service</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-[#09172D] sm:text-3xl">{{ $title }}</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">{{ $description }}</p>

            @if (($isIndigency ?? false) || ($isClearance ?? false) || ($isResidency ?? false))
                @php($type = $isClearance ?? false ? 'barangay-clearance' : ($isResidency ?? false ? 'certificate-of-residency' : 'certificate-of-indigency'))
                <form method="POST" action="{{ route("citizen.{$type}.preview") }}" class="mt-8 space-y-6"
                    aria-label="{{ $title }} form">
                    @csrf
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-form.input light placeholder=" " name="name" label="Full name" :value="old('name', auth()->user()->name)" required />
                        <x-form.input light placeholder=" " name="age" label="Age" type="number" :value="old('age', $profile?->age())"
                            required />
                    </div>

                    <x-form.input light placeholder=" " name="address" label="Address" :value="old('address', $profile?->address)" required />

                    <x-form.input light placeholder=" " name="purpose" label="Purpose" :value="old('purpose')" required />

                    <div class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                        Please review the prefilled details and provide the purpose of your request. Visit or contact your
                        barangay office to confirm the current requirements, fees, and processing time.
                    </div>

                    <button type="submit"
                        class="rounded-md bg-[#17325c] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#10244a]">
                        Preview {{ strtolower($title) }}
                    </button>
                </form>
            @else
                <div class="mt-6 rounded-md border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                    Online requests are being prepared. Visit or contact your barangay office to confirm the current
                    requirements, fees, and processing time for this service.
                </div>
            @endif
        </section>
    </div>
@endsection
