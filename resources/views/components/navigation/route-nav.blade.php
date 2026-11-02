{{--
    Route navigation.

    Usage:
        <x-navigation.route-nav :items="$navLinks" />

    Props:
        items  array of route items with route, text, and optional active pattern
        label  aria-label for the nav (default: "Page navigation")

    Extra attributes (class, id, etc.) are merged onto the <nav>.
--}}

@props([
    'items' => [],
    'label' => 'Page navigation',
])

@php
    $navLink =
        'relative inline-flex items-center whitespace-nowrap py-3 text-sm font-medium text-slate-600 transition-colors duration-200 hover:text-blue-900 focus-visible:text-blue-900 aria-[current=page]:font-semibold aria-[current=page]:text-blue-900 after:absolute after:inset-x-0 after:bottom-0 after:h-0.5 after:origin-left after:scale-x-0 after:bg-emerald-600 after:transition-transform after:duration-200 hover:after:scale-x-100 focus-visible:after:scale-x-100 aria-[current=page]:after:scale-x-100 focus-visible:outline-2 focus-visible:outline-offset-3 focus-visible:outline-emerald-600';
@endphp

<nav aria-label="{{ $label }}" {{ $attributes }}>
    <div class="flex items-center gap-5">
        @foreach ($items as $item)
            @php
                $isActive = request()->routeIs($item['active'] ?? $item['route']);
            @endphp

            <a
                href="{{ route($item['route']) }}"
                class="{{ $navLink }}"
                @if ($isActive) aria-current="page" @endif
            >
                {{ $item['text'] }}
            </a>
        @endforeach
    </div>
</nav>
