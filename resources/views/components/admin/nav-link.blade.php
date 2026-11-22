{{-- resources/views/components/admin/nav-link.blade.php --}}
@props(['href', 'match' => null, 'icon' => null, 'badge' => null])

@php
    $active = request()->is($match ?? ltrim($href, '/'));

    // Tighter padding. No ring or outline.
    $base = 'sb-link group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-200 outline-none focus:outline-none focus-visible:outline-none focus-visible:ring-0';

    // Active, hover and focus all get a background color.
    $state = $active
        ? 'bg-[#1b2260] text-white'
        : 'text-[#e6e9f5] hover:bg-[#151b4d] hover:text-white focus:bg-[#1b2260] focus:text-white focus-visible:bg-[#1b2260] focus-visible:text-white';
@endphp

<a href="{{ $href }}" @if ($active) aria-current="page" @endif
    {{ $attributes->class([$base, $state]) }}>
    <svg class="sb-icon h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        {{ $icon }}
    </svg>

    <span class="sb-label flex-1 truncate">{{ $slot }}</span>

    @if ($badge)
        <span class="sb-label rounded-full bg-[#2f3fa8] px-2 py-0.5 font-mono text-[11px] font-semibold text-white">{{ $badge }}</span>
    @endif
</a>