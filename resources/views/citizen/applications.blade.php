@extends('layouts.citizen')

@section('title', 'My Applications')

@section('content')
    <div class="space-y-7">
        <section>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-700">Requests</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-[#09172D] sm:text-3xl">My applications</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Track the progress of your barangay service requests in one place.</p>
        </section>

        @if ($applications->isEmpty())
            <section class="rounded-2xl border border-slate-200 bg-white px-5 py-14 text-center shadow-sm sm:px-6">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path d="M14 3v5h5M9 13h6M9 17h6"/></svg>
                </span>
                <h2 class="mt-4 font-semibold text-[#09172D]">No applications yet</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Submit a barangay service request to start tracking its progress here.</p>
                <a href="{{ route('citizen.services') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-[#0B2347] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#123365]">Browse services <span aria-hidden="true">→</span></a>
            </section>
        @else
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[42rem] text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Reference</th>
                                <th class="px-5 py-3 font-semibold">Service</th>
                                <th class="px-5 py-3 font-semibold">Date submitted</th>
                                <th class="px-5 py-3 font-semibold">Status</th>
                                <th class="px-5 py-3 font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($applications as $application)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-4 font-semibold text-slate-900">{{ $application->reference_number }}</td>
                                    <td class="px-5 py-4 text-slate-700">
                                        {{ match ($application->service_type) {
                                            \App\Models\ServiceRequest::BARANGAY_CLEARANCE => 'Barangay clearance',
                                            \App\Models\ServiceRequest::CERTIFICATE_OF_RESIDENCY => 'Certificate of residency',
                                            default => 'Certificate of indigency',
                                        } }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $application->created_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</td>
                                    <td class="px-5 py-4">
                                        <span @class([
                                            'rounded-full px-2.5 py-1 text-xs font-bold capitalize',
                                            'bg-amber-100 text-amber-700' => $application->status === 'pending',
                                            'bg-blue-100 text-blue-700' => $application->status === 'processing',
                                            'bg-green-100 text-green-700' => $application->status === 'approved',
                                            'bg-red-100 text-red-700' => $application->status === 'rejected',
                                            'bg-slate-100 text-slate-700' => $application->status === 'cancelled',
                                        ])>{{ $application->status }}</span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-col items-start gap-2">
                                            <a href="{{ route('citizen.applications.show', $application) }}"
                                                class="inline-flex rounded-md border border-[#17325c] px-3 py-1.5 text-xs font-semibold text-[#17325c] hover:bg-slate-50">
                                                Review
                                            </a>
                                            @if ($application->canPrint())
                                                <a href="{{ route('citizen.applications.print', $application) }}"
                                                    class="inline-flex rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">
                                                    Print
                                                </a>
                                            @elseif ($application->status === \App\Models\ServiceRequest::APPROVED)
                                                <span class="text-xs font-semibold text-slate-400">Print limit reached</span>
                                            @endif
                                            @if ($application->isCancellable())
                                                <details class="group">
                                                    <summary
                                                        class="cursor-pointer list-none rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700 [&::-webkit-details-marker]:hidden">
                                                        Cancel
                                                    </summary>
                                                    <form method="POST"
                                                        action="{{ route('citizen.applications.cancel', $application) }}"
                                                        class="mt-2 w-56 space-y-2 rounded-md border border-red-200 bg-red-50 p-3">
                                                        @csrf
                                                        <label for="cancellation_reason_{{ $application->id }}"
                                                            class="block text-xs font-semibold text-red-900">
                                                            Reason for cancellation
                                                        </label>
                                                        <textarea id="cancellation_reason_{{ $application->id }}"
                                                            name="cancellation_reason" required minlength="5" maxlength="500"
                                                            rows="3"
                                                            class="block w-full rounded-md border border-red-200 bg-white p-2 text-xs text-slate-900 outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20"></textarea>
                                                        <button type="submit"
                                                            class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">
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
                    <div class="border-t border-slate-200 p-4">
                        {{ $applications->links() }}
                    </div>
                @endif
            </section>
        @endif
    </div>
@endsection
