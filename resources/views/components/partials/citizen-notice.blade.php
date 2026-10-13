@props(['type' => 'blue', 'title'])

@php
    $colors = [
        'blue' => 'border-blue-200 bg-blue-50 text-blue-900',
        'amber' => 'border-amber-200 bg-amber-50 text-amber-900',
        'red' => 'border-red-200 bg-red-50 text-red-900',
        'green' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
    ];
@endphp

<section {{ $attributes->class(['rounded-md border p-4 sm:p-5', $colors[$type]]) }}>
    <h2 class="font-semibold">{{ $title }}</h2>
    <div class="mt-1 text-sm leading-6">{{ $slot }}</div>
</section>