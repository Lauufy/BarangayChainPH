@props([
    'title' => null,
    'description' => null,
    'paginator' => null,
    'total' => null,
    'itemLabel' => 'records',
    'searchable' => true,
    'searchName' => 'search',
    'searchModel' => null,
    'searchPlaceholder' => 'Search',
    'filters' => [],
    'keep' => [],
    'addLabel' => 'Add New',
    'addUrl' => null,
    'addOnclick' => null,
    'addWire' => null,
    'perPageOptions' => [10, 15, 25, 50],
    'perPageLabel' => 'Lines per page',
    'perPageModel' => null,
])

@php
    $wire = $searchModel || collect($filters)->contains(fn($f) => isset($f['model']));
    $activeFilters = collect($filters)
        ->filter(fn($f) => filled($f['value'] ?? (isset($f['name']) ? request($f['name']) : null)))
        ->count();
    $hasAdd = $addUrl || $addOnclick || $addWire;
    $hasToolbar = isset($toolbar) && !$toolbar->isEmpty();
    $hasHeader = $title || $description || $searchable || count($filters) || $hasAdd || $hasToolbar;

    $icon = fn($inner, $c = 'size-4') => '<svg class="' . $c . '" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $inner . '</svg>';
    $iSearch = '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>';
    $iFilter = '<path d="M10 20a1 1 0 0 0 .553.895l2 1A1 1 0 0 0 14 21v-7a2 2 0 0 1 .517-1.341L21.74 4.67A1 1 0 0 0 21 3H3a1 1 0 0 0-.742 1.67l7.225 7.989A2 2 0 0 1 10 14z"/>';
    $iPlus = '<path d="M5 12h14"/><path d="M12 5v14"/>';

    $field = 'h-10 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm text-slate-900 dark:text-slate-100 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20';
    $accent = 'bg-(--blue-600) hover:bg-(--blue-700) dark:bg-(--blue-500) dark:hover:bg-(--blue-400)';
@endphp

<section class="rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900">

    {{-- ===== Header ===== --}}
    @if ($hasHeader)
        <div class="flex flex-col gap-3 p-4 lg:flex-row lg:items-center lg:justify-between">
            @if ($title || $description)
                <div class="min-w-0">
                    @if ($title)
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $title }}</h2>
                    @endif
                    @if ($description)
                        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>
                    @endif
                </div>
            @endif

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center lg:ml-auto">
                {{-- Toolbar slot: sits on the same row as the title --}}
                @if ($hasToolbar)
                    <div class="w-full lg:w-auto">
                        {{ $toolbar }}
                    </div>
                @endif

                @if ($searchable || count($filters))
                    <form method="GET" class="flex flex-1 items-center gap-2"
                        @if ($wire) onsubmit="return false" @endif>
                        @foreach ($keep as $k => $v)
                            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                        @endforeach

                        @if ($searchable)
                            <div class="relative flex-1 sm:w-64 sm:flex-none">
                                <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500">{!! $icon($iSearch) !!}</span>
                                <input type="search" placeholder="{{ $searchPlaceholder }}"
                                    class="{{ $field }} w-full pl-10 pr-4 placeholder:text-slate-400 dark:placeholder:text-slate-500"
                                    @if ($searchModel) wire:model.live.debounce.300ms="{{ $searchModel }}"
                                    @else name="{{ $searchName }}" value="{{ request($searchName) }}" @endif>
                            </div>
                        @endif

                        @if (count($filters))
                            <details class="group relative">
                                <summary class="{{ $field }} inline-flex cursor-pointer list-none items-center gap-2 px-4 font-medium text-slate-700 dark:text-slate-200 transition hover:bg-slate-50 dark:hover:bg-slate-800/60 [&::-webkit-details-marker]:hidden">
                                    {!! $icon($iFilter) !!} Filter
                                    @if ($activeFilters)
                                        <span class="grid size-5 place-items-center rounded-md bg-violet-600 text-xs font-semibold text-white">{{ $activeFilters }}</span>
                                    @endif
                                </summary>

                                <div class="absolute right-0 z-30 mt-2 w-64 space-y-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-3 shadow-lg">
                                    @foreach ($filters as $f)
                                        <label class="block">
                                            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">{{ $f['label'] }}</span>
                                            <select class="{{ $field }} w-full px-3"
                                                @if (isset($f['model'])) wire:model.live="{{ $f['model'] }}" @else name="{{ $f['name'] }}" @endif>
                                                <option value="">{{ $f['placeholder'] ?? 'All' }}</option>
                                                @foreach ($f['options'] as $value => $text)
                                                    <option value="{{ $value }}" @selected(!isset($f['model']) && (string) request($f['name']) === (string) $value)>{{ $text }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                    @endforeach

                                    @unless ($wire)
                                        <div class="flex items-center justify-end gap-2 border-t border-slate-100 dark:border-slate-800 pt-3">
                                            <a href="{{ url()->current() }}" class="px-2 py-1.5 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100">Reset</a>
                                            <button type="submit" class="{{ $accent }} rounded-lg px-4 py-1.5 text-sm font-medium text-white transition focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500">Apply</button>
                                        </div>
                                    @endunless
                                </div>
                            </details>
                        @endif
                    </form>
                @endif

                @if ($hasAdd)
                    @php $addClass = "inline-flex h-10 items-center justify-center gap-2 whitespace-nowrap rounded-lg px-5 text-sm font-medium text-white shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 focus-visible:ring-offset-1 {$accent}"; @endphp
                    @if ($addUrl)
                        <a href="{{ $addUrl }}" class="{{ $addClass }}">{!! $icon($iPlus) !!} {{ $addLabel }}</a>
                    @else
                        <button type="button" class="{{ $addClass }}"
                            @if ($addWire) wire:click="{{ $addWire }}" @endif
                            @if ($addOnclick) onclick="{{ $addOnclick }}" @endif>{!! $icon($iPlus) !!} {{ $addLabel }}</button>
                    @endif
                @endif
            </div>
        </div>
    @endif

    <div class="p-2 sm:p-3">
        {{ $slot }}
    </div>

    {{-- ===== Footer (separate component) ===== --}}
    <x-tables.pager :paginator="$paginator" :total="$total" :item-label="$itemLabel" :per-page-options="$perPageOptions"
        :per-page-label="$perPageLabel" :per-page-model="$perPageModel" />
</section>