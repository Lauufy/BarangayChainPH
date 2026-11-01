{{--
    Header (title, search, filter, add) + body slot + footer (total, range, pagination).

    Works with plain Blade (GET query string) OR Livewire (wire:model):

    BLADE
    <x-tables.card title="System Accounts" description="..." :paginator="$users" item-label="accounts"
        search-placeholder="Search by name or email" add-label="Add New" :add-url="route('x.create')"
        :filters="[['name' => 'role', 'label' => 'Role', 'options' => ['staff' => 'Staff']]]">

    LIVEWIRE (keeps your existing properties; nothing is renamed)
    <x-tables.card ... search-model="search" add-wire="openCreate" per-page-model="perPage" :per-page-options="[10, 25, 50]"
        :filters="[['model' => 'role', 'label' => 'Role', 'options' => [...]]]">

    Add button: add-url (link) | add-onclick (JS, e.g. open a modal) | add-wire (Livewire method)
--}}
@props([
    'title',
    'description' => null,
    'paginator' => null,
    'total' => null,
    'itemLabel' => 'records',
    'searchable' => true,
    'searchName' => 'search',
    'searchModel' => null,
    'searchPlaceholder' => 'Search',
    'filters' => [], // name | model, label, options [value => label], placeholder, value (Livewire count)
    'addLabel' => 'Add New',
    'addUrl' => null,
    'addOnclick' => null,
    'addWire' => null,
    'perPageOptions' => [10, 15, 25, 50], // [] = hide the selector
    'perPageLabel' => 'Lines per page',
    'perPageModel' => null,
])

@php
    $total ??= $paginator?->total();
    $wire = $searchModel || collect($filters)->contains(fn($f) => isset($f['model']));
    $activeFilters = collect($filters)
        ->filter(fn($f) => filled($f['value'] ?? (isset($f['name']) ? request($f['name']) : null)))
        ->count();
    $hasAdd = $addUrl || $addOnclick || $addWire;

    $icon = fn($inner, $c = 'size-4') => '<svg class="' .
        $c .
        '" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' .
        $inner .
        '</svg>';
    $iSearch = '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>';
    $iFilter =
        '<path d="M10 20a1 1 0 0 0 .553.895l2 1A1 1 0 0 0 14 21v-7a2 2 0 0 1 .517-1.341L21.74 4.67A1 1 0 0 0 21 3H3a1 1 0 0 0-.742 1.67l7.225 7.989A2 2 0 0 1 10 14z"/>';
    $iPlus = '<path d="M5 12h14"/><path d="M12 5v14"/>';
    $iPrev = '<path d="m15 18-6-6 6-6"/>';
    $iNext = '<path d="m9 18 6-6-6-6"/>';
    $iUpDown = '<path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/>';

    $field =
        'h-9 rounded-lg border border-slate-300 bg-white text-sm text-slate-900 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20';
    $pageBtn =
        'grid size-8 place-items-center rounded-full text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600';
    $idle = 'text-slate-600 hover:bg-slate-100 hover:text-[#17325c]';
@endphp

