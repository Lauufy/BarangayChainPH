@extends('layouts.citizen')

@use('App\Models\ServiceRequest')

@section('title', 'My Documents')

@section('content')
    @php
        $heads = ['Reference', 'Document', 'Approved', 'Action'];
        $button = 'inline-flex rounded-lg px-3 py-1.5 text-xs font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2';
        $outline = $button . ' border border-slate-300 text-[#17325c] hover:border-[#17325c] hover:bg-slate-50';
    @endphp

    <div class="space-y-6">
        <section>
            <h1 class="text-2xl font-bold tracking-tight text-[#09172D] sm:text-3xl">My documents</h1>
            <p class="mt-1.5 max-w-2xl text-sm leading-6 text-slate-600">
                View barangay documents issued to you and their verification details.
            </p>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <h2 class="font-semibold text-[#09172D]">Issued documents</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Approved service requests are available here as issued documents.
                </p>
            </div>

            @if ($documents->isEmpty())
                <div class="px-5 py-14 text-center sm:px-6">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#17325c]/10 text-[#17325c]">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                            aria-hidden="true">
                            <path d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" />
                            <path d="M14 3v5h5M9 13h6M9 17h6" />
                        </svg>
                    </span>
                    <h3 class="mt-4 font-semibold text-[#09172D]">No documents to show</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
                        Approved documents will appear here once your service request has been reviewed.
                    </p>
                    <a href="{{ route('citizen.services') }}"
                        class="mt-5 inline-flex rounded-lg bg-[#17325c] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#10244a]">
                        Browse services
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-2xl w-full text-left text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50 text-slate-600">
                            <tr>
                                @foreach ($heads as $head)
                                    <th class="px-5 py-3 font-semibold">{{ $head }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($documents as $document)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="whitespace-nowrap px-5 py-4 font-semibold text-slate-900">
                                        {{ $document->reference_number }}</td>
                                    <td class="px-5 py-4 text-slate-700">
                                        {{ match ($document->service_type) {
                                            ServiceRequest::BARANGAY_CLEARANCE => 'Barangay clearance',
                                            ServiceRequest::CERTIFICATE_OF_RESIDENCY => 'Certificate of residency',
                                            default => 'Certificate of indigency',
                                        } }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                        {{ $document->updated_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <a href="{{ route('citizen.applications.show', $document) }}"
                                                class="{{ $outline }}">View</a>

                                            @if ($document->ticket)
                                                <a href="{{ route('citizen.tickets.show', $document) }}"
                                                    class="{{ $outline }}">Messages</a>
                                            @endif

                                            @if ($document->canPrint())
                                                <a href="{{ route('citizen.applications.print', $document) }}"
                                                    class="{{ $button }} bg-[#17325c] text-white hover:bg-[#10244a]">Print</a>
                                            @else
                                                <span class="text-xs font-medium text-slate-400">Print limit reached</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($documents->hasPages())
                    <div class="border-t border-slate-200 p-4">{{ $documents->links() }}</div>
                @endif
            @endif
        </section>
    </div>
@endsection