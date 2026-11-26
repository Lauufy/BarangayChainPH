@props([
    'mobile' => false,
])

@php
    $links = [
        ['citizen.dashboard', 'citizen.dashboard', 'Home'],
        ['citizen.services', 'citizen.services', 'Services'],
        ['citizen.applications', 'citizen.applications*', 'Applications'],
        ['citizen.documents', 'citizen.documents*', 'Documents'],
    ];
@endphp

@foreach ($links as [$routeName, $activePattern, $label])
    @if ($mobile)
        <a href="{{ route($routeName) }}"
            class="focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) hover:bg-(--blue-700)/10 dark:hover:bg-white/10 block w-full rounded-sm px-3 py-2.5 text-sm font-medium aria-[current=page]:bg-(--blue-700)/10 aria-[current=page]:font-semibold dark:aria-[current=page]:bg-white/10"
            @if (request()->routeIs($activePattern)) aria-current="page" @endif>{{ $label }}</a>
    @else
        <a href="{{ route($routeName) }}"
            class="focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--celadon-500) relative whitespace-nowrap rounded-sm py-1 text-sm font-medium transition-colors after:absolute after:inset-x-0 after:-bottom-0.5 after:h-0.5 after:origin-left after:scale-x-0 after:bg-current after:transition-transform hover:after:scale-x-100 aria-[current=page]:font-semibold aria-[current=page]:after:scale-x-100 dark:after:bg-(--celadon-500)"
            @if (request()->routeIs($activePattern)) aria-current="page" @endif>{{ $label }}</a>
    @endif
@endforeach
