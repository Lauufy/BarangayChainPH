{{--
    One row: tabs (+ filters) on the left, live table search + Export + Add on the right.
    Put it inside <x-tables.card :searchable="false"> above <x-tables.table>.

    :search="true"   adds a search box that filters the rows on screen (no page reload, no database)
--}}
@props([
    'key' => 'tbl',
    'tabs' => [],
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
    $activeTab = request($tabParam, array_key_first($tabs));

    $own = array_merge([$tabParam, $pageParam], collect($filters)->map(fn($f) => "{$key}_{$f['name']}")->all());
    $keep = collect(request()->except($own))->filter(fn($v) => is_scalar($v));

    $accent = 'bg-violet-600 hover:bg-violet-700 dark:bg-violet-500 dark:hover:bg-violet-400';
    $accentText = 'text-violet-700 dark:text-violet-300';
    $accentBorder = 'border-violet-600 dark:border-violet-400';

    $pill = 'inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 text-sm font-medium text-slate-800 dark:text-slate-200 transition hover:bg-slate-50 dark:hover:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500';
    $chevron = '<svg class="size-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
    $plus = '<svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>';
    $add = "inline-flex h-10 items-center gap-2 whitespace-nowrap rounded-lg px-5 text-sm font-medium text-white shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 focus-visible:ring-offset-1 {$accent}";
@endphp

@once
    <script>
        (function () {
            function runSearch(input) {
                var q = input.value.trim().toLowerCase();
                var scope = input.closest('section') || document;
                var clear = input.parentElement.querySelector('[data-table-search-clear]');
                if (clear) clear.style.display = q ? '' : 'none';

                var real = 0, shown = 0;
                scope.querySelectorAll('.tbl tbody > tr').forEach(function (tr) {
                    if (tr.hasAttribute('data-search-none') || tr.querySelector('[data-empty]')) return;
                    real++;
                    var match = !q || tr.textContent.toLowerCase().indexOf(q) !== -1;
                    tr.style.display = match ? '' : 'none';
                    if (match) shown++;
                });

                var none = scope.querySelector('tr[data-search-none]');
                if (real > 0 && q && shown === 0) {
                    if (!none) {
                        var tbody = scope.querySelector('.tbl tbody');
                        var cols = scope.querySelectorAll('.tbl thead th').length || 1;
                        none = document.createElement('tr');
                        none.setAttribute('data-search-none', '');
                        none.innerHTML = '<td colspan="' + cols + '" data-side="full" class="py-12! text-center text-sm text-slate-500 dark:text-slate-400"></td>';
                        tbody.appendChild(none);
                    }
                    none.firstChild.textContent = 'No matches for "' + input.value.trim() + '" on this page.';
                    none.style.display = '';
                } else if (none) {
                    none.style.display = 'none';
                }
            }

            document.addEventListener('input', function (e) {
                if (e.target.matches('[data-table-search]')) runSearch(e.target);
            });

            document.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-table-search-clear]');
                if (!btn) return;
                var input = btn.parentElement.querySelector('[data-table-search]');
                input.value = '';
                runSearch(input);
                input.focus();
            });
        })();
    </script>
@endonce

<div {{ $attributes->class('flex flex-wrap items-center justify-between gap-3 pb-2 sm:pb-3') }}>
    {{-- Left: tabs + filters --}}
    <div class="flex flex-wrap items-center gap-2">
        @if (count($tabs))
            <nav class="inline-flex items-center gap-0.5 rounded-lg bg-slate-100 p-1 dark:bg-slate-800" aria-label="Table views">
                @foreach ($tabs as $value => $label)
                    @php $isActive = (string) $activeTab === (string) $value; @endphp
                    <a href="{{ request()->fullUrlWithQuery([$tabParam => $value, $pageParam => null]) }}"
                        @if ($isActive) aria-current="page" @endif
                        class="rounded-md px-4 py-1.5 text-sm transition {{ $isActive ? 'bg-white font-semibold text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white' : 'font-medium text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        @endif

        @if (count($filters))
            <form method="GET" class="flex flex-wrap items-center gap-2">
                @foreach ($keep as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <input type="hidden" name="{{ $tabParam }}" value="{{ $activeTab }}">

                @foreach ($filters as $f)
                    @php
                        $param = "{$key}_{$f['name']}";
                        $current = (string) request($param);
                    @endphp
                    <label class="relative">
                        <span class="sr-only">{{ $f['label'] }}</span>
                        <select name="{{ $param }}" onchange="this.form.submit()"
                            class="{{ $pill }} appearance-none pr-9 {{ $current !== '' ? "{$accentBorder} {$accentText}" : '' }}">
                            <option value="">{{ $f['label'] }}</option>
                            @foreach ($f['options'] as $value => $text)
                                <option value="{{ $value }}" @selected($current === (string) $value)>{{ $text }}</option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2">{!! $chevron !!}</span>
                    </label>
                @endforeach
            </form>
        @endif
    </div>

    {{-- Right: search + export + add --}}
    <div class="flex flex-wrap items-center gap-2">
        @if ($search)
            <div class="relative w-full sm:w-72">
                <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </span>
                <input type="text" data-table-search autocomplete="off" placeholder="{{ $searchPlaceholder }}"
                    aria-label="Search this table"
                    class="h-10 w-full rounded-lg border border-slate-200 bg-white pl-10 pr-10 text-sm text-slate-900 placeholder:text-slate-400 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500">
                <button type="button" data-table-search-clear aria-label="Clear search" style="display: none"
                    class="absolute right-2.5 top-1/2 grid size-6 -translate-y-1/2 place-items-center rounded text-slate-400 hover:text-slate-700 dark:hover:text-slate-200">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
        @endif

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