@props([
    'label',
    'icon',
    'href' => null,
    'action' => null,
    'method' => 'POST',
    'variant' => 'default',
    'confirm' => null,
    'confirmTitle' => 'Confirm action',
    'confirmLabel' => 'Confirm',
    'cancelLabel' => 'Cancel',
    'confirmVariant' => 'default',
    'iconOnly' => false,
])

@php
    $inner =
        [
            'file-search' =>
                '<path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M4.268 21a2 2 0 0 0 1.727 1H18a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v3"/><path d="m9 18-1.5-1.5"/><circle cx="5" cy="14" r="3"/>',
            'pencil' =>
                '<path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.375 2.625a1 1 0 0 1 3 3l-9.013 9.014a2 2 0 0 1-.853.505l-2.873.84a.5.5 0 0 1-.62-.62l.84-2.873a2 2 0 0 1 .506-.852z"/>',
            'trash' =>
                '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/>',
            'shield' =>
                '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
            'power' => '<path d="M12 2v10"/><path d="M18.4 6.6a9 9 0 1 1-12.77.04"/>',
            'message' =>
                '<path d="M22 17a2 2 0 0 1-2 2H6.828a2 2 0 0 0-1.414.586l-2.202 2.202A.71.71 0 0 1 2 21.286V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2z"/>',
            'printer' =>
                '<path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6"/><rect x="6" y="14" width="12" height="8" rx="1"/>',
        ][$icon] ?? '';

    $svg =
        '<svg class="size-4.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' .
        $inner .
        '</svg>';

    $colors = [
        'file-search' =>
            'bg-sky-50 text-sky-700 hover:bg-sky-100 dark:bg-sky-500/10 dark:text-sky-300 dark:hover:bg-sky-500/20',
        'pencil' =>
            'bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-500/20',
        'trash' =>
            'bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-300 dark:hover:bg-rose-500/20',
        'shield' =>
            'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:bg-emerald-500/20',
        'power' =>
            'bg-orange-50 text-orange-700 hover:bg-orange-100 dark:bg-orange-500/10 dark:text-orange-300 dark:hover:bg-orange-500/20',
        'message' =>
            'bg-violet-50 text-violet-700 hover:bg-violet-100 dark:bg-violet-500/10 dark:text-violet-300 dark:hover:bg-violet-500/20',
        'printer' =>
            'bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-300 dark:hover:bg-indigo-500/20',

        // Explicit variants still win over the icon color
        'success' =>
            'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:bg-emerald-500/20',
        'danger' =>
            'bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-300 dark:hover:bg-rose-500/20',

        // Fallback
        'default' =>
            'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-[#17325c] dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-sky-300',
    ];

    $color = $colors[$variant !== 'default' ? $variant : $icon] ?? $colors['default'];

    $btn =
        ($iconOnly
            ? 'grid size-8 place-items-center rounded-md text-slate-500 dark:text-slate-400'
            : 'inline-flex items-center justify-center rounded-md px-3 py-1.5 text-sm font-medium') .
        ' transition focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 ' .
        $color;
@endphp

<div class="group relative inline-flex">
    @if ($action)
        <form action="{{ $action }}" method="POST"
            @if ($confirm)
                data-confirm-title="{{ $confirmTitle }}"
                data-confirm-text="{{ $confirm }}"
                data-confirm-label="{{ $confirmLabel }}"
                data-cancel-label="{{ $cancelLabel }}"
                data-confirm-variant="{{ $confirmVariant }}"
                onsubmit="event.preventDefault(); var form = this; tConfirmation({ title: form.dataset.confirmTitle, text: form.dataset.confirmText, confirmLabel: form.dataset.confirmLabel, cancelLabel: form.dataset.cancelLabel, confirmVariant: form.dataset.confirmVariant }).then(function (ok) { if (ok) form.submit(); }); return false;"
            @endif>
            @csrf
            @if (strtoupper($method) !== 'POST')
                @method($method)
            @endif
            <button type="submit" aria-label="{{ $label }}" {{ $attributes->class($btn) }}>
                @if ($iconOnly)
                    {!! $svg !!}
                @else
                    {{ $label }}
                @endif
            </button>
        </form>
    @elseif ($href)
        <a href="{{ $href }}" aria-label="{{ $label }}" {{ $attributes->class($btn) }}>
            @if ($iconOnly)
                {!! $svg !!}
            @else
                {{ $label }}
            @endif
        </a>
    @else
        <button type="button" aria-label="{{ $label }}" {{ $attributes->class($btn) }}>
            @if ($iconOnly)
                {!! $svg !!}
            @else
                {{ $label }}
            @endif
        </button>
    @endif

    @if ($iconOnly)
        <span role="tooltip"
            class="pointer-events-none absolute -top-8 left-1/2 z-10 -translate-x-1/2 whitespace-nowrap rounded bg-slate-900 px-2 py-1 text-xs font-medium text-white opacity-0 transition group-focus-within:opacity-100 group-hover:opacity-100 dark:bg-slate-100 dark:text-slate-900">{{ $label }}</span>
    @endif
</div>
