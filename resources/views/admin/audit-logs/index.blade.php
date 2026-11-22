@extends('layouts.admin')

@section('title', 'Audit Logs')

@section('content')
    @php
        $hasFilters = filled($userFilter) || filled($actionFilter) || filled($dateFilter);
        $activeCount = collect([$userFilter, $actionFilter, $dateFilter])->filter(fn ($v) => filled($v))->count();
        $field =
            'block h-9 w-full rounded-sm border border-slate-300 bg-white px-2.5 text-sm text-slate-900 placeholder:text-slate-400 transition focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/30 dark:[color-scheme:dark]';
        $label = 'mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300';
    @endphp

    <div class="space-y-4" data-table-key="audit-logs">
        <x-admin.page-header label="Security & Accountability" title="Audit Logs"
            text="Review changes made to applications and issued documents." />

        <x-tables.card title="System Activity History"
            description="Changes to service requests and issued documents." :paginator="$logs" :searchable="false"
            item-label="audit records" :per-page-options="[10, 15, 20, 25, 50]">
            <x-slot:toolbar>
                <div class="flex items-center justify-end gap-2">
                    {{-- Quick search (filters the rows on this page as you type). --}}
                    <div class="relative">
                        <label class="sr-only" for="audit-search">Search audit logs</label>
                        <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.3-4.3" />
                        </svg>
                        <input id="audit-search" type="search" data-audit-search placeholder="Search user, action, or record"
                            class="h-9 w-48 rounded-sm border border-slate-300 bg-white pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 transition focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20 sm:w-64 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/30">
                    </div>

                    {{-- Filters menu --}}
                    <details id="audit-filters" class="relative">
                        <summary
                            class="inline-flex h-9 cursor-pointer list-none items-center gap-2 rounded-sm border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 transition marker:content-none hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-white/5 [&::-webkit-details-marker]:hidden">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3Z" />
                            </svg>
                            Filters
                            @if ($activeCount)
                                <span
                                    class="grid size-5 place-items-center rounded-full bg-blue-600 text-[11px] font-semibold text-white">
                                    {{ $activeCount }}
                                </span>
                            @endif
                        </summary>

                        <form method="GET" action="{{ route('admin.audit-logs.index') }}"
                            class="absolute right-0 z-30 mt-2 w-72 space-y-3 rounded-md border border-slate-200 bg-white p-4 text-left shadow-lg dark:border-slate-700 dark:bg-slate-900">
                            <input type="hidden" name="per_page" value="{{ request('per_page') }}">

                            <div>
                                <label class="{{ $label }}" for="f-user">User ID</label>
                                <input id="f-user" name="user_id" type="number" min="1"
                                    value="{{ $userFilter }}" placeholder="e.g. 12" class="{{ $field }}">
                            </div>

                            <div>
                                <label class="{{ $label }}" for="f-action">Action key</label>
                                <input id="f-action" name="action" type="text" value="{{ $actionFilter }}"
                                    placeholder="e.g. service_request.approved" class="{{ $field }}">
                            </div>

                            <div>
                                <label class="{{ $label }}" for="f-date">Date</label>
                                <input id="f-date" name="date" type="date" value="{{ $dateFilter }}"
                                    class="{{ $field }}">
                            </div>

                            <div class="flex items-center justify-end gap-2 pt-1">
                                @if ($hasFilters)
                                    <a href="{{ route('admin.audit-logs.index', request()->only('per_page')) }}"
                                        class="inline-flex h-9 items-center rounded-sm border border-slate-300 px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-white/5">
                                        Clear
                                    </a>
                                @endif
                                <button type="submit"
                                    class="inline-flex h-9 items-center rounded-sm bg-blue-600 px-4 text-sm font-semibold text-white transition hover:bg-blue-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                    Apply
                                </button>
                            </div>
                        </form>
                    </details>
                </div>
            </x-slot:toolbar>

            <x-tables.table :heads="['User', 'Action', 'Record', 'Changes', 'Date & Time']" :empty="$isEmpty"
                message="No audit records match these filters."
                hint="Try removing a filter or changing the date.">
                @foreach ($logs as $log)
                    @php
                        $verb = Str::lower(Str::afterLast($log['action'], '.'));
                        $variant = match (true) {
                            Str::contains($verb, ['create', 'issue', 'approve', 'verif', 'add']) => 'success',
                            Str::contains($verb, ['delete', 'reject', 'revoke', 'fail', 'remove']) => 'danger',
                            Str::contains($verb, ['update', 'edit', 'change']) => 'info',
                            Str::contains($verb, ['pending', 'cancel']) => 'warning',
                            default => 'neutral',
                        };
                        $changes = trim((string) $log['changes']);
                    @endphp

                    <tr>
                        <td data-title>
                            <x-tables.user-cell :name="$log['user']" />
                        </td>

                        <td data-label="Action">
                            <x-tables.badge :variant="$variant">
                                {{ Str::headline($verb ?: $log['action']) }}
                            </x-tables.badge>
                        </td>

                        <td data-label="Record" class="font-medium text-slate-900 dark:text-slate-100">
                            {{ $log['record'] }}
                        </td>

                        <td data-side="full" class="whitespace-nowrap">
                            @if ($changes === '')
                                <span class="text-slate-400 dark:text-slate-500">—</span>
                            @else
                                <button type="button" data-audit-open
                                    data-user="{{ $log['user'] }}"
                                    data-action="{{ $log['action'] }}"
                                    data-record="{{ $log['record'] }}"
                                    data-date="{{ $log['date'] }}"
                                    data-changes="{{ $changes }}"
                                    class="cursor-pointer text-sm font-medium text-blue-600 underline-offset-2 transition hover:text-blue-700 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 dark:text-blue-400 dark:hover:text-blue-300">
                                    View changes
                                </button>
                            @endif
                        </td>

                        <td data-side="right" class="whitespace-nowrap text-slate-500 dark:text-slate-400">
                            {{ $log['date'] }}
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        </x-tables.card>
    </div>

    {{-- Changes modal (filled by the script below). --}}
    <x-admin.modal id="audit-dialog" title="Change details" size="max-w-lg">
        <div class="space-y-5 px-6 py-5">
            <dl class="grid grid-cols-[4.5rem_minmax(0,1fr)] gap-x-3 gap-y-2 text-sm">
                <dt class="text-slate-500 dark:text-(--gray-300)">User</dt>
                <dd id="audit-user" class="wrap-break-word font-medium"></dd>
                <dt class="text-slate-500 dark:text-(--gray-300)">Action</dt>
                <dd id="audit-action" class="wrap-break-word font-mono text-xs"></dd>
                <dt class="text-slate-500 dark:text-(--gray-300)">Record</dt>
                <dd id="audit-record" class="wrap-break-word font-medium"></dd>
                <dt class="text-slate-500 dark:text-(--gray-300)">Date</dt>
                <dd id="audit-date" class="wrap-break-word"></dd>
            </dl>

            <div>
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-(--blue-700) dark:text-(--celadon-500)">Changes</p>
                <div id="audit-changes" class="divide-y divide-slate-100 rounded-sm border border-slate-200 dark:divide-white/10 dark:border-white/10"></div>
            </div>
        </div>

        <x-slot:footer>
            <div class="flex justify-end">
                <button type="button" data-modal-close
                    class="inline-flex h-9 items-center rounded-sm border border-slate-300 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-(--blue-500) dark:border-white/20 dark:text-(--gray-200) dark:hover:bg-white/10 dark:focus-visible:ring-(--celadon-500)">
                    Close
                </button>
            </div>
        </x-slot:footer>
    </x-admin.modal>

    <script>
        (function () {
            // The page content may be re-rendered and this script re-run; only bind once.
            if (window.__auditLogsBound) return;
            window.__auditLogsBound = true;

            // Uses textContent, so values are never parsed as HTML.
            function el(tag, cls, text) {
                var node = document.createElement(tag);
                if (cls) node.className = cls;
                if (text != null) node.textContent = text;
                return node;
            }

            var ARROW =
                '<svg class="size-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';

            // Turn "Field: a -> b; Field2: c -> d" into rows.
            function parseChanges(text) {
                return String(text || '')
                    .split(';')
                    .map(function (s) { return s.trim(); })
                    .filter(Boolean)
                    .map(function (part) {
                        var i = part.indexOf(':');
                        if (i === -1) return { raw: part };

                        var field = part.slice(0, i).trim();
                        var rest = part.slice(i + 1).trim();
                        var sides = rest.split('->');

                        if (sides.length === 2) {
                            return { field: field, from: sides[0].trim(), to: sides[1].trim() };
                        }
                        return { field: field, raw: rest };
                    });
            }

            function isEmptyValue(v) {
                return v === '' || /^(empty|null|none|n\/a|-)$/i.test(v);
            }

            function valueChip(value, kind) {
                if (isEmptyValue(value)) {
                    return el('span', 'rounded-sm bg-slate-100 px-2 py-1 text-sm italic text-slate-400 dark:bg-white/5 dark:text-(--gray-400)', 'Empty');
                }
                var cls = kind === 'from'
                    ? 'wrap-break-word rounded-sm bg-slate-100 px-2 py-1 text-sm text-slate-500 line-through decoration-slate-400 dark:bg-white/5 dark:text-(--gray-300)'
                    : 'wrap-break-word rounded-sm bg-blue-600/10 px-2 py-1 text-sm font-medium text-blue-700 dark:bg-(--celadon-500)/10 dark:text-(--celadon-500)';
                return el('span', cls, value);
            }

            function renderChanges(container, text) {
                container.textContent = '';
                var rows = parseChanges(text);

                if (!rows.length) {
                    container.appendChild(el('p', 'px-3 py-3 text-sm text-slate-400 dark:text-(--gray-400)', 'No details recorded.'));
                    return;
                }

                rows.forEach(function (row) {
                    var item = el('div', 'px-3 py-2.5');

                    if (row.field) {
                        item.appendChild(el('p', 'mb-1.5 text-xs font-semibold text-slate-600 dark:text-(--celadon-500)', row.field));
                    }

                    if (row.from !== undefined) {
                        var line = el('div', 'flex flex-wrap items-center gap-2');
                        line.appendChild(valueChip(row.from, 'from'));
                        var arrow = el('span', 'inline-flex');
                        arrow.innerHTML = ARROW;
                        line.appendChild(arrow);
                        line.appendChild(valueChip(row.to, 'to'));
                        item.appendChild(line);
                    } else {
                        item.appendChild(el('p', 'wrap-break-word text-sm text-slate-700 dark:text-(--gray-200)', row.raw));
                    }

                    container.appendChild(item);
                });
            }

           function openDialog(d) {
    var modal = document.getElementById('audit-dialog');

    // Move it to <body> so a page-content swap can't remove it.
    if (modal.parentElement !== document.body) document.body.appendChild(modal);

    // Remove stale copies that a swap re-renders inside the page content.
    document.querySelectorAll('#audit-dialog').forEach(function (m) {
        if (m !== modal) m.remove();
    });

    document.getElementById('audit-user').textContent = d.user || '—';
    document.getElementById('audit-action').textContent = d.action || '—';
    document.getElementById('audit-record').textContent = d.record || '—';
    document.getElementById('audit-date').textContent = d.date || '—';
    renderChanges(document.getElementById('audit-changes'), d.changes);

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
            // Quick search on the rows of this page.
            document.addEventListener('input', function (event) {
                if (!event.target.matches('[data-audit-search]')) return;

                var query = event.target.value.trim().toLowerCase();
                document.querySelectorAll('.tbl tbody tr').forEach(function (row) {
                    row.hidden = query !== '' && !row.textContent.toLowerCase().includes(query);
                });
            });

            document.addEventListener('click', function (e) {
                // Close the Filters menu when clicking anywhere outside it.
                var filters = document.getElementById('audit-filters');
                if (filters && filters.open && !filters.contains(e.target)) {
                    filters.open = false;
                }

                var opener = e.target.closest('[data-audit-open]');
                if (opener) openDialog(opener.dataset);
            });

            // Escape closes the Filters menu (the modal component handles its own Escape).
            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape') return;
                var filters = document.getElementById('audit-filters');
                if (filters && filters.open) filters.open = false;
            });
        })();
    </script>
@endsection