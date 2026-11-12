{{-- <x-tables.badge variant="success">Approved</x-tables.badge>   success | danger | warning | info | neutral. Add `labeled` to keep the text visible on mobile (icon-only by default). --}}
@props(['variant' => 'neutral', 'labeled' => false])

@php
    // [color classes (light + dark), icon shapes]
    $styles = [
        'success' => ['bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300', '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>'],
        'danger'  => ['bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300', '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/>'],
        'info'    => ['bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300', '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>'],
        'warning' => ['bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300', '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>'],
        'neutral' => ['bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300', '<rect x="2" y="3" width="20" height="5" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8M10 12h4"/>'],
    ];
    [$color, $icon] = $styles[$variant] ?? $styles['neutral'];
@endphp

<span {{ $attributes->class(['inline-flex items-center justify-center whitespace-nowrap rounded-md text-xs font-semibold', $labeled ? 'gap-1.5 px-2 py-1 sm:w-28' : 'size-7 sm:size-auto sm:w-28 sm:gap-1.5 sm:px-2 sm:py-1', $color]) }}>
    <svg class="size-4 shrink-0 sm:size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icon !!}</svg>
    <span class="{{ $labeled ? '' : 'sr-only sm:not-sr-only' }}">{{ $slot }}</span>
</span>