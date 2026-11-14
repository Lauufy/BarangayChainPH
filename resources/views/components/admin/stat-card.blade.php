@props([
    'label',
    'subtitle' => null,          // small text under the title
    'value' => '—',
    'trend' => null,             // percent, e.g. 3.1 or -2.1
    'latest' => null,            // Carbon/DateTime or date string
    'latestLabel' => 'Latest',
    'emptyText' => 'No records yet',
    'dateFormat' => 'M d, Y g:i A',
    'note' => null,              // footer fallback when there's no date
    'href' => null,              // makes the whole card a link
    'invert' => false,           // true when "down" is good (e.g. pending counts)
    'mono' => false,
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
        'block min-w-0 rounded-2xl border p-4 shadow-sm sm:p-5',
        'border-(--gray-200) bg-(--gray-50)',
        'dark:border-white/10 dark:bg-(--blue-900)',
        'transition-all duration-200 hover:-translate-y-0.5 hover:border-(--blue-300) hover:shadow-md dark:hover:border-white/25' => $href,
        'transition-colors duration-300' => ! $href,
    ]) }}>

    {{-- Header --}}
    <div class="flex items-start justify-between gap-2 sm:gap-3">
        <div class="min-w-0">
            <p class="truncate text-[clamp(0.6875rem,0.66rem+0.1vw,0.8125rem)] font-bold uppercase tracking-wide text-(--gray-900) dark:text-(--white)">
                {{ $label }}
            </p>
            @if ($subtitle)
                <p class="truncate text-[clamp(0.625rem,0.6rem+0.1vw,0.75rem)] text-(--gray-500) dark:text-(--blue-200)">
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        @isset($icon)
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-(--gray-200) bg-(--white) text-(--blue-700) sm:h-10 sm:w-10 dark:border-white/10 dark:bg-white/10 dark:text-(--white)">
                {{ $icon }}
            </span>
        @endisset
    </div>

    {{-- Value --}}
    <p title="{{ $value }}" @class([
        'mt-2 truncate text-stat tabular-nums text-(--gray-900) xl:text-[clamp(2rem,1.2rem+1.4vw,2.75rem)] dark:text-(--white)',
        'font-mono' => $mono,
    ])>
        {{ $value }}
    </p>

    {{-- Footer --}}
    @if ($showFooter)
        <div class="mt-2 flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
            <p class="min-w-0 truncate text-caption text-(--gray-500) dark:text-(--blue-200)">
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
                    'inline-flex shrink-0 items-center gap-1 rounded-full bg-(--gray-100) px-2 py-0.5 text-caption font-semibold dark:bg-white/10',
                    'text-(--green-600) dark:text-(--green-300)' => $isGood,
                    'text-(--red-600) dark:text-(--red-500)' => ! $isGood,
                ])>
                    <svg @class(['h-2.5 w-2.5', 'rotate-180' => ! $isUp]) viewBox="0 0 10 10" fill="currentColor" aria-hidden="true">
                        <path d="M5 1.5 9 8H1l4-6.5Z" />
                    </svg>
                    {{ $isUp ? '+' : '-' }}{{ number_format(abs($trend), 1) }}%
                </span>
            @endif
        </div>
    @endif
</{{ $tag }}>