@extends('layouts.admin')

@section('title', 'Ticket Messages')

@section('content')
    <div class="space-y-6">
        <x-admin.page-header label="Communication" title="Ticket Messages"
            text="Read and reply to citizen messages about their requests." />

        <x-tables.card title="Citizen Messages"
            description="Open a ticket to read the conversation and reply."
            :paginator="$tickets->hasPages() ? $tickets : null"
            :searchable="false" :per-page-options="[]">
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