@props([
    'label',
    'title',
    'text',
    'exportUrl' => null,
    'exportLabel' => 'Export',
    'confirmTitle' => 'Export this list?',
    'confirmText' => 'The file may contain personal information. Keep it safe and share it only with authorized people.',
])

<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="min-w-0">
        <p class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">{{ $label }}</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $title }}</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $text }}</p>
    </div>

    @if ($exportUrl || !$slot->isEmpty())
        <div class="flex flex-wrap items-center gap-2">
            {{-- Optional extra buttons --}}
            {{ $slot }}

            @if ($exportUrl)
                <a href="{{ $exportUrl }}" data-title="{{ $confirmTitle }}" data-text="{{ $confirmText }}"
                    onclick="event.preventDefault(); var a = this; tConfirmation({ title: a.dataset.title, text: a.dataset.text, confirmLabel: 'Yes, export' }).then(function (ok) { if (ok) window.location = a.href; });"
                    class="inline-flex h-10 items-center gap-2 whitespace-nowrap rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        <path d="m7 10 5 5 5-5" />
                        <path d="M12 15V3" />
                    </svg>
                    {{ $exportLabel }}
                </a>
            @endif
        </div>
    @endif
</div>