{{--
    Blueprint background. Usage:

    As a background layer (parent must be `relative`):
        <section class="relative isolate overflow-hidden">
            <x-partials.blueprint src="{{ asset('img/photos/photo.jpg') }}" class="absolute inset-0 -z-20" />
            ...content...
        </section>

    As a box with content inside:
        <x-partials.blueprint class="relative rounded-xl p-8 text-white">Content</x-partials.blueprint>

    Grid only (no photo):
        <x-partials.blueprint class="relative h-64" />

    Colors: blue-900 in light mode, wine bottle green in dark mode (needs the `dark` class on <html>).

    Props:
        src   image URL (optional)
        base  Tailwind background classes for the base color (default blue-900, wine bottle green in dark)
        grid  big grid square size in px (default 96; fine grid is grid/4)
        pos   object-position class for the image (default object-right)
--}}
@props(['src' => null, 'base' => 'bg-blue-900 dark:bg-[#294833]', 'grid' => 96, 'pos' => 'object-right'])

<div {{ $attributes->class([$base, '[--line:255,255,255] dark:[--line:255,255,255]']) }}>
    @if ($src)
        <img src="{{ $src }}" alt="" loading="lazy"
            class="absolute inset-0 h-full w-full {{ $pos }} object-cover opacity-80 mix-blend-luminosity contrast-125 grayscale">
        <div class="absolute inset-0 bg-blue-900/50 dark:bg-[#0f1110]/60"></div>
    @endif

    <div aria-hidden="true" class="absolute inset-0"
        style="background-image: linear-gradient(rgba(var(--line),.14) 1px, transparent 1px), linear-gradient(90deg, rgba(var(--line),.14) 1px, transparent 1px), linear-gradient(rgba(var(--line),.07) 1px, transparent 1px), linear-gradient(90deg, rgba(var(--line),.07) 1px, transparent 1px); background-size: {{ $grid }}px {{ $grid }}px, {{ $grid }}px {{ $grid }}px, {{ $grid / 4 }}px {{ $grid / 4 }}px, {{ $grid / 4 }}px {{ $grid / 4 }}px;">
    </div>

    @if (! $slot->isEmpty())
        <div class="relative">{{ $slot }}</div>
    @endif
</div>