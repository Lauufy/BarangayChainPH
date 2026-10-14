
@props([
    'type' => 'button',
    'variant' => 'primary',
    'color' => '#059669',
    'hoverColor' => null,
    'loadingText' => 'Loading...',
    'loading' => false,
])

@php
    $isCustomColor = str_starts_with($color, '#');

    $colors = [
        'green' => ['bg-green-600', 'hover:bg-green-700'],
        'emerald' => ['bg-emerald-600', 'hover:bg-emerald-700'],
        'blue' => ['bg-blue-600', 'hover:bg-blue-700'],
        'indigo' => ['bg-indigo-600', 'hover:bg-indigo-700'],
        'red' => ['bg-red-600', 'hover:bg-red-700'],
        'amber' => ['bg-amber-600', 'hover:bg-amber-700'],
        'slate' => ['bg-slate-600', 'hover:bg-slate-700'],
    ];

    $variantClasses = [
        'primary' => 'text-white',
        'secondary' => 'border border-white/10 text-white',
        'danger' => 'bg-red-300 text-white hover:bg-red-700',
    ];

    $buttonClass = $variantClasses[$variant] ?? $variantClasses['primary'];
    $style = $attributes->get('style', '');

    if ($variant !== 'danger') {
        if ($isCustomColor) {
            $style = trim($style . '; background-color: ' . $color, '; ');

            if ($hoverColor && str_starts_with($hoverColor, '#')) {
                $style = trim($style . '; --button-hover-color: ' . $hoverColor, '; ');
            }

            $buttonClass .= ' custom-button-color';
        } else {
            $selectedColor = $colors[$color] ?? $colors['emerald'];

            $buttonClass .= ' ' . implode(' ', $selectedColor);
        }
    }

    $attributes = $attributes->except(['style']);
@endphp

<button
    type="{{ $type }}"
    data-loading
    data-loading-text="{{ $loadingText }}"
    @if ($style !== '')
        style="{{ $style }}"
    @endif
    {{ $attributes->class([
        'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-3.5 text-sm font-semibold',
        'transition-all duration-200 ease-in-out',
        'hover:-translate-y-1 hover:shadow-lg',
        'active:translate-y-0 active:scale-[0.98]',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400',
        'focus-visible:ring-offset-2 focus-visible:ring-offset-[#020A18]',
        'disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0 disabled:hover:shadow-none',
        $buttonClass,
    ]) }}
    @if ($loading) disabled @endif
>
    <span class="button-content inline-flex items-center justify-center gap-2">
        @if ($loading)
            <x-loading-indicator />
            {{ $loadingText }}
        @else
            {{ $slot }}
        @endif
    </span>
</button>
