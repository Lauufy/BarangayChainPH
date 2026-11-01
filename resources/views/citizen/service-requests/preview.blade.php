@extends('layouts.citizen')

@section('title', 'Preview '.$documentTitle)

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <a href="{{ route(match ($type) {
            'clearance' => 'citizen.barangay-clearance',
            'residency' => 'citizen.certificate-of-residency',
            default => 'citizen.certificate-of-indigency',
        }) }}"
            class="inline-flex items-center gap-2 text-sm font-semibold text-[#17325c] hover:text-[#10244a]">
            <span aria-hidden="true">←</span>
            Back to form
        </a>

        <section class="rounded-md border border-slate-200 bg-white p-6 shadow-sm sm:p-10 print:border-0 print:p-0 print:shadow-none">
            <div class="text-center">
                <p class="text-sm font-semibold uppercase tracking-wide">Republic of the Philippines</p>
                <p class="text-sm">Barangay San Luis, Antipolo City, Rizal</p>
                <h1 class="mt-8 text-xl font-bold uppercase tracking-wide sm:text-2xl">{{ $documentTitle }}</h1>
            </div>

            <div class="mt-10 space-y-6 text-sm leading-7 text-slate-700">
                <p>To whom it may concern:</p>
                <p>
                    This is to certify that <strong>{{ $requestData['name'] }}</strong>, {{ $requestData['age'] }} years old,
                    and a resident of <strong>{{ $requestData['address'] }}</strong>, is requesting a {{ $documentTitle }}
                    for the following purpose:
                </p>
                <p class="min-h-20 border-b border-slate-400 pb-2 font-semibold">{{ $requestData['purpose'] }}</p>
                <p>
                    This certification is issued upon the request of the interested party for whatever legal purpose it may serve.
                </p>
            </div>

            <div class="mt-16 text-center text-sm">
                <p class="font-semibold">Barangay Official</p>
                <p class="mt-10 border-t border-slate-400 pt-2">Authorized signature</p>
            </div>
        </section>

        <div class="flex flex-col gap-3 sm:flex-row sm:justify-end print:hidden">
            <form method="POST" action="{{ route(match ($type) {
                'clearance' => 'citizen.barangay-clearance.modify',
                'residency' => 'citizen.certificate-of-residency.modify',
                default => 'citizen.certificate-of-indigency.modify',
            }) }}">
                @csrf
                <button type="submit"
                    class="w-full rounded-md bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-700 sm:w-auto">
                    Modify
                </button>
            </form>
            <form method="POST" action="{{ route(match ($type) {
                'clearance' => 'citizen.barangay-clearance.proceed',
                'residency' => 'citizen.certificate-of-residency.proceed',
                default => 'citizen.certificate-of-indigency.proceed',
            }) }}">
                @csrf
                <button type="submit"
                    class="w-full rounded-md bg-[#17325c] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#10244a] sm:w-auto">
                    Proceed
                </button>
            </form>
        </div>
    </div>
@endsection
