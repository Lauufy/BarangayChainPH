@props([
    'type' => 'button',
    'variant' => 'primary',
    'color' => 'emerald',
    'hoverColor' => null,
    'loadingText' => 'Loading...',
    'loading' => false,
])

@php
    // Named colors: [background, hover, focus ring]
    $palette = [
        'green' => ['bg-green-600', 'hover:bg-green-700', 'focus-visible:ring-green-400'],
        'emerald' => ['bg-emerald-600', 'hover:bg-emerald-700', 'focus-visible:ring-emerald-400'],
        'blue' => ['bg-blue-900', 'hover:bg-blue-800', 'focus-visible:ring-blue-400'],
        'indigo' => ['bg-indigo-600', 'hover:bg-indigo-700', 'focus-visible:ring-indigo-400'],
        'red' => ['bg-red-900', 'hover:bg-red-800', 'focus-visible:ring-red-400'],
        'amber' => ['bg-amber-700', 'hover:bg-amber-800', 'focus-visible:ring-amber-400'],
        'slate' => ['bg-slate-600', 'hover:bg-slate-700', 'focus-visible:ring-slate-400'],
    ];

    // Danger is always red, whatever color was passed
    $color = $variant === 'danger' ? 'red' : $color;
    $style = $attributes->get('style', '');

    if ($variant === 'secondary') {
        // Quiet button for dark surfaces; ignores $color
        $colorClass = 'border border-white/10 bg-white/5 text-white hover:bg-white/10 focus-visible:ring-white/40';
    } elseif (str_starts_with($color, '#')) {
        // Custom hex: colors come from CSS variables, hover defaults to a slightly darker mix
        $hover = $hoverColor ?: "color-mix(in srgb, {$color} 85%, black)";
        $style = trim("--btn-bg: {$color}; --btn-hover: {$hover}; {$style}");
        $colorClass = 'bg-[var(--btn-bg)] text-white hover:bg-[var(--btn-hover)] focus-visible:ring-[var(--btn-bg)]';
    } else {
        $colorClass = 'text-white ' . implode(' ', $palette[$color] ?? $palette['emerald']);
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
        'hover:scale-[1.03] hover:shadow-md',
        'active:scale-[0.98] active:shadow-none',
        'focus-visible:outline-none focus-visible:ring-2',
        'focus-visible:ring-offset-2 focus-visible:ring-offset-[#020A18]',
        'disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:scale-100 disabled:hover:shadow-none',
        $colorClass,
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