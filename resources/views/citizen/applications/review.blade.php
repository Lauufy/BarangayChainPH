@extends('layouts.citizen')

@section('title', 'Review Application')

@section('content')
@php
    $tones = [
        'pending' => ['bg-amber-50 text-amber-800 ring-amber-200', 'bg-amber-500'],
        'processing' => ['bg-blue-50 text-blue-800 ring-blue-200', 'bg-blue-500'],
        'approved' => ['bg-green-50 text-green-800 ring-green-200', 'bg-green-500'],
        'rejected' => ['bg-red-50 text-red-800 ring-red-200', 'bg-red-500'],
        'cancelled' => ['bg-slate-100 text-slate-700 ring-slate-200', 'bg-slate-400'],
    ];
    [$pill, $dot] = $tones[$application->status] ?? $tones['cancelled'];

    $details = [
        'Reference number' => $application->reference_number,
        'Date submitted' => $application->created_at->timezone(config('app.timezone'))->format('M j, Y g:i A'),
        'Full name' => $application->applicant_name,
        'Age' => $application->age,
        'Address' => $application->address,
        'Purpose' => $application->purpose,
        'Cancellation reason' => $application->cancellation_reason,
    ];
@endphp

    <div class="mx-auto max-w-3xl space-y-5">
        <a href="{{ route('citizen.applications') }}"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-[#17325c]">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M15 18l-6-6 6-6" />
            </svg>
            Back to applications
        </a>

        {{-- Summary --}}
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 bg-[#17325c] px-6 py-6 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                <div>
                    <p class="text-sm text-blue-200">Submitted application</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-white">
                        {{ $documentTitle ?? 'Certificate of indigency' }}
                    </h1>
                </div>

                <span class="inline-flex w-fit items-center gap-2 rounded-full px-3 py-1 text-sm font-semibold capitalize ring-1 {{ $pill }}">
                    <span class="h-2 w-2 rounded-full {{ $dot }}" aria-hidden="true"></span>
                    {{ $application->status }}
                </span>
            </div>

            <dl class="divide-y divide-slate-100 px-6 sm:px-8">
                @foreach ($details as $label => $value)
                    @if (filled($value))
                        <div class="grid gap-1 py-3.5 text-sm sm:grid-cols-3 sm:gap-4">
                            <dt class="text-slate-500">{{ $label }}</dt>
                            <dd class="font-medium text-slate-900 sm:col-span-2">{{ $value }}</dd>
                        </div>
                    @endif
                @endforeach
            </dl>

            @if ($application->status === 'rejected' && $application->rejection_reason)
                <div class="m-6 rounded-lg border-l-4 border-red-500 bg-red-50 p-4 sm:mx-8">
                    <p class="text-sm font-semibold text-red-900">Reason for rejection</p>
                    <p class="mt-1 text-sm leading-6 text-red-800">{{ $application->rejection_reason }}</p>
                </div>
            @endif
        </section>

        {{-- Messages --}}
        @if ($ticket)
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:px-8">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-semibold text-[#09172D]">Latest messages</h2>
                    <a href="{{ route('citizen.tickets.show', $application) }}"
                        class="text-sm font-semibold text-[#17325c] hover:underline">
                        Open ticket
                    </a>
                </div>

                <ul class="mt-4 space-y-3 text-sm">
                    @foreach ($recentMessages as $message)
                        <li class="border-l-2 border-slate-200 pl-3">
                            <p class="text-slate-800">{{ Str::limit($message->body, 120) }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ $message->created_at->timezone(config('app.timezone'))->format('M j, g:i A') }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- Cancel --}}
        @if ($application->isCancellable())
            <details @if ($errors->any()) open @endif
                class="group rounded-xl border border-slate-200 bg-white shadow-sm">
                <summary
                    class="flex cursor-pointer list-none items-center justify-between px-6 py-4 text-sm font-semibold text-red-700 sm:px-8">
                    Cancel application
                    <svg class="h-4 w-4 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        aria-hidden="true">
                        <path d="M6 9l6 6 6-6" />
                    </svg>
                </summary>

                <div class="border-t border-slate-100 px-6 py-5 sm:px-8">
                    <p class="text-sm leading-6 text-slate-600">
                        You can cancel this pending application within two hours of submission. Please provide a reason.
                    </p>

                    @if ($errors->any())
                        <div role="alert" class="mt-3 text-sm font-medium text-red-700">
                            {{ $errors->first('cancellation_reason') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('citizen.applications.cancel', $application) }}"
                        class="mt-4 space-y-3">
                        @csrf
                        <textarea name="cancellation_reason" required minlength="5" maxlength="500" rows="3"
                            placeholder="Reason for cancellation"
                            class="block w-full rounded-lg border border-slate-300 bg-white p-3 text-sm text-slate-900 outline-none focus:border-red-500 focus:ring-2 focus:ring-red-500/20">{{ old('cancellation_reason') }}</textarea>
                        <button
                            class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                            Cancel application
                        </button>
                    </form>
                </div>
            </details>
        @endif
    </div>
@endsection