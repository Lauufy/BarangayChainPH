{{--
    Reusable status badge.
    Save as: resources/views/components/admin/status-badge.blade.php
    Use as:  <x-admin.status-badge variant="success">Verified</x-admin.status-badge>

    Variants: warning, success, danger, info, neutral
    Size:     md (default, header size) or sm (tables and lists)
--}}
@props([
    'variant' => 'neutral',
    'size' => 'md',
])

@php
    $variants = [
        'warning' => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-400/10 dark:text-amber-400 dark:ring-amber-400/20',
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-400/10 dark:text-emerald-400 dark:ring-emerald-400/20',
        'danger' => 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-400/10 dark:text-red-400 dark:ring-red-400/20',
        'info' => 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-400/10 dark:text-sky-400 dark:ring-sky-400/20',
        'neutral' => 'bg-slate-50 text-slate-600 ring-slate-500/20 dark:bg-slate-400/10 dark:text-slate-300 dark:ring-slate-400/20',
    ];

    $sizes = [
        'md' => 'px-5 py-3',
        'sm' => 'px-2.5 py-1',
    ];
@endphp

<span {{ $attributes->class([
    'inline-flex items-center justify-center rounded-md text-xs font-semibold ring-1 ring-inset',
    $sizes[$size] ?? $sizes['md'],
    $variants[$variant] ?? $variants['neutral'],
]) }}>
    {{ $slot }}
</span>