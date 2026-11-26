@extends('layouts.admin')

@section('title', 'Communications Management')

@section('content')
    <div class="admin-module space-y-5">
        <x-admin.page-header title="Communications Management"
            text="Manage citizen ticket messages and public website inquiries." />

        @php
            $activeCommunication = request('communications_tab') === 'inquiries' ? 'inquiries' : 'tickets';
            $showingTickets = $activeCommunication === 'tickets';

            $tabs = [
                'tickets' => [
                    'label' => 'Ticket Messages',
                    // message-square
                    'icon' => 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z',
                ],
                'inquiries' => [
                    'label' => 'Website Inquiries',
                    // mail
                    'icon' => 'M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z M22 7l-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7',
                ],
            ];
        @endphp

        <x-tables.card :paginator="$showingTickets ? $tickets : $contactMessages" :searchable="false"
            :item-label="$showingTickets ? 'tickets' : 'inquiries'" :per-page-options="[10, 15, 25, 50]">
            <x-tables.toolbar :key="$showingTickets ? 'ticket-messages' : 'website-inquiries'" :tabs="$tabs" :active="$activeCommunication" :search="true"
                :search-placeholder="$showingTickets
                    ? 'Search citizen, reference, or message'
                    : 'Search name, email, subject, or message'" />

            @if ($showingTickets)
                <x-tables.table :heads="['Citizen', 'Reference', 'Last activity', 'Status', ['label' => 'Action', 'align' => 'center']]"
                    :empty="$tickets->isEmpty()" message="No tickets yet.">
                    @foreach ($tickets as $ticket)
                        <tr>
                            <td>
                                <x-tables.user-cell :name="$ticket->citizenName()"
                                    :sub="$ticket->user->email" />
                            </td>
                            <td class="text-slate-600">{{ $ticket->reference() }}</td>
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
                                    <x-tables.action label="Open" icon="file-search"
                                        :href="route('admin.tickets.show', $ticket)" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-tables.table>
            @else
                <x-tables.table :heads="['Inquiry', 'Sender', 'Service', 'Last activity', 'Status', ['label' => 'Action', 'align' => 'center']]"
                    :empty="$contactMessages->isEmpty()" message="No website inquiries yet.">
                    @foreach ($contactMessages as $contactMessage)
                        @php($awaitingReply = $contactMessage->status === \App\Models\ContactMessage::NEW)
                        <tr>
                            <td class="max-w-xs">
                                <p class="truncate font-semibold text-slate-900 dark:text-slate-100">
                                    {{ $contactMessage->subject }}
                                </p>
                                <p class="mt-1 line-clamp-2 whitespace-pre-line text-xs text-slate-500 dark:text-slate-400">
                                    {{ $contactMessage->body }}
                                </p>
                            </td>
                            <td>
                                <p class="text-slate-900 dark:text-slate-100">
                                    {{ $contactMessage->first_name }} {{ $contactMessage->last_name }}
                                </p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $contactMessage->email }}</p>
                            </td>
                            <td class="text-slate-600">
                                {{ $contactMessage->service }}
                                <span class="block text-xs text-slate-500">
                                    {{ $contactMessage->replies_count }}
                                    {{ \Illuminate\Support\Str::plural('reply', $contactMessage->replies_count) }}
                                </span>
                            </td>
                            <td class="text-slate-600 sm:whitespace-nowrap">
                                {{ $contactMessage->updated_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                            </td>
                            <td>
                                <x-tables.badge :variant="$awaitingReply ? 'warning' : 'success'">
                                    {{ $awaitingReply ? 'Awaiting reply' : 'Replied' }}
                                </x-tables.badge>
                            </td>
                            <td class="text-center">
                                <div class="flex justify-center">
                                    <x-tables.action label="Open" icon="file-search"
                                        :href="route('admin.contact-messages.show', $contactMessage)" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-tables.table>
            @endif
        </x-tables.card>
    </div>
@endsection