@extends('layouts.admin')

@section('title', 'Communications Management')

@section('content')
    <div class="space-y-6">
        <x-admin.page-header label="Communication" title="Communications Management"
            text="Manage citizen ticket messages and public website inquiries." />

        @php
            $activeCommunication = request('communications_tab') === 'inquiries' ? 'inquiries' : 'tickets';
        @endphp

        <div class="space-y-4">
            <nav class="inline-flex items-center gap-1 rounded-lg bg-slate-100 p-1 dark:bg-slate-800"
                aria-label="Communication types">
                <a href="{{ route('admin.tickets.index', ['communications_tab' => 'tickets']) }}"
                    @if ($activeCommunication === 'tickets') aria-current="page" @endif
                    class="rounded-md px-4 py-2 text-sm transition {{ $activeCommunication === 'tickets'
                        ? 'bg-white font-semibold text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white'
                        : 'font-medium text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}">
                    Ticket Messages
                </a>
                <a href="{{ route('admin.tickets.index', ['communications_tab' => 'inquiries']) }}"
                    @if ($activeCommunication === 'inquiries') aria-current="page" @endif
                    class="rounded-md px-4 py-2 text-sm transition {{ $activeCommunication === 'inquiries'
                        ? 'bg-white font-semibold text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white'
                        : 'font-medium text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}">
                    Website Inquiries
                </a>
            </nav>

            @if ($activeCommunication === 'tickets')
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
                </x-tables.card>
            @else
                <x-tables.card title="Website Inquiries"
                    description="Read public landing page messages and reply by email."
                    :paginator="$contactMessages" :searchable="false" item-label="inquiries"
                    :per-page-options="[10, 15, 25, 50]">
                    <x-slot:toolbar>
                        <x-tables.toolbar key="website-inquiries" :search="true"
                            search-placeholder="Search name, email, subject, or message"
                            class="gap-0! pb-0! sm:pb-0! justify-end" />
                    </x-slot:toolbar>

                    <div class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse ($contactMessages as $contactMessage)
                            @php($awaitingReply = $contactMessage->status === \App\Models\ContactMessage::NEW)
                            <a href="{{ route('admin.contact-messages.show', $contactMessage) }}"
                                class="block px-4 py-4 transition hover:bg-slate-50 dark:hover:bg-white/5">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-900 dark:text-slate-100">
                                            {{ $contactMessage->subject }}
                                        </p>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                            {{ $contactMessage->first_name }} {{ $contactMessage->last_name }}
                                            · {{ $contactMessage->email }}
                                        </p>
                                    </div>
                                    <span @class([
                                        'rounded-full px-2.5 py-1 text-xs font-semibold',
                                        'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300' => $awaitingReply,
                                        'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300' => !$awaitingReply,
                                    ])>
                                        {{ $awaitingReply ? 'Awaiting reply' : 'Replied' }}
                                    </span>
                                </div>
                                <p class="mt-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                                    {{ $contactMessage->service }}
                                    · {{ $contactMessage->replies_count }} {{ \Illuminate\Support\Str::plural('reply', $contactMessage->replies_count) }}
                                    · {{ $contactMessage->updated_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                </p>
                                <p class="mt-2 line-clamp-2 whitespace-pre-line text-sm text-slate-700 dark:text-slate-300">
                                    {{ $contactMessage->body }}
                                </p>
                            </a>
                        @empty
                            <div class="px-4 py-12 text-center">
                                <p class="font-semibold text-slate-900 dark:text-slate-100">No website inquiries yet</p>
                            </div>
                        @endforelse
                    </div>
                </x-tables.card>
            @endif
        </div>
    </div>
@endsection