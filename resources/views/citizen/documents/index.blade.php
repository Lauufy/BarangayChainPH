@extends('layouts.citizen')

@section('title', 'My Documents')

@section('content')
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

        <x-tables.card title="Issued documents"
            description="Approved service requests are available here as issued documents."
            :paginator="$documents->hasPages() ? $documents : null" :searchable="false" :per-page-options="[]">
            <x-tables.table
                :heads="['Reference', 'Document', 'Approved', ['label' => 'Actions', 'align' => 'center']]"
                :empty="$documents->isEmpty()"
                message="No documents to show. Approved documents will appear here once your service request has been reviewed."
            >
                @foreach ($documents as $document)
                    <tr>
                        {{-- Summary: always visible on mobile --}}
                        <td data-title class="whitespace-nowrap font-semibold text-slate-900 dark:text-slate-100">
                            {{ $document->reference_number }}
                        </td>
                        <td class="text-slate-600 dark:text-slate-300">{{ $document->serviceTitle() }}</td>

                        {{-- Details: revealed when the card is tapped on mobile --}}
                        <x-tables.detail label="Approved" class="whitespace-nowrap text-slate-600 dark:text-slate-300">
                            {{ $document->updated_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                        </x-tables.detail>

                        <x-tables.detail side="full" class="text-center">
                            {{-- Desktop: compact icon buttons --}}
                            <div class="hidden flex-wrap items-center justify-center gap-2 sm:flex">
                                <x-tables.action label="View" icon="eye" icon-only :href="route('citizen.applications.show', $document)" />

                                @if ($document->ticket)
                                    <x-tables.action label="Messages" icon="message" icon-only :href="route('citizen.tickets.show', $document)" />
                                @endif

                                @if ($document->canPrint())
                                    <x-tables.action label="Print" icon="printer" icon-only :href="route('citizen.applications.print', $document)" />
                                @endif
                            </div>

                            {{-- Mobile: labeled buttons, easier to tap --}}
                            <div class="flex flex-wrap items-center justify-center gap-2 sm:hidden">
                                <x-tables.action label="View" icon="eye" :href="route('citizen.applications.show', $document)" />

                                @if ($document->ticket)
                                    <x-tables.action label="Messages" icon="message" :href="route('citizen.tickets.show', $document)" />
                                @endif

                                @if ($document->canPrint())
                                    <x-tables.action label="Print" icon="printer" :href="route('citizen.applications.print', $document)" />
                                @endif
                            </div>
                        </x-tables.detail>
                    </tr>
                @endforeach
            </x-tables.table>
        </x-tables.card>
    </div>

@endsection