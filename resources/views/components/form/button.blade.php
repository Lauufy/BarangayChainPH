@props([
    'type' => 'button',
    'variant' => 'primary',
    'loadingText' => 'Loading...',
    'loading' => false,
])

@php
    $variants = [
        'primary' => 'bg-emerald-600 text-white hover:bg-emerald-500',
        'secondary' => 'border border-white/10 bg-[#0B2347] text-white hover:bg-[#12315C]',
        'danger' => 'bg-red-600 text-white hover:bg-red-500',
    ];

    $buttonClass = $variants[$variant] ?? $variants['primary'];
@endphp

<button
    type="{{ $type }}"
    data-loading
    data-loading-text="{{ $loadingText }}"
    {{ $attributes->class([
        'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-3.5 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-70',
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
