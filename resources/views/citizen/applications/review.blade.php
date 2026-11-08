@extends('layouts.citizen')

@section('title', 'Review Application')

@section('content')
    @php
        // Status pill: light + dark colors, and the dot color
        $tones = [
            'pending' => ['bg-amber-50 text-amber-800 ring-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:ring-amber-800', 'bg-amber-500'],
            'processing' => ['bg-blue-50 text-blue-800 ring-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:ring-blue-800', 'bg-blue-500'],
            'approved' => ['bg-green-50 text-green-800 ring-green-200 dark:bg-green-900/30 dark:text-green-300 dark:ring-green-800', 'bg-green-500'],
            'rejected' => ['bg-red-50 text-red-800 ring-red-200 dark:bg-red-900/30 dark:text-red-300 dark:ring-red-800', 'bg-red-500'],
            'cancelled' => ['bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-700 dark:text-slate-200 dark:ring-slate-600', 'bg-slate-400'],
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

        // Shared look (theme tokens follow the light / dark toggle)
        $card = 'rounded-xl border border-(--gray-200) bg-(--white) shadow-sm dark:border-(--gray-700) dark:bg-(--gray-800)';
        $line = 'border-(--gray-100) dark:border-(--gray-700)';
        $title = 'text-(--gray-900) dark:text-(--gray-50)';
        $muted = 'text-(--gray-500) dark:text-(--gray-400)';
        $box = 'inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-semibold';
    @endphp

    <div class="space-y-6">
        {{-- Header --}}
        <header class="space-y-2 border-b border-(--gray-200) pb-4 dark:border-(--gray-700)">
            <nav class="flex items-center gap-2 text-xs text-(--gray-500) dark:text-(--gray-400)" aria-label="Breadcrumb">
                <a href="{{ route('citizen.applications') }}" class="hover:text-(--blue-700) dark:hover:text-(--blue-300)">Applications</a>
                <span aria-hidden="true">›</span>
                <span class="font-semibold text-(--blue-700) dark:text-(--blue-300)">Review</span>
            </nav>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-(--blue-900) sm:text-3xl dark:text-(--blue-300)">
                        {{ $documentTitle ?? 'Certificate of indigency' }}
                    </h1>
                    <p class="mt-1 text-sm text-(--gray-600) dark:text-(--gray-300)">Review the details of your submitted application.</p>
                </div>

                <div class="flex items-center gap-2">
                    <span class="{{ $box }} border-transparent capitalize ring-1 {{ $pill }}">
                        <span class="h-2 w-2 rounded-full {{ $dot }}" aria-hidden="true"></span>
                        {{ $application->status }}
                    </span>

                    <a href="{{ route('citizen.applications') }}"
                        class="{{ $box }} border-(--gray-300) bg-(--white) text-(--gray-700) transition hover:bg-(--gray-50) focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-(--blue-300) dark:border-(--gray-600) dark:bg-(--gray-800) dark:text-(--gray-200) dark:hover:bg-(--gray-700)">Back</a>
                </div>
            </div>
        </header>

        {{-- Details --}}
        <section class="{{ $card }} overflow-hidden">
            <dl class="divide-y divide-(--gray-100) px-5 sm:px-6 dark:divide-(--gray-700)">
                @foreach ($details as $label => $value)
                    @if (filled($value))
                        <div class="grid gap-1 py-3.5 text-sm sm:grid-cols-3 sm:gap-4">
                            <dt class="{{ $muted }}">{{ $label }}</dt>
                            <dd class="{{ $title }} font-medium sm:col-span-2">{{ $value }}</dd>
                        </div>
                    @endif
                @endforeach
            </dl>

            @if ($application->status === 'rejected' && $application->rejection_reason)
                <div class="m-5 rounded-lg border-l-4 border-red-500 bg-red-50 p-4 sm:mx-6 dark:bg-red-950/40">
                    <p class="text-sm font-semibold text-red-900 dark:text-red-200">Reason for rejection</p>
                    <p class="mt-1 text-sm leading-6 text-red-800 dark:text-red-300">{{ $application->rejection_reason }}</p>
                </div>
            @endif
        </section>

        {{-- Messages --}}
        @if ($ticket)
            <section class="{{ $card }} p-5 sm:px-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="{{ $title }} font-semibold">Latest messages</h2>
                    <a href="{{ route('citizen.tickets.show', $application) }}"
                        class="text-sm font-semibold text-[#17325c] hover:underline dark:text-(--blue-300)">Open ticket</a>
                </div>

                <ul class="mt-4 space-y-3 text-sm">
                    @foreach ($recentMessages as $message)
                        <li class="border-l-2 border-(--gray-200) pl-3 dark:border-(--gray-600)">
                            <p class="text-(--gray-800) dark:text-(--gray-100)">{{ Str::limit($message->body, 120) }}</p>
                            <p class="{{ $muted }} mt-0.5 text-xs">
                                {{ $message->created_at->timezone(config('app.timezone'))->format('M j, g:i A') }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- Cancel --}}
        @if ($application->isCancellable())
            <details @if ($errors->any()) open @endif class="{{ $card }} group">
                <summary
                    class="flex cursor-pointer list-none items-center justify-between px-5 py-4 text-sm font-semibold text-red-700 sm:px-6 dark:text-red-400">
                    Cancel application
                    <svg class="h-4 w-4 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        aria-hidden="true">
                        <path d="M6 9l6 6 6-6" />
                    </svg>
                </summary>

                <div class="{{ $line }} border-t px-5 py-5 sm:px-6">
                    <p class="text-sm leading-6 text-(--gray-600) dark:text-(--gray-300)">
                        You can cancel this pending application within two hours of submission. Please provide a reason.
                    </p>

                    @if ($errors->any())
                        <div role="alert" class="mt-3 text-sm font-medium text-red-700 dark:text-red-400">
                            {{ $errors->first('cancellation_reason') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('citizen.applications.cancel', $application) }}"
                        class="mt-4 space-y-3">
                        @csrf
                        <textarea name="cancellation_reason" required minlength="5" maxlength="500" rows="3"
                            placeholder="Reason for cancellation"
                            class="block w-full rounded-lg border border-(--gray-300) bg-(--white) p-3 text-sm text-(--gray-900) outline-none placeholder:text-(--gray-400) focus:border-red-500 focus:ring-2 focus:ring-red-500/20 dark:border-(--gray-600) dark:bg-(--gray-900) dark:text-(--gray-50)">{{ old('cancellation_reason') }}</textarea>
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