@extends('layouts.admin')

@section('title', 'Ticket Messages')

@section('content')
    <div class="space-y-6">
        <x-admin.page-header label="Communication" title="Ticket Messages"
            text="Read and reply to citizen messages about their requests." />

        <div class="admin-card">
            <div class="border-b border-slate-200 p-5">
                <h2 class="font-semibold text-slate-900">Citizen Messages</h2>
                <p class="mt-1 text-sm text-slate-500">Open a ticket to read the conversation and reply.</p>
            </div>

            <x-admin.table :heads="['Citizen', 'Reference', 'Last activity', 'Status', 'Action']"
                :empty="$tickets->isEmpty()" message="No tickets yet.">
                @foreach ($tickets as $ticket)
                    <tr>
                        <td class="px-5 py-4">
                            <p class="font-medium text-slate-900">{{ $ticket->serviceRequest->applicant_name }}</p>
                            <p class="text-xs text-slate-500">{{ $ticket->serviceRequest->user->email }}</p>
                        </td>

                        <td class="px-5 py-4 text-slate-600">{{ $ticket->serviceRequest->reference_number }}</td>

                        <td class="px-5 py-4 text-slate-600">
                            {{ $ticket->updated_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                        </td>

                        <td class="px-5 py-4">
                            <x-badge :variant="$ticket->status === 'open' ? 'success' : 'default'">
                                {{ $ticket->status }}
                            </x-badge>
                        </td>

                        <td class="px-5 py-4">
                            <a href="{{ route('admin.tickets.show', $ticket) }}"
                                class="inline-flex rounded-md border border-[#17325c] px-3 py-1.5 text-xs font-semibold text-[#17325c] hover:bg-slate-50">
                                Open
                            </a>
                        </td>
                    </tr>
                @endforeach
            </x-admin.table>

            <div class="border-t border-slate-200 p-4">{{ $tickets->links() }}</div>
        </div>
    </div>
@endsection