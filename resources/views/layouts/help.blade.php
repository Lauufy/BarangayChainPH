@extends('layouts.citizen')

@section('flush', 'true')

@section('content')
    <header class="relative isolate overflow-hidden bg-(--blue-850) px-5 text-center text-white">
        <x-partials.blueprint aria-hidden="true" base="bg-(--celadon-500)" src="{{ asset('img/photos/ynares-hall-front.jpg') }}"
            pos="object-center lg:object-right" class="pointer-events-none absolute -inset-2 -z-20 blur-xs" />
        <div aria-hidden="true" class="absolute inset-0 -z-10 bg-linear-to-r from-(--blue-850) via-(--blue-850)/90 to-(--blue-850)/45"></div>
        <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 bottom-0 -z-10 h-12 bg-linear-to-b from-transparent to-(--off-white) sm:h-16 dark:to-(--blue-850)"></div>
        <div class="mx-auto max-w-3xl pt-12 pb-24 sm:pt-16 sm:pb-32">
            @hasSection('help-intro')
                @yield('help-intro')
            @else
                <a href="{{ route('citizen.help.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm text-white/85 hover:underline"><x-lucide-arrow-left aria-hidden="true" class="size-4" /> Help Center</a>
                <h1 class="text-balance text-3xl font-bold sm:text-4xl">@yield('title')</h1>
            @endif
        </div>
    </header>
    <div class="px-5 py-10 sm:px-8 sm:py-14">
        @yield('help-content')
    </div>
@endsection
