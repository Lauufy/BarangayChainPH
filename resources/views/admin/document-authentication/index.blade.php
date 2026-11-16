@extends('layouts.admin')

@section('title', 'Document Authentication')

@section('content')
    <div class="space-y-6">
        {{-- Explain the review queue and summarize the number of authentication records. --}}
        <x-admin.page-header label="Document Security" title="Document Authentication"
            text="Review document verification records and authentication results."
            :export-url="route('admin.exports.show', ['module' => 'document-authentication'])" />

        {{-- Totals for submitted, pending, and authenticated documents. --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <x-admin.stat-card label="Documents Submitted" :value="$submitted" />
            <x-admin.stat-card label="Pending Review" :value="$pending" />
            <x-admin.stat-card label="Authenticated" :value="$authenticated" />
        </div>

        {{-- Search and paginate issued document records. --}}
        <x-tables.card title="Authentication Records"
            description="Track issued documents and their authentication status."
            :paginator="$documents" :searchable="false" item-label="documents">
            <x-slot:toolbar>
                <x-tables.toolbar key="document-authentication" :search="true"
                    search-placeholder="Search by permit number or name"
                    class="gap-0! pb-0! sm:pb-0! justify-end" />
            </x-slot:toolbar>

            <x-tables.table :heads="['Document Reference', 'Document Type', 'Submitted By', 'Authentication Status', 'Date']"
                :empty="$documents->isEmpty()" message="No authenticated documents yet.">
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