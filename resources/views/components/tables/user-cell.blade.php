@props(['name', 'sub' => null, 'avatar' => null])

@php
    $palette = [
        'bg-indigo-100 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-300',
        'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-300',
        'bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-300',
        'bg-sky-100 text-sky-600 dark:bg-sky-500/20 dark:text-sky-300',
        'bg-rose-100 text-rose-600 dark:bg-rose-500/20 dark:text-rose-300',
        'bg-violet-100 text-violet-600 dark:bg-violet-500/20 dark:text-violet-300',
    ];
    $tone = $palette[crc32((string) $name) % count($palette)];
    $initials = collect(preg_split('/\s+/', trim((string) $name)))
        ->filter()->take(2)
        ->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))
        ->implode('');
@endphp

<div data-user-cell class="flex min-w-0 items-center gap-3">
    @if ($avatar)
        <img src="{{ $avatar }}" alt="" class="size-10 shrink-0 rounded-full object-cover">
    @else
        <span aria-hidden="true" class="grid size-10 shrink-0 place-items-center rounded-full text-xs font-semibold {{ $tone }}">{{ $initials }}</span>
    @endif

    <div class="min-w-0">
        <p class="truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{ $name }}</p>
        @if ($sub)
            <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $sub }}</p>
        @endif
    </div>
</div>