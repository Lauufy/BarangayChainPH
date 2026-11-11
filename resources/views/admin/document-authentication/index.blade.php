@extends('layouts.admin')

@section('title', 'Document Authentication')

@section('content')
    <div class="space-y-6">
        <x-admin.page-header label="Document Security" title="Document Authentication"
            text="Review document verification records and authentication results." />

        {{-- Numbers --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <x-admin.stat-card label="Documents Submitted" :value="$submitted" />
            <x-admin.stat-card label="Pending Review" :value="$pending" />
            <x-admin.stat-card label="Authenticated" :value="$authenticated" />
        </div>

        <x-tables.card title="Authentication Records"
            description="Track issued documents and their authentication status."
            :paginator="$documents" item-label="documents"
            search-placeholder="Search by permit number or name">
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
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-xs font-semibold text-slate-700">Replaced</span>
                            @else
                                <span class="rounded-full border border-green-200 bg-green-50 px-2.5 py-0.5 text-xs font-semibold text-green-800">Active</span>
                            @endif
                        </td>

                        <td class="whitespace-nowrap">
                            {{ $document->issued_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') ?? '—' }}
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        </x-tables.card>

        {{-- Note --}}
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
            <h2 class="font-semibold text-emerald-900">Authentication Integrity</h2>
            <p class="mt-2 text-sm leading-6 text-emerald-800">
                Each document is listed with its version. A newer version replaces the older one.
                A hash comparison can help detect changes to a document, but does not by itself establish
                the identity of its issuer or prove that the original document was legitimate.
            </p>
        </div>
    </div>
@endsection