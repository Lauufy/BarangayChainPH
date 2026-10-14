@props([
    'variant' => 'neutral',
])

@php
    $variants = [
        'success' => 'border border-emerald-400/30 bg-emerald-500/10 text-emerald-300',
        'danger' => 'border border-red-400/30 bg-red-500/10 text-red-300',
        'warning' => 'border border-amber-400/30 bg-amber-500/10 text-amber-200',
        'info' => 'border border-sky-400/30 bg-sky-500/10 text-sky-200',
        'neutral' => 'border border-white/10 bg-white/5 text-slate-200',
    ];
@endphp

<span {{ $attributes->class([
    'inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.12em]',
    $variants[$variant] ?? $variants['neutral'],
]) }}>
    {{ $slot }}
</span>
