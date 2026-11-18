{{--
  Automatic ID check (assistive only).
  Include with: @include('admin.identity-verifications.ocr-flags', ['verification' => $verification])
--}}
@php
    // flag => [message, severity, what the reviewer should do]
    $catalog = [
        'unreadable_image'     => ['The image could not be opened.', 'error', 'Open the uploaded file and check it manually.'],
        'no_text_detected'     => ['No text could be read from the ID.', 'error', 'Compare the details by eye.'],
        'ocr_failed'           => ['The automatic check failed.', 'error', 'Review the application manually.'],
        'low_resolution'       => ['The photo is low resolution.', 'warning', 'Consider rejecting and asking for a clearer photo.'],
        'last_name_not_found'  => ['The last name entered was not found on the ID.', 'mismatch', 'Check the spelling against the document.'],
        'first_name_not_found' => ['The first name entered was not found on the ID.', 'mismatch', 'Check the spelling against the document.'],
        'name_not_found'       => ['The name entered was not found on the ID.', 'mismatch', 'Check the spelling against the document.'],
        'birthdate_not_found'  => ['The birthdate entered was not found on the ID.', 'mismatch', 'Check the date against the document.'],
    ];

    $flags    = collect($verification->ocr_flags ?? [])->values();
    $status   = $verification->ocr_status;
    $isClean  = $status === 'done' && $flags->isEmpty();
    $isEmpty  = is_null($status) || ($flags->isEmpty() && ! $isClean);
    $hasError = $flags->contains(fn ($f) => ($catalog[$f][1] ?? null) === 'error');

    // Sort so the most serious flags come first
    $rank  = ['error' => 0, 'mismatch' => 1, 'warning' => 2];
    $flags = $flags->sortBy(fn ($f) => $rank[$catalog[$f][1] ?? 'warning'] ?? 3)->values();

    $tone = $isClean ? 'success' : ($isEmpty ? 'neutral' : ($hasError ? 'error' : 'warning'));

    $banner = [
        'success' => ['bg-emerald-50 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300', 'Looks consistent', 'The name and birthdate on the ID appear to match what the citizen entered.'],
        'neutral' => ['bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300', $status ? 'No result available' : 'Not processed yet', $status ? 'Review the document manually.' : 'Results will appear here once the check has run.'],
        'warning' => ['bg-amber-50 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300', $flags->count() . ' ' . Str::plural('item', $flags->count()) . ' to review', 'Possible differences between the entered details and the ID.'],
        'error'   => ['bg-red-50 text-red-800 dark:bg-red-500/10 dark:text-red-300', 'Automatic check incomplete', 'Some checks could not run. Review the document manually.'],
    ][$tone];

@endphp

<div class="space-y-3 text-sm">

    {{-- Summary --}}
    <div class="flex items-start gap-3 rounded-md p-3 {{ $banner[0] }}" role="status">
        <span class="mt-0.5 shrink-0" aria-hidden="true">
            @if ($tone === 'success')
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            @elseif ($tone === 'neutral')
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            @else
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
            @endif
        </span>
        <div>
            <p class="font-semibold">{{ $banner[1] }}</p>
            <p class="mt-0.5 text-xs opacity-90">{{ $banner[2] }}</p>
        </div>
    </div>

    {{-- Flags --}}
    @if ($flags->isNotEmpty())
        <ul class="divide-y divide-slate-100 rounded-md border border-slate-200 dark:divide-slate-800 dark:border-slate-800">
            @foreach ($flags as $flag)
                @php [$message, $severity, $hint] = $catalog[$flag] ?? [$flag, 'warning', null]; @endphp
                <li class="flex items-start gap-3 px-3 py-2.5">
                    <svg class="mt-0.5 size-4 shrink-0 text-slate-500 dark:text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                    <div class="min-w-0">
                        <p class="font-medium text-slate-900 dark:text-(--white)">{{ $message }}</p>
                        @if ($hint)
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $hint }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    {{-- Disclaimer + PhilSys check --}}
    <div class="space-y-1.5 border-t border-slate-100 pt-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
        <p>This check is assistive only and can be wrong. Staff make the final decision.</p>
        <p>
            For a PhilSys National ID, also scan its QR code at
            <a href="https://everify.gov.ph/check" target="_blank" rel="noopener noreferrer"
               class="font-semibold text-[#17325c] underline underline-offset-2 hover:text-[#0a1f6b] dark:text-sky-300 dark:hover:text-sky-200">National ID Check<span class="sr-only"> (opens in a new tab)</span><svg class="ml-1 inline size-3 align-[-1px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg></a>.
        </p>
    </div>
</div>