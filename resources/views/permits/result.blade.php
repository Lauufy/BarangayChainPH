@extends('layouts.permit-verification')

@section('title', 'Verification result')

@section('content')
@php
    $badge = match ($status) {
        'valid' => ['Valid document', 'bg-emerald-50 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200', 'This PDF matches the current permit in the barangay and blockchain records.'],
        'revoked' => ['Revoked', 'bg-red-50 text-red-900 dark:bg-red-950 dark:text-red-200', null],
        'invalid' => ['Fake / Invalid', 'bg-red-50 text-red-900 dark:bg-red-950 dark:text-red-200', 'The file could not be verified.'],
        'expired' => ['Expired', 'bg-amber-50 text-amber-900 dark:bg-amber-950 dark:text-amber-200', null],
        'unavailable' => ['Cannot verify right now', 'bg-amber-50 text-amber-900 dark:bg-amber-950 dark:text-amber-200', 'Blockchain confirmation is temporarily unavailable. Try again later.'],
        default => null,
    };
@endphp
    @if ($badge)
        <section aria-labelledby="permit-status" class="mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-start gap-3 px-5 py-4 sm:px-7 {{ $badge[1] }}">
                <span class="grid size-8 shrink-0 place-items-center rounded-full bg-current/10" aria-hidden="true">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        @if ($status === 'valid')
                            <path d="m5 12 4 4L19 6" />
                        @else
                            <path d="M12 6v7m0 4h.01" />
                        @endif
                    </svg>
                </span>
                <div>
                <h2 id="permit-status" class="text-lg font-semibold">{{ $badge[0] }}</h2>
                @if ($reason ?? $badge[2])
                    <p class="mt-1 text-sm leading-relaxed text-slate-700 dark:text-slate-200">{{ $reason ?? $badge[2] }}</p>
                @elseif ($status === 'revoked')
                    <p class="mt-1 text-sm text-slate-700 dark:text-slate-200">Revoked on {{ $document->revoked_at->format('F j, Y') }}.</p>
                @elseif ($status === 'expired')
                    <p class="mt-1 text-sm text-slate-700 dark:text-slate-200">Expired on {{ $document->expires_at->format('F j, Y') }}.</p>
                @endif
                </div>
            </div>

            @if ($differences)
                <div class="border-t border-slate-200 p-5 sm:px-7 dark:border-slate-800">
                    <h3 class="text-sm font-semibold">Fields that differ</h3>
                    <ul class="mt-2 list-disc space-y-1 ps-5 text-sm">
                        @foreach ($differences as $field)
                            <li>{{ $field }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($status === 'valid')
                <dl class="grid gap-x-6 gap-y-4 border-t border-slate-200 p-5 text-sm sm:grid-cols-2 sm:px-7 dark:border-slate-800">
                    @foreach ($details as $label => $value)
                        <div @class(['min-w-0', 'sm:col-span-2' => $loop->first])>
                            <dt class="text-xs font-semibold text-slate-600 dark:text-slate-300">{{ $label }}</dt>
                            <dd class="mt-1 wrap-break-word font-medium">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            @elseif ($status === 'revoked' && $document->replacement)
                <div class="border-t border-slate-200 p-5 sm:px-7 dark:border-slate-800">
                    <a class="text-sm font-semibold text-blue-800 underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-4 dark:text-blue-300"
                        href="{{ route('permits.verify.form', ['ref' => $document->replacement->document_uuid]) }}">
                        Verify replacement permit (version {{ $document->replacement->version }})
                    </a>
                </div>
            @endif
        </section>
    @endif

    <div class="flex flex-wrap gap-x-5 gap-y-3">
        <a href="{{ route('permits.verify.form', ['ref' => $reference]) }}"
            class="inline-flex rounded-md border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-[#17325c] hover:border-blue-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
            Try another file
        </a>
        <a href="{{ route('permits.verify.form') }}"
            class="inline-flex items-center px-1 text-sm font-semibold text-blue-800 underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2 dark:text-blue-300">
            Verify another permit
        </a>
    </div>
@endsection
