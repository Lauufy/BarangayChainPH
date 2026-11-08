@extends('layouts.citizen')

@use('App\Models\ServiceRequest')

@section('title', 'My Documents')

@section('content')
    @php
        $heads = ['Reference', 'Document', 'Approved', 'Action'];
        $button = 'inline-flex rounded-lg px-3 py-1.5 text-xs font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2';
        $outline = $button . ' border border-slate-300 text-[#17325c] hover:border-[#17325c] hover:bg-slate-50 dark:border-slate-600 dark:text-blue-200 dark:hover:border-blue-300 dark:hover:bg-slate-800';
        $primary = $button . ' bg-[#17325c] text-white hover:bg-[#10244a] dark:bg-blue-600 dark:hover:bg-blue-500';
    @endphp

    <div class="space-y-6">
        {{-- Header --}}
        <header class="space-y-2 border-b border-(--gray-200) pb-4 dark:border-(--gray-700)">
            <nav class="flex items-center gap-2 text-xs text-(--gray-500) dark:text-(--gray-400)" aria-label="Breadcrumb">
                <a href="{{ route('citizen.services') }}" class="hover:text-(--blue-700) dark:hover:text-(--blue-300)">Services</a>
                <span aria-hidden="true">›</span>
                <span class="font-semibold text-(--blue-700) dark:text-(--blue-300)">My documents</span>
            </nav>

            <div>
                <h1 class="text-2xl font-bold tracking-tight text-(--blue-900) sm:text-3xl dark:text-(--blue-300)">My documents</h1>
                <p class="mt-1 text-sm text-(--gray-600) dark:text-(--gray-300)">View barangay documents issued to you and their verification details.</p>
            </div>
        </header>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:shadow-none">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6 dark:border-slate-700">
                <h2 class="font-semibold text-[#09172D] dark:text-slate-100">Issued documents</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Approved service requests are available here as issued documents.
                </p>
            </div>

            @if ($documents->isEmpty())
                <div class="px-5 py-14 text-center sm:px-6">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#17325c]/10 text-[#17325c] dark:bg-blue-300/10 dark:text-blue-300">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                            aria-hidden="true">
                            <path d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" />
                            <path d="M14 3v5h5M9 13h6M9 17h6" />
                        </svg>
                    </span>
                    <h3 class="mt-4 font-semibold text-[#09172D] dark:text-slate-100">No documents to show</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500 dark:text-slate-400">
                        Approved documents will appear here once your service request has been reviewed.
                    </p>
                    <a href="{{ route('citizen.services') }}"
                        class="mt-5 inline-flex rounded-lg bg-[#17325c] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#10244a] dark:bg-blue-600 dark:hover:bg-blue-500">
                        Browse services
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-2xl w-full text-left text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            <tr>
                                @foreach ($heads as $head)
                                    <th class="px-5 py-3 font-semibold">{{ $head }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($documents as $document)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/60">
                                    <td class="whitespace-nowrap px-5 py-4 font-semibold text-slate-900 dark:text-slate-100">
                                        {{ $document->reference_number }}</td>
                                    <td class="px-5 py-4 text-slate-700 dark:text-slate-300">
                                        {{ match ($document->service_type) {
                                            ServiceRequest::BARANGAY_CLEARANCE => 'Barangay clearance',
                                            ServiceRequest::CERTIFICATE_OF_RESIDENCY => 'Certificate of residency',
                                            default => 'Certificate of indigency',
                                        } }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600 dark:text-slate-400">
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
                                                    class="{{ $primary }}">Print</a>
                                            @else
                                                <span class="text-xs font-medium text-slate-400 dark:text-slate-500">Print limit reached</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($documents->hasPages())
                    <div class="border-t border-slate-200 p-4 dark:border-slate-700">{{ $documents->links() }}</div>
                @endif
            @endif
        </section>
    </div>
@endsection