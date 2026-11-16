{{--
    Pager: "Showing [n] items per page" on the left, arrows / numbers on the right.
    Props: paginator, perPageOptions, perPageModel.
--}}
@props([
    'paginator' => null,
    'perPageOptions' => [10, 15, 25, 50],
    'perPageModel' => null,
])

@php
    $iPrev = '<svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>';
    $iNext = '<svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>';
    $iChevron = '<svg class="size-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
@endphp

@if ($paginator)
    <div class="flex flex-col gap-3 px-4 py-3 text-sm text-slate-500 dark:text-slate-400 sm:flex-row sm:items-center sm:justify-between border-t border-slate-200 dark:border-slate-700">
        {{-- Left: Showing [n] items per page --}}
        <div>
            @if (count($perPageOptions))
                <form method="GET" class="flex items-center gap-2" @if ($perPageModel) onsubmit="return false" @endif>
                    @unless ($perPageModel)
                        @foreach (request()->except(['per_page', 'page', 'search']) as $k => $v)
                            @if (is_scalar($v))
                                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                            @endif
                        @endforeach
                    @endunless
                    <label for="per-page-{{ $perPageModel ?? 'select' }}">Showing</label>
                    <span class="relative">
                        <select id="per-page-{{ $perPageModel ?? 'select' }}"
                            class="h-8 appearance-none rounded-lg border border-slate-200 bg-white pl-3 pr-7 text-sm font-medium text-slate-900 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                            @if ($perPageModel) wire:model.live="{{ $perPageModel }}"
                            @else name="per_page" onchange="this.form.submit()" @endif>
                            @foreach ($perPageOptions as $n)
                                <option value="{{ $n }}" @selected(!$perPageModel && (int) request('per_page', $paginator->perPage()) === $n)>{{ $n }}</option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-slate-500 dark:text-slate-400">{!! $iChevron !!}</span>
                    </span>
                    <span>items per page</span>
                </form>
            @endif
        </div>

        {{-- Right: <  1 2 3 ... 6  > --}}
        @if ($paginator->lastPage() >= 1)
            @php
                $current = $paginator->currentPage();
                $last = $paginator->lastPage();
                $show = collect([1, 2, 3, $last - 1, $last, $current - 1, $current, $current + 1])
                    ->filter(fn($n) => $n >= 1 && $n <= $last)->unique()->sort()->values();

                $nav = 'grid size-9 place-items-center rounded-lg border transition focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500';
                $on = 'border-slate-200 bg-white text-slate-900 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800';
                $off = 'cursor-not-allowed border-slate-200 bg-white text-slate-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-600';
                $num = 'grid size-8 place-items-center rounded-md text-sm transition';
            @endphp

            <nav class="flex flex-wrap items-center gap-1.5" aria-label="Pagination">
                @if ($paginator->onFirstPage())
                    <span class="{{ $nav }} {{ $off }}">{!! $iPrev !!}</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" aria-label="Previous page" class="{{ $nav }} {{ $on }}">{!! $iPrev !!}</a>
                @endif

                <div class="mx-1 flex items-center gap-1">
                    @foreach ($show as $i => $n)
                        @if ($i > 0 && $n - $show[$i - 1] > 1)
                            <span class="px-1 text-slate-400 dark:text-slate-500" aria-hidden="true">...</span>
                        @endif
                        @if ($n === $current)
                            <span aria-current="page" class="{{ $num }} bg-violet-100 font-semibold text-violet-700 dark:bg-violet-500/20 dark:text-violet-300">{{ $n }}</span>
                        @else
                            <a href="{{ $paginator->url($n) }}" class="{{ $num }} text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">{{ $n }}</a>
                        @endif
                    @endforeach
                </div>

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" aria-label="Next page" class="{{ $nav }} {{ $on }}">{!! $iNext !!}</a>
                @else
                    <span class="{{ $nav }} {{ $off }}">{!! $iNext !!}</span>
                @endif
            </nav>
        @endif
    </div>
@endif