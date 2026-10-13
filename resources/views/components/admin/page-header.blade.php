@props(['label', 'title', 'text'])

<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <p class="text-xs font-bold uppercase tracking-widest text-emerald-600">{{ $label }}</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ $title }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $text }}</p>
    </div>

    {{-- Optional button on the right --}}
    {{ $slot }}
</div>