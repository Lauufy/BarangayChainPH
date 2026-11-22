@props([
    'title' => 'Please fix the following:',
    'bag' => 'default',
])

@php
    $all = $errors->getBag($bag)->all();
@endphp

@if ($all)
    <div role="alert" aria-live="assertive"
        {{ $attributes->class([
            'mb-4 flex items-start gap-2.5 rounded-xl border-2 px-3.5 py-2.5 text-xs leading-4',
            'border-red-300 bg-transparent text-red-700',
            'dark:border-red-400/50 dark:text-red-300',
        ]) }}>
        <svg class="mt-px size-3.5 shrink-0 text-red-500 dark:text-red-400" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 8v4M12 16h.01" />
        </svg>

        <div class="min-w-0 flex-1">
            @if (count($all) === 1)
                <p class="wrap-break-word font-semibold">{{ $all[0] }}</p>
            @else
                <p class="font-semibold">{{ $title }}</p>
                <ul class="mt-1 list-inside list-disc space-y-0.5 font-medium opacity-90">
                    @foreach ($all as $message)
                        <li class="wrap-break-word">{{ $message }}</li>
                    @endforeach
                </ul>
            @endif
        </div>

        <button type="button" aria-label="Dismiss" onclick="this.parentElement.remove()"
            class="rounded p-0.5 text-red-400 transition hover:text-red-600 dark:text-red-300/70 dark:hover:text-red-200">
            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
            </svg>
        </button>
    </div>
@endif