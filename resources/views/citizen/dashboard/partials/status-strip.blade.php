<section
    class="border-(--blue-700)/10 text-(--blue-700) dark:bg-(--blue-860) dark:text-(--off-white) border-b bg-white dark:border-white/10">
    <div class="mx-auto max-w-7xl px-5 sm:px-8">
        <dl class="grid grid-cols-2 md:grid-cols-[repeat(auto-fit,minmax(10rem,1fr))]">
            @foreach ($cells as [$label, $value])
                <div
                    class="{{ $loop->index % 2 ? 'border-l' : '' }} {{ $loop->index >= 2 ? 'border-t' : '' }} {{ $loop->index > 0 ? 'md:border-l' : '' }} border-current/10 px-4 py-5 text-center sm:px-6 sm:py-6 md:border-t-0">
                    <dt class="text-current/65 text-xs font-semibold uppercase tracking-wider">{{ $label }}
                    </dt>
                    <dd class="mt-1.5 text-xl font-bold tabular-nums tracking-tight lg:text-2xl">
                        {{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</section>
