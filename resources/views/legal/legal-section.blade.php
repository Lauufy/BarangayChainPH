@props(['title'])

{{-- One section of a legal page --}}
<section>
    <h2 class="mb-3 text-lg font-semibold text-white">
        {{ $title }}
    </h2>

    <div class="space-y-3 text-sm leading-6 text-slate-300">
        {{ $slot }}
    </div>
</section>