@extends('layouts.citizen')

@use('App\Models\ServiceRequest')

@section('title', 'My Applications')

@section('content')
    @php
        $tones = [
            'pending' => ['bg-amber-50 text-amber-800 ring-amber-200 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/30', 'bg-amber-500'],
            'processing' => ['bg-blue-50 text-blue-800 ring-blue-200 dark:bg-blue-400/10 dark:text-blue-300 dark:ring-blue-400/30', 'bg-blue-500'],
            'approved' => ['bg-green-50 text-green-800 ring-green-200 dark:bg-green-400/10 dark:text-green-300 dark:ring-green-400/30', 'bg-green-500'],
            'rejected' => ['bg-red-50 text-red-800 ring-red-200 dark:bg-red-400/10 dark:text-red-300 dark:ring-red-400/30', 'bg-red-500'],
            'cancelled' => ['bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-700/40 dark:text-slate-300 dark:ring-slate-600', 'bg-slate-400'],
        ];
        $btn = 'inline-flex rounded-lg px-3 py-1.5 text-xs font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2';
        $outline = $btn . ' border border-slate-300 text-[#17325c] hover:border-[#17325c] hover:bg-slate-50 dark:border-slate-600 dark:text-blue-200 dark:hover:border-blue-300 dark:hover:bg-slate-800';
        $primary = $btn . ' bg-[#17325c] text-white hover:bg-[#10244a] dark:bg-blue-600 dark:hover:bg-blue-500';
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

        @if ($applications->isEmpty())
            <section class="rounded-xl border border-dashed border-slate-300 bg-white px-5 py-14 text-center sm:px-6 dark:border-slate-600 dark:bg-slate-900">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#17325c]/10 text-[#17325c] dark:bg-blue-300/10 dark:text-blue-300">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                        aria-hidden="true">
                        <path d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" />
                        <path d="M14 3v5h5M9 13h6M9 17h6" />
                    </svg>
                </span>
                <h2 class="mt-4 font-semibold text-[#09172D] dark:text-slate-100">No applications yet</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500 dark:text-slate-400">
                    Submit a barangay service request to start tracking its progress here.
                </p>
                <a href="{{ route('citizen.services') }}"
                    class="mt-5 inline-flex rounded-lg bg-[#17325c] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#10244a] dark:bg-blue-600 dark:hover:bg-blue-500">
                    Browse services
                </a>
            </section>
        @else
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:shadow-none">
                <div class="overflow-x-auto">
                    <table class="min-w-2xl w-full text-left text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Reference</th>
                                <th class="px-5 py-3 font-semibold">Service</th>
                                <th class="px-5 py-3 font-semibold">Date submitted</th>
                                <th class="px-5 py-3 font-semibold">Status</th>
                                <th class="px-5 py-3 font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($applications as $application)
                                @php [$pill, $dot] = $tones[$application->status] ?? $tones['cancelled']; @endphp
                                <tr class="align-top hover:bg-slate-50/60 dark:hover:bg-slate-800/60">
                                    <td class="whitespace-nowrap px-5 py-4 font-semibold text-slate-900 dark:text-slate-100">
                                        {{ $application->reference_number }}</td>

                                    <td class="px-5 py-4 text-slate-700 dark:text-slate-300">
                                        @switch($application->service_type)
                                            @case(ServiceRequest::BARANGAY_CLEARANCE)
                                                Barangay clearance
                                            @break

                                            @case(ServiceRequest::CERTIFICATE_OF_RESIDENCY)
                                                Certificate of residency
                                            @break

                                            @default
                                                Certificate of indigency
                                        @endswitch
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600 dark:text-slate-400">
                                        {{ $application->created_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                                    </td>

                                    <td class="px-5 py-4">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold capitalize ring-1 {{ $pill }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $dot }}"
                                                aria-hidden="true"></span>
                                            {{ $application->status }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap items-start gap-2">
                                            <a href="{{ route('citizen.applications.show', $application) }}"
                                                class="{{ $outline }}">Review</a>

                                            @if ($application->ticket)
                                                <a href="{{ route('citizen.tickets.show', $application) }}"
                                                    class="{{ $outline }}">Messages</a>
                                            @endif

                                            @if ($application->canPrint())
                                                <a href="{{ route('citizen.applications.print', $application) }}"
                                                    class="{{ $primary }}">Print</a>
                                            @elseif ($application->status === ServiceRequest::APPROVED)
                                                <span class="py-1.5 text-xs font-medium text-slate-400 dark:text-slate-500">Print limit reached</span>
                                            @endif

                                            @if ($application->isCancellable())
                                                <details class="group">
                                                    <summary
                                                        class="{{ $btn }} cursor-pointer list-none border border-red-200 text-red-700 hover:bg-red-50 dark:border-red-500/40 dark:text-red-300 dark:hover:bg-red-500/10 [&::-webkit-details-marker]:hidden">
                                                        Cancel
                                                    </summary>
                                                    <form method="POST"
                                                        action="{{ route('citizen.applications.cancel', $application) }}"
                                                        class="mt-2 w-56 space-y-2 rounded-lg border border-slate-200 bg-white p-3 shadow-md dark:border-slate-700 dark:bg-slate-800 dark:shadow-none">
                                                        @csrf
                                                        <label for="cancellation_reason_{{ $application->id }}"
                                                            class="block text-xs font-semibold text-slate-800 dark:text-slate-200">
                                                            Reason for cancellation
                                                        </label>
                                                        <textarea id="cancellation_reason_{{ $application->id }}" name="cancellation_reason" required minlength="5"
                                                            maxlength="500" rows="3"
                                                            class="block w-full rounded-md border border-slate-300 bg-white p-2 text-xs text-slate-900 outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-red-400"></textarea>
                                                        <button class="{{ $btn }} bg-red-600 text-white hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-400">
                                                            Confirm cancellation
                                                        </button>
                                                    </form>
                                                </details>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($applications->hasPages())
                    <div class="border-t border-slate-200 p-4 dark:border-slate-700">{{ $applications->links() }}</div>
                @endif
            </section>
        @endif
    </div>
@endsection