@extends('layouts.citizen')

@section('title', 'Print '.$documentTitle)

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <div class="flex items-center justify-between print:hidden">
            <a href="{{ route('citizen.applications') }}"
                class="inline-flex items-center gap-2 text-sm font-semibold text-[#17325c] hover:text-[#10244a]">
                <span aria-hidden="true">&larr;</span>
                Back to applications
            </a>
            <button type="button" onclick="window.print()"
                class="rounded-md bg-[#17325c] px-4 py-2 text-sm font-semibold text-white hover:bg-[#10244a]">
                Print certificate
            </button>
        </div>

        <section class="rounded-md border border-slate-200 bg-white p-6 shadow-sm sm:p-10 print:border-0 print:p-0 print:shadow-none">
            <div class="text-center">
                <p class="text-sm font-semibold uppercase tracking-wide">Republic of the Philippines</p>
                <p class="text-sm">Barangay San Luis, Antipolo City, Rizal</p>
                <h1 class="mt-8 text-xl font-bold uppercase tracking-wide sm:text-2xl">{{ $documentTitle }}</h1>
                <p class="mt-2 text-xs text-slate-500">Date issued: {{ now()->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</p>
                <p class="mt-2 text-xs text-slate-500">Permit number: {{ $application->permit_number }}</p>
                <p class="text-xs text-slate-500">Reference: {{ $application->reference_number }}</p>
            </div>

            <div class="mt-10 space-y-6 text-sm leading-7 text-slate-700">
                <p>To whom it may concern:</p>
                <p>
                    This is to certify that <strong>{{ $application->applicant_name }}</strong>, {{ $application->age }} years old,
                    and a resident of <strong>{{ $application->address }}</strong>, is requesting a {{ $documentTitle }}
                    for the following purpose:
                </p>
                <p class="min-h-20 border-b border-slate-400 pb-2 font-semibold">{{ $application->purpose }}</p>
                <p>
                    This certification is issued upon the request of the interested party for whatever legal purpose it may serve.
                </p>
            </div>

            <div class="mt-16 text-center text-sm">
                <img src="{{ $qrCode }}" alt="Scan to verify permit authenticity" class="mx-auto h-24 w-24">
                <p class="mt-2 text-xs text-slate-500">Scan to verify authenticity</p>
                <p class="font-semibold">Barangay Official</p>
                <p class="mt-10 border-t border-slate-400 pt-2">Authorized signature</p>
            </div>
        </section>
    </div>
@endsection
