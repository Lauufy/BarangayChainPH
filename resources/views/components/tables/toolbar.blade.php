@props([
    'key' => 'tbl',
    'tabs' => [],
    'active' => null,
    'total' => null,
    'totalLabel' => 'All',
    'totalIcon' => 'M3 7l9-5 9 5-9 5-9-5Zm0 5 9 5 9-5M3 17l9 5 9-5',
    'filters' => [],
    'search' => false,
    'searchPlaceholder' => 'Start searching...',
    'exportUrl' => null,
    'addLabel' => null,
    'addUrl' => null,
    'addOnclick' => null,
])

@php
    $tabParam = "{$key}_tab";
    $pageParam = "{$key}_page";

    // A single tab is shown the same way as a total.
    if (count($tabs) === 1) {
        $only = reset($tabs);
        $only = is_array($only) ? $only : ['label' => $only];
        $totalLabel = $only['label'];
        $total = $only['count'] ?? $total;
        $totalIcon = $only['icon'] ?? $totalIcon;
        $tabs = [];
    }
    $activeTab = $active ?? request($tabParam, array_key_first($tabs));

    $accent = 'bg-(--blue-600) hover:bg-(--blue-700) dark:bg-(--blue-500) dark:hover:bg-(--blue-400)';

    $pill = 'inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 text-sm font-medium text-slate-800 dark:text-slate-200 transition hover:bg-slate-50 dark:hover:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-(--blue-500)';
    $plus = '<svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>';
    $add = "inline-flex h-10 items-center gap-2 whitespace-nowrap rounded-lg px-5 text-sm font-medium text-white shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-(--blue-500) focus-visible:ring-offset-1 {$accent}";
@endphp

<div data-table-key="{{ $key }}" {{ $attributes->class('flex flex-wrap items-center justify-between gap-3 pb-2 sm:pb-3') }}>
    {{-- Left: tabs or total --}}
    <div class="flex flex-wrap items-center gap-2">
        @isset($navigation)
            {{ $navigation }}
        @endisset
        @if (count($tabs))
            <nav class="inline-flex flex-wrap items-center gap-0.5 rounded-lg bg-slate-100 p-1 dark:bg-slate-800" aria-label="Table views">
                @foreach ($tabs as $value => $tab)
                    @php
                        $isActive = (string) $activeTab === (string) $value;
                        $tab = is_array($tab) ? $tab : ['label' => $tab];
                    @endphp
                    <a href="{{ request()->fullUrlWithQuery([$tabParam => $value, $pageParam => null, 'page' => null, 'search' => null]) }}"
                        @if ($isActive) aria-current="page" @endif
                        class="inline-flex items-center gap-2 rounded-md px-4 py-1.5 text-sm transition {{ $isActive ? 'bg-white font-semibold text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white' : 'font-medium text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}">
                        @if (!empty($tab['icon']))
                            <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $tab['icon'] }}" /></svg>
                        @endif
                        {{ $tab['label'] }}
                        @isset($tab['count'])
                            <span class="rounded-md bg-slate-200 px-1.5 py-0.5 text-xs tabular-nums text-slate-700 dark:bg-slate-600 dark:text-slate-100">{{ $tab['count'] }}</span>
                        @endisset
                    </a>
                @endforeach
            </nav>
        @endif

        @if (!count($tabs) && $total !== null)
            {{-- Only one view, so it is always shown as active. --}}
            <span aria-current="page" class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-(--gray-100) px-3 text-sm font-semibold text-(--blue-800) dark:bg-(--blue-800) dark:text-(--blue-100)">
                <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $totalIcon }}" /></svg>
                {{ $totalLabel }}
                <span class="rounded-md bg-(--gray-200) px-1.5 py-0.5 text-xs tabular-nums dark:bg-(--blue-900)">{{ $total }}</span>
            </span>
        @endif
    </div>

    {{-- Right: search + filters + export + add --}}
    <div class="flex flex-wrap items-center gap-2">
        @if ($search)
            <x-tables.search :placeholder="$searchPlaceholder" />
        @endif

        @foreach ($filters as $filter)
            <x-tables.filter :param="$key . '_' . $filter['name']" :options="$filter['options']"
                :selected="$filter['selected'] ?? request($key . '_' . $filter['name'])"
                :label="$filter['label'] ?? 'Filter'" :title="$filter['title'] ?? null"
                :all-label="$filter['allLabel'] ?? 'All'" :reset="[$pageParam]" />
        @endforeach

        @isset($searchActions)
            {{ $searchActions }}
        @endisset

        @if ($exportUrl)
            <a href="{{ $exportUrl }}" class="{{ $pill }}">Export</a>
        @endif

        @if ($addLabel)
            @if ($addUrl)
                <a href="{{ $addUrl }}" class="{{ $add }}">{!! $plus !!} {{ $addLabel }}</a>
            @else
                <button type="button" class="{{ $add }}" @if ($addOnclick) onclick="{{ $addOnclick }}" @endif>{!! $plus !!} {{ $addLabel }}</button>
            @endif
        @endif
    </div>
</div>