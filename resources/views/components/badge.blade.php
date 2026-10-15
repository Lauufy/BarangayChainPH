@props([
    'variant' => 'neutral',
])

<span {{ $attributes->class([
    'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold capitalize',
    'bg-green-100 text-green-700' => $variant === 'success',
    'bg-red-100 text-red-700' => $variant === 'danger',
    'bg-amber-100 text-amber-700' => $variant === 'warning',
    'bg-sky-100 text-sky-700' => $variant === 'info',
    'bg-slate-100 text-slate-700' => ! in_array($variant, ['success', 'danger', 'warning', 'info']),
]) }}>
    {{ $slot }}
</span>