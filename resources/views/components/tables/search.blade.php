{{--
    Live search for the table rows on screen (no page reload, no database).
    Place it in the same <section> as the table.

    <x-tables.search placeholder="Search by name" />
--}}
@props(['placeholder' => 'Start searching...'])

@once
    <script>
        (function () {
            function runSearch(input) {
                var q = input.value.trim().toLowerCase();
                var scope = input.closest('section') || document;
                var clear = input.parentElement.querySelector('[data-table-search-clear]');
                if (clear) clear.style.display = q ? '' : 'none';

                var real = 0;
                var shown = 0;
                scope.querySelectorAll('.tbl tbody > tr').forEach(function (tr) {
                    if (tr.hasAttribute('data-search-empty')) return;
                    real++;
                    var match = !q || tr.textContent.toLowerCase().indexOf(q) !== -1;
                    tr.style.display = match ? '' : 'none';
                    if (match) shown++;
                });

                var empty = scope.querySelector('.tbl tbody > tr[data-search-empty]');
                if (!empty) return;

                var noMatches = real > 0 && q && shown === 0;
                var message = empty.querySelector('[data-empty-message]');
                var hint = empty.querySelector('[data-empty-hint]');
                if (message) message.textContent = noMatches ? 'No records found.' : message.dataset.defaultMessage;
                if (hint) hint.textContent = noMatches ? 'Try changing your search or filters.' : hint.dataset.defaultHint;
                empty.style.display = real === 0 || noMatches ? '' : 'none';
            }

            document.addEventListener('input', function (e) {
                if (e.target.matches('[data-table-search]')) runSearch(e.target);
            });

            document.addEventListener('click', function (e) {
                var button = e.target.closest('[data-table-search-clear]');
                if (!button) return;
                var input = button.parentElement.querySelector('[data-table-search]');
                input.value = '';
                runSearch(input);
                input.focus();
            });
        })();
    </script>
@endonce

<div {{ $attributes->class('w-full sm:w-72') }}>
    <div class="relative">
        <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        </span>
        <input type="text" data-table-search autocomplete="off" placeholder="{{ $placeholder }}" aria-label="Search this table"
            class="h-10 w-full rounded-lg border border-slate-200 bg-white pl-10 pr-10 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-(--blue-850) dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:ring-(--blue-600)">
        <button type="button" data-table-search-clear aria-label="Clear search" style="display: none"
            class="absolute right-2.5 top-1/2 grid size-6 -translate-y-1/2 place-items-center rounded text-slate-400 hover:text-slate-700 dark:hover:text-slate-200">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
    </div>
</div>