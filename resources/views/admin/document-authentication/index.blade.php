@extends('layouts.admin')

@section('title', 'Document Authentication')

@section('content')
    <div class="admin-module space-y-5">
        {{-- Explain the review queue and summarize the number of authentication records. --}}
        <x-admin.page-header title="Document Authentication"
            text="Review document verification records and authentication results." />

        {{-- Totals for submitted, pending, and authenticated documents. --}}
        <div class="grid grid-cols-2 overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900 lg:grid-cols-3">
            <x-admin.stat-card label="Documents Submitted" subtitle="All issued documents" :value="number_format($submitted ?? 0)" :latest="$submittedLatest ?? null"
                latest-label="Last issued">
                <x-slot:icon>
                    {{-- Lucide: file-text --}}
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
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
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
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
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path
                            d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                        <path d="m9 12 2 2 4-4" />
                    </svg>
                </x-slot:icon>
            </x-admin.stat-card>
        </div>

        {{-- Document directory --}}
        <x-tables.card :paginator="$documents" :searchable="false" item-label="documents">
            <x-tables.toolbar key="document-authentication" total-label="All Documents"
                :total="number_format($totalDocuments ?? 0)" :search="true"
                search-placeholder="Search by permit number or name" :filters="[[
                    'name' => 'status',
                    'label' => 'Status',
                    'title' => 'Authentication status',
                    'allLabel' => 'All statuses',
                    'options' => ['active' => 'Active', 'replaced' => 'Replaced'],
                ], [
                    'name' => 'date',
                    'label' => 'Date',
                    'title' => 'Date issued',
                    'allLabel' => 'All dates',
                    'options' => ['today' => 'Today', '7days' => 'Last 7 days', '30days' => 'Last 30 days', 'year' => 'This year'],
                ]]" />

            <div class="account-directory">
                <x-tables.table :heads="['Submitted By', 'Document Type', 'Authentication Status', 'Date Issued']"
                    :empty="$documents->isEmpty()" message="No documents found.">
                    @foreach ($documents as $document)
                        <tr>
                            <td data-title>
                                <x-tables.user-cell :name="$document->serviceRequest->applicant_name"
                                    :sub="$document->document_number . ' · v' . $document->version" />
                            </td>
                            <td data-label="Document type">{{ $document->serviceRequest->serviceTitle() }}</td>
                            <td data-side="right">
                                <x-tables.badge :variant="$document->revoked_at ? 'neutral' : 'success'"
                                    class="gap-1.5 rounded-full!">
                                    <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
                                    {{ $document->revoked_at ? 'Replaced' : 'Active' }}
                                </x-tables.badge>
                            </td>
                            <td data-label="Date issued">
                                {{ $document->issued_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') ?? 'N/A' }}
                            </td>
                        </tr>
                    @endforeach
                </x-tables.table>
            </div>
        </x-tables.card>

    </div>
@endsection