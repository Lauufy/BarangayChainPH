@extends('layouts.citizen')

@section('title', 'My Documents')

@section('content')
    <div class="space-y-7">
        <section>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-700">Records</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-[#09172D] sm:text-3xl">My documents</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">View barangay documents issued to you and their verification details.</p>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <h2 class="font-semibold text-[#09172D]">Issued documents</h2>
                <p class="mt-1 text-sm text-slate-500">Approved service requests are available here as issued documents.</p>
            </div>
            @if ($documents->isEmpty())
                <div class="px-5 py-14 text-center sm:px-6">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path d="M14 3v5h5M9 13h6M9 17h6"/></svg>
                    </span>
                    <h3 class="mt-4 font-semibold text-[#09172D]">No documents to show</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Approved documents will appear here once your service request has been reviewed.</p>
                    <a href="{{ route('citizen.services') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-[#0B2347] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#123365]">Browse services <span aria-hidden="true">→</span></a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[42rem] text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Reference</th>
                                <th class="px-5 py-3 font-semibold">Document</th>
                                <th class="px-5 py-3 font-semibold">Approved</th>
                                <th class="px-5 py-3 font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($documents as $document)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-4 font-semibold text-slate-900">{{ $document->reference_number }}</td>
                                    <td class="px-5 py-4 text-slate-700">
                                        {{ match ($document->service_type) {
                                            \App\Models\ServiceRequest::BARANGAY_CLEARANCE => 'Barangay clearance',
                                            \App\Models\ServiceRequest::CERTIFICATE_OF_RESIDENCY => 'Certificate of residency',
                                            default => 'Certificate of indigency',
                                        } }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $document->updated_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <a href="{{ route('citizen.applications.show', $document) }}"
                                                class="inline-flex rounded-md border border-[#17325c] px-3 py-1.5 text-xs font-semibold text-[#17325c] hover:bg-slate-50">
                                                View
                                            </a>
                                            @if ($document->canPrint())
                                                <a href="{{ route('citizen.applications.print', $document) }}"
                                                    class="inline-flex rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">
                                                    Print
                                                </a>
                                            @else
                                                <span class="text-xs font-semibold text-slate-400">Print limit reached</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($documents->hasPages())
                    <div class="border-t border-slate-200 p-4">
                        {{ $documents->links() }}
                    </div>
                @endif
            @endif
        </section>
    </div>
@endsection
