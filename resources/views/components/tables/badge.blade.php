@props(['variant' => 'neutral'])

@php
    $styles = [
        'success' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
        'danger'  => 'bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300',
        'warning' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
        'info'    => 'bg-sky-50 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
        'neutral' => 'bg-slate-100 text-slate-600 dark:bg-slate-700/60 dark:text-slate-300',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center whitespace-nowrap rounded-md px-2.5 py-1 text-xs font-medium capitalize', $styles[$variant] ?? $styles['neutral']]) }}>
    {{ $slot }}
</span>