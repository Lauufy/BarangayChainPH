{{--
    Blueprint background. Usage:

    As a background layer:
        <section class="relative isolate overflow-hidden">
            <x-partials.blueprint src="{{ asset('img/photos/photo.jpg') }}" class="absolute inset-0 -z-20" />
        </section>

    As a box with content:
        <x-partials.blueprint class="relative rounded-xl p-8 text-white">Content</x-partials.blueprint>

    Grid only:
        <x-partials.blueprint class="relative h-64" />

    Props:
        src   image URL (optional)
        base  Tailwind background classes (default navy in light mode, olive in dark)
        grid  big grid square size in px (default 96)
        pos   object-position class for the image (default object-right)
--}}
@props([
    'src' => null,
    'base' => 'bg-(--blue-900) dark:bg-(--green-900)',
    'grid' => 96,
    'pos' => 'object-right',
])

<div {{ $attributes->class([$base, '[--line:255,255,255]']) }}>
    @if ($src)
        <img src="{{ $src }}" alt="" loading="lazy"
            class="absolute inset-0 h-full w-full {{ $pos }} object-cover opacity-80 mix-blend-luminosity contrast-125 grayscale">

        <div class="absolute inset-0 bg-(--blue-950)/50 dark:bg-black/60"></div>
    @endif

    <div aria-hidden="true" class="absolute inset-0"
        style="
            background-image:
                linear-gradient(rgba(var(--line),.14) 1px, transparent 1px),
                linear-gradient(90deg, rgba(var(--line),.14) 1px, transparent 1px),
                linear-gradient(rgba(var(--line),.07) 1px, transparent 1px),
                linear-gradient(90deg, rgba(var(--line),.07) 1px, transparent 1px);
            background-size:
                {{ $grid }}px {{ $grid }}px,
                {{ $grid }}px {{ $grid }}px,
                {{ $grid / 4 }}px {{ $grid / 4 }}px,
                {{ $grid / 4 }}px {{ $grid / 4 }}px;
        ">
    </div>

    @if (! $slot->isEmpty())
        <div class="relative">{{ $slot }}</div>
    @endif
</div>