@props([
    'title' => 'Please fix the following:',
    'bag' => 'default',
    'light' => true, // false = dark theme
])

@php
    $all = $errors->getBag($bag)->all();
    $box = $light ? 'border-red-200 bg-red-50 text-red-700' : 'border-red-400/25 bg-red-500/10 text-red-200';
@endphp

@if ($all)
    <div role="alert" aria-live="assertive"
        {{ $attributes->class(["mb-4 flex items-start gap-2.5 rounded-md border border-l-4 border-l-red-500 py-2.5 pl-3 pr-2 text-xs leading-4 $box"]) }}>
        <svg class="mt-px size-4 shrink-0 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 8v4M12 16h.01" />
        </svg>

        <div class="min-w-0 flex-1">
            @if (count($all) === 1)
                <p class="wrap-break-word font-medium">{{ $all[0] }}</p>
            @else
                <p class="font-semibold">{{ $title }}</p>
                <ul class="mt-1 list-inside list-disc space-y-0.5 opacity-90">
                    @foreach ($all as $message)
                        <li class="wrap-break-word">{{ $message }}</li>
                    @endforeach
                </ul>
            @endif
        </div>

        <button type="button" aria-label="Dismiss" onclick="this.parentElement.remove()"
            class="rounded p-0.5 opacity-60 transition hover:opacity-100">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
            </svg>
        </button>
    </div>
@endif