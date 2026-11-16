@extends('layouts.admin')

@section('title', 'Ticket Messages')

@section('content')
    <div class="space-y-6">
        {{-- Explain the support inbox and the ticket action available to staff. --}}
        <x-admin.page-header label="Communication" title="Ticket Messages"
            text="Read and reply to citizen messages about their requests."
            :export-url="route('admin.exports.show', ['module' => 'tickets'])" />

        {{-- List ticket activity with paging and direct access to each conversation. --}}
        <x-tables.card title="Citizen Messages"
            description="Open a ticket to read the conversation and reply."
            :paginator="$tickets" :searchable="false" item-label="tickets"
            :per-page-options="[10, 15, 25, 50]">
            <x-slot:toolbar>
                <x-tables.toolbar key="ticket-messages" :search="true"
                    search-placeholder="Search citizen, reference, or message"
                    class="gap-0! pb-0! sm:pb-0! justify-end" />
            </x-slot:toolbar>

            <x-tables.table :heads="['Citizen', 'Reference', 'Last activity', 'Status', ['label' => 'Action', 'align' => 'center']]"
                :empty="$tickets->isEmpty()" message="No tickets yet.">
                @foreach ($tickets as $ticket)
                    <tr>
                        <td>
                            <x-tables.user-cell :name="$ticket->serviceRequest->applicant_name"
                                :sub="$ticket->serviceRequest->user->email" />
                        </td>

                        <td class="text-slate-600">{{ $ticket->serviceRequest->reference_number }}</td>

                        <td class="text-slate-600">
                            {{ $ticket->updated_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                        </td>

                        <td>
                            <x-tables.badge :variant="$ticket->status === 'open' ? 'success' : 'neutral'">
                                {{ $ticket->status }}
                            </x-tables.badge>
                        </td>

                        <td class="text-center">
                            <div class="flex justify-center">
                                <x-tables.action label="Open" icon="eye"
                                    :href="route('admin.tickets.show', $ticket)" />
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        </x-tables.card>
    </div>
@endsection