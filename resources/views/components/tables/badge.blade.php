{{-- <x-tables.badge variant="success">Active</x-tables.badge>   success | danger | warning | info | neutral --}}
@props(['variant' => 'neutral'])

@php
    $map = [
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'danger' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
        'warning' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'info' => 'bg-sky-50 text-sky-700 ring-sky-600/20',
        'neutral' => 'bg-slate-100 text-slate-600 ring-slate-500/20',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset', $map[$variant] ?? $map['neutral']]) }}>
    <span class="size-1.5 rounded-full bg-current"></span>{{ $slot }}
</span>