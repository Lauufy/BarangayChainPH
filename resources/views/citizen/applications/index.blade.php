@extends('layouts.citizen')

@use('App\Models\ServiceRequest')

@section('title', 'My Applications')

@section('content')
    @php
        // Badge colour per status (same map as the admin permits page)
        $statuses = [
            'pending' => 'warning',
            'processing' => 'info',
            'approved' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'neutral',
        ];
    @endphp

    <div class="space-y-6">
        {{-- Header --}}
        <header class="space-y-2 border-b border-(--gray-200) pb-4 dark:border-(--gray-700)">
            <nav class="flex items-center gap-2 text-xs text-(--gray-500) dark:text-(--gray-400)" aria-label="Breadcrumb">
                <a href="{{ route('citizen.services') }}" class="hover:text-(--blue-700) dark:hover:text-(--blue-300)">Services</a>
                <span aria-hidden="true">›</span>
                <span class="font-semibold text-(--blue-700) dark:text-(--blue-300)">My applications</span>
            </nav>

            <div>
                <h1 class="text-2xl font-bold tracking-tight text-(--blue-900) sm:text-3xl dark:text-(--blue-300)">My applications</h1>
                <p class="mt-1 text-sm text-(--gray-600) dark:text-(--gray-300)">Track the progress of your barangay service requests in one place.</p>
            </div>
        </header>

        <x-tables.card title="Service requests"
            description="Review your submitted requests and their current status."
            :paginator="$applications->hasPages() ? $applications : null" :searchable="false" :per-page-options="[]">
            <x-tables.table
                :heads="['Reference', 'Service', 'Date Submitted', 'Status', ['label' => 'Actions', 'align' => 'center']]"
                :empty="$applications->isEmpty()"
                message="No applications yet. Submit a barangay service request to start tracking its progress here."
            >
                @foreach ($applications as $application)
                    <tr>
                        {{-- Summary: always visible on mobile --}}
                        <td data-title class="whitespace-nowrap font-semibold text-slate-900 dark:text-slate-100">
                            {{ $application->reference_number }}
                        </td>
                        <td class="text-slate-600 dark:text-slate-300">{{ $application->serviceTitle() }}</td>
                        <td data-side="right">
                            <x-tables.badge :variant="$statuses[$application->status] ?? 'neutral'" labeled class="capitalize">
                                {{ $application->status }}
                            </x-tables.badge>
                        </td>

                        {{-- Details: revealed when the card is tapped on mobile --}}
                        <x-tables.detail label="Date submitted" class="whitespace-nowrap text-slate-600 dark:text-slate-300">
                            {{ $application->created_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                        </x-tables.detail>

                        <x-tables.detail side="full" class="text-center">
                            <div class="flex flex-wrap items-start justify-center gap-2">
                                <x-tables.action label="Review" icon="eye" :href="route('citizen.applications.show', $application)" />

                                @if ($application->ticket)
                                    <x-tables.action label="Messages" icon="message" :href="route('citizen.tickets.show', $application)" />
                                @endif

                                @if ($application->canPrint())
                                    <x-tables.action label="Print" icon="printer" :href="route('citizen.applications.print', $application)" />
                                @endif

                                @if ($application->isCancellable())
                                    <details class="group text-left max-sm:w-full max-sm:text-center">
                                        <summary
                                            class="inline-flex cursor-pointer list-none rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-50 focus-visible:outline-solid focus-visible:outline-2 focus-visible:outline-offset-2 dark:border-red-500/40 dark:text-red-300 dark:hover:bg-red-500/10 [&::-webkit-details-marker]:hidden">
                                            Cancel
                                        </summary>
                                        <form method="POST"
                                            action="{{ route('citizen.applications.cancel', $application) }}"
                                            class="mt-2 w-full space-y-2 text-left rounded-lg border border-slate-200 bg-white p-3 shadow-md sm:w-56 dark:border-slate-700 dark:bg-slate-900">
                                            @csrf
                                            <label for="cancellation_reason_{{ $application->id }}"
                                                class="block text-xs font-semibold text-slate-800 dark:text-slate-100">
                                                Reason for cancellation
                                            </label>
                                            <textarea id="cancellation_reason_{{ $application->id }}" name="cancellation_reason" required minlength="5"
                                                maxlength="500" rows="3"
                                                class="block w-full rounded-md border border-slate-300 bg-white p-2 text-xs text-slate-900 outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"></textarea>
                                            <button class="inline-flex rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-red-700">
                                                Confirm cancellation
                                            </button>
                                        </form>
                                    </details>
                                @endif
                            </div>
                        </x-tables.detail>
                    </tr>
                @endforeach
            </x-tables.table>
        </x-tables.card>
    </div>
@endsection