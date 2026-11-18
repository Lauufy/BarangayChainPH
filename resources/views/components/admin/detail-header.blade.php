@props([
    'title',
    'subtitle' => null,
    'crumbs' => [],   // [['label' => 'Services', 'href' => '/services'], ['label' => 'Current page']]
])

<header {{ $attributes->class('flex flex-wrap items-end justify-between gap-x-4 gap-y-3') }}>
    <div class="min-w-0">
        @if ($crumbs)
            <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                @foreach ($crumbs as $crumb)
                    @if (! $loop->first)
                        <span aria-hidden="true">&rsaquo;</span>
                    @endif

                    @if (! $loop->last && isset($crumb['href']))
                        <a href="{{ $crumb['href'] }}" class="hover:text-slate-900 hover:underline dark:hover:text-(--celadon-300)">
                            {{ $crumb['label'] }}
                        </a>
                    @else
                        <span class="font-semibold text-slate-900 dark:text-(--celadon-500)/70" @if ($loop->last) aria-current="page" @endif>
                            {{ $crumb['label'] }}
                        </span>
                    @endif
                @endforeach
            </nav>
        @endif

        <h1 class="mt-1 truncate text-2xl font-extrabold leading-tight text-slate-900 sm:text-3xl dark:text-(--celadon-500)">
            {{ $title }}
        </h1>

        @if ($subtitle)
            <p class="mt-0.5 text-sm text-slate-600">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex shrink-0 items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</header>