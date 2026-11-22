@props([
    'timezone' => 'Asia/Manila',
    'showTime' => true,
])

@php
    // Server-rendered first value, so nothing flashes before the JS runs
    $now = now($timezone);
@endphp

<div data-live-date data-timezone="{{ $timezone }}"
    {{ $attributes->class([
        'flex' => ! $attributes->has('class'),
        'items-center gap-3 rounded-md border border-(--gray-200) bg-(--white) px-4 py-2 text-sm shadow-sm',
        'dark:border-(--gray-700) dark:bg-(--gray-800)',
    ]) }}>

    {{-- Calendar icon --}}
    <svg class="h-5 w-5 shrink-0 text-(--blue-700) dark:text-(--blue-300)" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect x="3" y="4" width="18" height="18" rx="2" />
        <path d="M16 2v4M8 2v4M3 10h18" />
    </svg>

    {{-- Date: DD / MM / YYYY --}}
    <p class="flex items-center gap-1.5 font-semibold tabular-nums text-(--gray-900) dark:text-(--gray-50)">
        <span data-part="day">{{ $now->format('d') }}</span>
        <span class="text-(--gray-400)">/</span>
        <span data-part="month">{{ $now->format('m') }}</span>
        <span class="text-(--gray-400)">/</span>
        <span data-part="year">{{ $now->format('Y') }}</span>
    </p>

    {{-- Time --}}
    @if ($showTime)
        <span class="h-4 border-l border-(--gray-200) dark:border-(--gray-700)" aria-hidden="true"></span>

        <p class="flex items-center gap-1 font-medium tabular-nums text-(--gray-500) dark:text-(--gray-400)">
            <span data-part="hour">{{ $now->format('h') }}</span>
            <span>:</span>
            <span data-part="minute">{{ $now->format('i') }}</span>
            <span>:</span>
            <span data-part="second">{{ $now->format('s') }}</span>
            <span data-part="dayPeriod" class="ml-1">{{ $now->format('A') }}</span>
        </p>
    @endif
</div>

@once
    <script>
        (function () {
            function tick() {
                document.querySelectorAll('[data-live-date]').forEach(function (box) {
                    const formatter = new Intl.DateTimeFormat('en-US', {
                        timeZone: box.dataset.timezone,
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: true,
                    });

                    formatter.formatToParts(new Date()).forEach(function (part) {
                        const el = box.querySelector('[data-part="' + part.type + '"]');
                        if (el) el.textContent = part.value;
                    });
                });
            }

            tick();
            setInterval(tick, 1000);
        })();
    </script>
@endonce