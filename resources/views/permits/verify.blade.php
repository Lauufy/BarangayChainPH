@extends('layouts.citizen')

@section('title', 'Verify Permit')

@section('content')
    <div class="mx-auto max-w-xl">
        <section class="rounded-md border p-6 shadow-sm sm:p-8 {{ $isGenuine ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50' }}">
            <p class="text-xs font-bold uppercase tracking-[0.16em] {{ $isGenuine ? 'text-emerald-700' : 'text-red-700' }}">
                Permit verification
            </p>
            <h1 class="mt-2 text-2xl font-bold {{ $isGenuine ? 'text-emerald-950' : 'text-red-950' }}">
                {{ $isGenuine ? 'Genuine permit' : 'Permit could not be verified' }}
            </h1>
            @if ($isGenuine)
                <p class="mt-3 text-sm text-emerald-800">This permit was issued by BarangayChain PH and is currently approved.</p>
                <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-emerald-700">Permit number</dt>
                        <dd class="mt-1 font-bold text-emerald-950">{{ $application->permit_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-emerald-700">Applicant</dt>
                        <dd class="mt-1 font-semibold text-emerald-950">{{ $application->applicant_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-emerald-700">Document</dt>
                        <dd class="mt-1 font-semibold text-emerald-950">
                            {{ match ($application->service_type) {
                                \App\Models\ServiceRequest::BARANGAY_CLEARANCE => 'Barangay clearance',
                                \App\Models\ServiceRequest::CERTIFICATE_OF_RESIDENCY => 'Certificate of residency',
                                default => 'Certificate of indigency',
                            } }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-emerald-700">Status</dt>
                        <dd class="mt-1 font-semibold capitalize text-emerald-950">{{ $application->status }}</dd>
                    </div>
                </dl>
            @else
                <p class="mt-3 text-sm text-red-800">This permit is not approved or its permit number is invalid.</p>
            @endif
        </section>
    </div>
@endsection
