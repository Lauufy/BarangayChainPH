{{-- resources/views/components/admin/nav-link.blade.php --}}
@props(['href', 'match' => null, 'icon' => null])

@php
    $active = request()->is($match ?? ltrim($href, '/'));
@endphp

<a href="{{ $href }}" @if ($active) aria-current="page" @endif
    {{ $attributes->class([
        'sb-link group relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
        'bg-white/10 text-white' => $active,
        'text-slate-300 hover:bg-white/5 hover:text-white' => !$active,
    ]) }}>
    @if ($active)
        <span class="absolute -left-3.5 top-2 bottom-2 w-0.75 rounded-r bg-emerald-400" aria-hidden="true"></span>
    @endif
    <svg class="{{ $active ? 'text-emerald-400' : 'text-slate-400 group-hover:text-slate-200' }} h-4.5 w-4.5 shrink-0 transition-colors"
        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"
        stroke-linejoin="round" aria-hidden="true">
        {{ $icon }}
    </svg>
    <span class="sb-label truncate">{{ $slot }}</span>
</a>