<section class="rounded-xl border border-slate-200 bg-white">

    {{-- ===== Header ===== --}}
    <div class="flex flex-col gap-3 border-b border-slate-200 p-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="min-w-0">
            <h2 class="text-lg font-semibold text-[#17325c]">{{ $title }}</h2>
            @if ($description)
                <p class="mt-0.5 text-sm text-slate-500">{{ $description }}</p>
            @endif
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            @if ($searchable || count($filters))
                <form method="GET" class="flex flex-1 items-center gap-2"
                    @if ($wire) onsubmit="return false" @endif>
                    @if ($searchable)
                        <div class="relative flex-1 sm:w-60 sm:flex-none">
                            <span
                                class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">{!! $icon($iSearch) !!}</span>
                            <input type="search" placeholder="{{ $searchPlaceholder }}"
                                class="{{ $field }} w-full pl-9 pr-3 placeholder:text-slate-400"
                                @if ($searchModel) wire:model.live.debounce.300ms="{{ $searchModel }}"
                                @else name="{{ $searchName }}" value="{{ request($searchName) }}" @endif>
                        </div>
                    @endif

                    @if (count($filters))
                        <details class="group relative">
                            <summary
                                class="inline-flex h-9 cursor-pointer list-none items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 group-open:bg-slate-50 [&::-webkit-details-marker]:hidden">
                                {!! $icon($iFilter) !!} Filter
                                @if ($activeFilters)
                                    <span
                                        class="grid size-5 place-items-center rounded-full bg-emerald-600 text-xs font-semibold text-white">{{ $activeFilters }}</span>
                                @endif
                            </summary>

                            <div
                                class="absolute right-0 z-30 mt-2 w-64 space-y-3 rounded-lg border border-slate-200 bg-white p-3 shadow-lg">
                                @foreach ($filters as $f)
                                    <label class="block">
                                        <span
                                            class="mb-1 block text-xs font-medium text-slate-600">{{ $f['label'] }}</span>
                                        <select class="{{ $field }} w-full px-2.5"
                                            @if (isset($f['model'])) wire:model.live="{{ $f['model'] }}" @else name="{{ $f['name'] }}" @endif>
                                            <option value="">{{ $f['placeholder'] ?? 'All' }}</option>
                                            @foreach ($f['options'] as $value => $text)
                                                <option value="{{ $value }}" @selected(!isset($f['model']) && (string) request($f['name']) === (string) $value)>
                                                    {{ $text }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                @endforeach

                                @unless ($wire)
                                    <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-3">
                                        <a href="{{ url()->current() }}"
                                            class="px-2 py-1.5 text-sm font-medium text-slate-500 hover:text-[#17325c]">Reset</a>
                                        <button type="submit"
                                            class="rounded-lg bg-[#17325c] px-3.5 py-1.5 text-sm font-medium text-white transition hover:bg-[#10244a] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-1">Apply</button>
                                    </div>
                                @endunless
                            </div>
                        </details>
                    @endif
                </form>
            @endif

            @if ($hasAdd)
                @php $addClass = 'inline-flex h-9 items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-[#17325c] px-4 text-sm font-medium text-white transition hover:bg-[#10244a] focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-1'; @endphp
                @if ($addUrl)
                    <a href="{{ $addUrl }}" class="{{ $addClass }}">{!! $icon($iPlus) !!}
                        {{ $addLabel }}</a>
                @else
                    <button type="button" class="{{ $addClass }}"
                        @if ($addWire) wire:click="{{ $addWire }}" @endif
                        @if ($addOnclick) onclick="{{ $addOnclick }}" @endif>{!! $icon($iPlus) !!}
                        {{ $addLabel }}</button>
                @endif
            @endif
        </div>
    </div>

    <div class="p-2 sm:p-3">
        {{ $slot }}
    </div>

    {{-- ===== Footer ===== --}}
    @if ($paginator)
        <div
            class="flex flex-col gap-2 rounded-b-xl border-t border-slate-200 px-3 py-2 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-4">
            <p>Total <span class="ml-1 font-semibold text-slate-900">{{ number_format($total ?? 0) }}</span></p>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                @if (count($perPageOptions))
                    <form method="GET" class="flex items-center gap-2"
                        @if ($perPageModel) onsubmit="return false" @endif>
                        @unless ($perPageModel)
                            @foreach (request()->except(['per_page', 'page']) as $k => $v)
                                @if (is_scalar($v))
                                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                                @endif
                            @endforeach
                        @endunless
                        <label for="per-page-{{ $perPageModel ?? 'select' }}">{{ $perPageLabel }}</label>
                        <span class="relative">
                            <select id="per-page-{{ $perPageModel ?? 'select' }}"
                                class="h-7 appearance-none rounded-md border border-slate-300 bg-white pl-2 pr-6 text-xs font-semibold text-slate-900 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20"
                                @if ($perPageModel) wire:model.live="{{ $perPageModel }}"
                                @else name="per_page" onchange="this.form.submit()" @endif>
                                @foreach ($perPageOptions as $n)
                                    <option value="{{ $n }}" @selected(!$perPageModel && (int) request('per_page', $paginator->perPage()) === $n)>
                                        {{ $n }}</option>
                                @endforeach
                            </select>
                            <span
                                class="pointer-events-none absolute right-1.5 top-1/2 -translate-y-1/2 text-slate-500">{!! $icon($iUpDown, 'size-3') !!}</span>
                        </span>
                    </form>
                @endif

                @if ($paginator->lastPage() >= 1)
                    @php
                        $current = $paginator->currentPage();
                        $last = $paginator->lastPage();
                        $show = collect([1, 2, 3, $last - 1, $last, $current - 1, $current, $current + 1])
                            ->filter(fn($n) => $n >= 1 && $n <= $last)
                            ->unique()
                            ->sort()
                            ->values();
                        $arrowBtn =
                            'grid size-7 place-items-center rounded-md border border-slate-200 bg-white transition focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600';
                        $numBtn =
                            'grid size-7 place-items-center rounded-md text-xs font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600';
                    @endphp
                    <nav class="flex flex-wrap items-center gap-1" aria-label="Pagination">
                        @if ($paginator->onFirstPage())
                            <span
                                class="{{ $arrowBtn }} cursor-not-allowed text-slate-300">{!! $icon($iPrev, 'size-3.5') !!}</span>
                        @else
                            <a href="{{ $paginator->previousPageUrl() }}" aria-label="Previous page"
                                class="{{ $arrowBtn }} text-slate-600 hover:bg-slate-50 hover:text-[#17325c]">{!! $icon($iPrev, 'size-3.5') !!}</a>
                        @endif

                        @foreach ($show as $i => $n)
                            @if ($i > 0 && $n - $show[$i - 1] > 1)
                                <span class="grid size-7 place-items-center text-slate-400"
                                    aria-hidden="true">···</span>
                            @endif
                            @if ($n === $current)
                                <span aria-current="page"
                                    class="{{ $numBtn }} bg-[#17325c] font-semibold text-white">{{ $n }}</span>
                            @else
                                <a href="{{ $paginator->url($n) }}"
                                    class="{{ $numBtn }} text-slate-600 hover:bg-slate-100 hover:text-[#17325c]">{{ $n }}</a>
                            @endif
                        @endforeach

                        @if ($paginator->hasMorePages())
                            <a href="{{ $paginator->nextPageUrl() }}" aria-label="Next page"
                                class="{{ $arrowBtn }} text-slate-600 hover:bg-slate-50 hover:text-[#17325c]">{!! $icon($iNext, 'size-3.5') !!}</a>
                        @else
                            <span
                                class="{{ $arrowBtn }} cursor-not-allowed text-slate-300">{!! $icon($iNext, 'size-3.5') !!}</span>
                        @endif
                    </nav>
                @endif
            </div>
        </div>
    @endif
</section>
