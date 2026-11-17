@extends('layouts.admin')

@section('title', 'Document Authentication')

@section('content')
    <div class="space-y-6">
        {{-- Explain the review queue and summarize the number of authentication records. --}}
        <x-admin.page-header label="Document Security" title="Document Authentication"
            text="Review document verification records and authentication results." :export-url="route('admin.exports.show', ['module' => 'document-authentication'])" />

        {{-- Totals for submitted, pending, and authenticated documents. --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <x-admin.stat-card label="Documents Submitted" subtitle="All issued documents" :value="number_format($submitted ?? 0)" :latest="$submittedLatest ?? null"
                latest-label="Last issued">
                <x-slot:icon>
                    {{-- Lucide: file-text --}}
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
                        <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                        <path d="M10 9H8" />
                        <path d="M16 13H8" />
                        <path d="M16 17H8" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>

            <x-admin.stat-card label="Pending Review" subtitle="Awaiting authentication" :value="number_format($pending ?? 0)" :invert="true"
                :latest="$pendingLatest ?? null" latest-label="Oldest waiting">
                <x-slot:icon>
                    {{-- Lucide: clock --}}
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>

            <x-admin.stat-card label="Authenticated" subtitle="Verified and active" :value="number_format($authenticated ?? 0)" :latest="$authenticatedLatest ?? null"
                latest-label="Last authenticated">
                <x-slot:icon>
                    {{-- Lucide: shield-check --}}
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path
                            d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                        <path d="m9 12 2 2 4-4" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>
        </div>

        {{-- Search and paginate issued document records. --}}
        <x-tables.card title="Authentication Records" description="Track issued documents and their authentication status."
            :paginator="$documents" :searchable="false" item-label="documents">
            <x-slot:toolbar>
                <x-tables.toolbar key="document-authentication" :search="true"
                    search-placeholder="Search by permit number or name" class="gap-0! pb-0! sm:pb-0! justify-end" />
            </x-slot:toolbar>

            <x-tables.table :heads="['Document Reference', 'Document Type', 'Submitted By', 'Authentication Status', 'Date']" :empty="$documents->isEmpty()" message="No authenticated documents yet.">
                @foreach ($documents as $document)
                    <tr>
                        <td class="font-medium text-slate-900">
                            {{ $document->document_number }}
                            <span class="text-xs font-normal text-slate-500">v{{ $document->version }}</span>
                        </td>

                        <td>{{ $document->serviceRequest->serviceTitle() }}</td>

                        <td>{{ $document->serviceRequest->applicant_name }}</td>

                        <td>
                            @if ($document->revoked_at)
                                <x-tables.badge variant="neutral">Replaced</x-tables.badge>
                            @else
                                <x-tables.badge variant="success">Active</x-tables.badge>
                            @endif
                        </td>

                        <td class="whitespace-nowrap">
                            {{ $document->issued_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') ?? '—' }}
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        </x-tables.card>

    </div>
@endsection
