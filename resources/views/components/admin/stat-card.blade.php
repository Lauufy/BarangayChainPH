@props([
    'label',
    'subtitle' => null,          // small text under the value
    'value' => '—',
    'trend' => null,             // percent, e.g. 3.1 or -2.1
    'latest' => null,            // Carbon/DateTime or date string
    'latestLabel' => 'Latest',
    'emptyText' => 'No records yet',
    'dateFormat' => 'M d, Y',
    'note' => null,              // footer fallback when there's no date
    'href' => null,              // makes the whole card a link
    'invert' => false,           // true when "down" is good (e.g. pending counts)
    'mono' => false,
    'valueClass' => null,        // overrides the value color (e.g. status text)
])

@php
    $tag = $href ? 'a' : 'div';

    // Trend
    $hasTrend = is_numeric($trend);
    $isUp = $hasTrend && $trend >= 0;
    $isGood = $invert ? ! $isUp : $isUp;

    // Latest date: Carbon/DateTime, date string, or plain text
    $latestText = match (true) {
        $latest instanceof \DateTimeInterface => $latest->format($dateFormat),
        is_string($latest) && strtotime($latest) !== false => \Illuminate\Support\Carbon::parse($latest)->format($dateFormat),
        default => $latest,
    };

    // Zero value with no date -> show empty text
    $numeric = str_replace(',', '', (string) $value);
    $isEmpty = ! $latestText && is_numeric($numeric) && (float) $numeric === 0.0;

    $showFooter = $latestText || $isEmpty || $hasTrend || $note;
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class([
        'relative block min-w-0 bg-white px-4 py-3 dark:bg-slate-900',
        // Inset divider on the left edge (hidden on the first card and on the first card of each mobile row)
        'before:absolute before:inset-y-3 before:left-0 before:w-px before:bg-slate-200 dark:before:bg-slate-700',
        'first:before:hidden max-lg:odd:before:hidden',
        'transition-colors hover:bg-slate-50 dark:hover:bg-slate-800' => $href,
    ]) }}>

    {{-- Header: icon + label --}}
    <div class="flex items-center gap-2">
        @isset($icon)
            <span class="grid size-7 shrink-0 place-items-center rounded-md bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300">
                {{ $icon }}
            </span>
        @endisset
        <span class="wrap-anywhere sm:truncate text-sm font-medium text-slate-700 dark:text-slate-300">{{ $label }}</span>
    </div>

    {{-- Value --}}
    <p title="{{ $value }}" @class([
        'mt-2 wrap-anywhere sm:truncate text-xl font-bold tabular-nums',
        'text-slate-900 dark:text-slate-100' => ! $valueClass,
        $valueClass => $valueClass,
        'font-mono' => $mono,
    ])>
        {{ $value }}
    </p>

    {{-- Subtitle --}}
    @if ($subtitle)
        <p class="wrap-anywhere sm:truncate text-xs text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>
    @endif

    {{-- Footer: latest date, note, or trend --}}
    @if ($showFooter)
        <div class="mt-1 flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
            <p class="min-w-0 wrap-anywhere sm:truncate text-xs text-slate-500 dark:text-slate-400">
                @if ($isEmpty)
                    {{ $emptyText }}
                @elseif ($latestText)
                    {{ $latestLabel }} {{ $latestText }}
                @else
                    {{ $note }}
                @endif
            </p>

            @if ($hasTrend)
                <span @class([
                    'inline-flex shrink-0 items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold dark:bg-white/10',
                    'text-green-600 dark:text-green-300' => $isGood,
                    'text-red-600 dark:text-red-500' => ! $isGood,
                ])>
                    <svg @class(['h-2.5 w-2.5', 'rotate-180' => ! $isUp]) viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 4 22 20H2L12 4Z" />
                    </svg>
                    {{ $isUp ? '+' : '-' }}{{ number_format(abs($trend), 1) }}%
                </span>
            @endif
        </div>
    @endif
</{{ $tag }}>