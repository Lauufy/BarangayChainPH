{{--
    Responsive table: polished table on sm+ screens, stacked cards below 640px.

    <x-tables.table :heads="['Course', 'Grade', 'Status']" :empty="$items->isEmpty()">
        <tr>
            <td>plain td: padding and alignment are applied automatically</td>
            <td class="text-center">...</td>
        </tr>
    </x-tables.table>

    Props:
      selectable      checkbox column (add your own <td><input type="checkbox"></td> per row)
      :cards="false"  keep the horizontally-scrolling table on mobile too

    Mobile card attributes on each <td> (optional, ignored on desktop):
      (none)               left column, stacked in source order
      data-title           left column, larger and bold (the card's headline)
      data-side="right"    right column, right-aligned (grade, badges, term)
      data-side="full"     full-width footer row (e.g. action buttons)
      data-label="Units"   small caption above the value
      data-hide-mobile     hide this cell on mobile
      data-detail          collapsed on mobile: hidden until the card is tapped.
                           Cards that have at least one data-detail cell become
                           tappable and show a chevron. Tap again to collapse.
                           Interactive elements (links, buttons, forms) inside the
                           card do NOT toggle it.

    Example (compact card; tap to reveal details + buttons):
    <tr>
        (summary cells: always visible on mobile)
        <td><span class="font-semibold text-blue-600">OLENG01</span></td>
        <td data-side="right" class="text-xl font-bold text-slate-900 dark:text-slate-100">2.50</td>
        <td data-title>Purposive Communication</td>
        <td data-side="right"><x-tables.badge variant="warning">Ongoing</x-tables.badge></td>

        (detail cells: revealed on tap)
        <td data-detail data-label="Units">3 units · lecture</td>
        <td data-detail data-side="right" data-label="Period"><x-tables.badge variant="info">Midterm</x-tables.badge></td>
        <td data-detail data-label="Prerequisites">None</td>
        <td data-detail data-side="right" data-label="Term">AY 2026-2027 • LFSU111A002</td>
        <td data-detail data-side="full">
            <div class="flex gap-2">
                <x-tables.action label="View" icon="eye" :href="route('x.show', $x)" />
                <x-tables.action label="Edit" icon="pencil" :href="route('x.edit', $x)" />
            </div>
        </td>
    </tr>
--}}
@props([
    'heads' => [],
    'empty' => false,
    'selectable' => false,
    'cards' => true,
    'message' => 'No records found.',
    'hint' => 'Try changing your search or filters.',
])

@once
    <style>
        /* ---------- Theme tokens (light default, dark under .dark / [data-theme="dark"]) ---------- */
        .tbl {
            --tbl-card: #ffffff;
            --tbl-card-active: #f8fafc;
            --tbl-border: #e2e8f0;
            --tbl-border-strong: #cbd5e1;
            --tbl-muted: #94a3b8;
            --tbl-sub: #64748b;
            --tbl-accent: #0f172a;
            --tbl-shadow: rgb(15 23 42 / .08);
        }
        .dark .tbl, [data-theme="dark"] .tbl {
            --tbl-card: #0f172a;
            --tbl-card-active: #1e293b;
            --tbl-border: #334155;
            --tbl-border-strong: #475569;
            --tbl-muted: #64748b;
            --tbl-sub: #94a3b8;
            --tbl-accent: #f1f5f9;
            --tbl-shadow: rgb(0 0 0 / .45);
        }

        .tbl input[type=checkbox] { width: 1rem; height: 1rem; border-radius: .25rem; accent-color: #059669; cursor: pointer; }
        .dark .tbl input[type=checkbox], [data-theme="dark"] .tbl input[type=checkbox] { accent-color: #34d399; }

        /* ---------- Desktop polish ---------- */
        .tbl thead th { position: sticky; top: 0; z-index: 1; letter-spacing: .04em; }
        .tbl tbody > tr:last-child > td { border-bottom: 0; }

        /* ---------- Mobile cards ---------- */
        @media (max-width: 639.98px) {
            .tbl-cards { overflow-x: visible; }
            .tbl-cards table { display: block; width: 100%; min-width: 0 !important; font-size: .875rem; }
            .tbl-cards thead { display: none; }

            .tbl-cards tbody {
                display: flex;
                flex-direction: column;
                gap: .75rem;
                padding: .125rem;
                border: 0;
            }

            .tbl-cards tbody > tr {
                position: relative;
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                grid-auto-flow: row dense;
                column-gap: .875rem;
                row-gap: .375rem;
                align-items: center;
                padding: .875rem 1rem;
                overflow: hidden;
                border: 1px solid var(--tbl-border);
                border-radius: .375rem;
                background: var(--tbl-card);
                box-shadow: 0 1px 2px rgb(15 23 42 / .05), 0 1px 3px rgb(15 23 42 / .04);
                transition: box-shadow .15s ease, transform .15s ease, background-color .15s ease;
            }
            .tbl-cards tbody > tr:active { background: var(--tbl-card-active); transform: scale(.995); }

            .tbl-cards tbody > tr > td {
                display: block;
                grid-column: 1;
                min-width: 0;
                padding: 0 !important;
                text-align: left;
                line-height: 1.35;
                overflow-wrap: anywhere;
            }
            .tbl-cards tbody > tr > td[data-side="right"] {
                grid-column: 2;
                justify-self: end;
                text-align: right;
            }
            .tbl-cards tbody > tr > td[data-title] {
                font-size: 1rem;
                font-weight: 600;
                line-height: 1.3;
                color: var(--tbl-accent);
            }
            .tbl-cards tbody > tr > td[data-side="full"] {
                grid-column: 1 / -1;
                margin-top: .375rem;
                padding-top: .625rem !important;
                border-top: 1px dashed var(--tbl-border);
                font-size: .75rem;
                color: var(--tbl-sub);
            }
            .tbl-cards tbody > tr > td[data-hide-mobile] { display: none; }

            .tbl-cards tbody > tr > td[data-label]::before {
                content: attr(data-label);
                display: block;
                margin-bottom: .125rem;
                font-size: .625rem;
                font-weight: 600;
                letter-spacing: .06em;
                text-transform: uppercase;
                color: var(--tbl-muted);
            }

            /* ---- tap to expand ---- */
            .tbl-cards tbody > tr:has(> [data-detail]) {
                cursor: pointer;
                padding-bottom: 1.75rem;
                -webkit-tap-highlight-color: transparent;
            }
            /* chevron */
            .tbl-cards tbody > tr:has(> [data-detail])::after {
                content: "";
                position: absolute;
                left: 50%;
                bottom: .6rem;
                width: .45rem;
                height: .45rem;
                margin-left: -.225rem;
                border-right: 2px solid var(--tbl-muted);
                border-bottom: 2px solid var(--tbl-muted);
                transform: rotate(45deg);
                transition: transform .2s ease;
            }
            .tbl-cards tbody > tr.is-open::after { transform: rotate(-135deg) translate(-2px, -2px); }

            .tbl-cards tbody > tr > td[data-detail] { display: none; }
            .tbl-cards tbody > tr.is-open > td[data-detail] {
                display: block;
                animation: tbl-detail-in .18s ease-out;
            }
            .tbl-cards tbody > tr.is-open { border-color: var(--tbl-border-strong); box-shadow: 0 4px 12px var(--tbl-shadow); }
            /* keep the detail block visually separate from the summary */
            .tbl-cards tbody > tr.is-open > td[data-detail]:not([data-side]) { grid-column: 1; }

            @keyframes tbl-detail-in {
                from { opacity: 0; transform: translateY(-4px); }
                to   { opacity: 1; transform: none; }
            }

            /* badges stay compact and readable inside cards */
            .tbl-cards tbody > tr > td[data-side="right"] > span { justify-content: center; }

            /* empty state card */
            .tbl-cards tbody > tr > td[data-empty] { text-align: center; }
        }
    </style>
    <script>
        document.addEventListener('click', function (e) {
            var tr = e.target.closest('.tbl-cards tbody > tr');
            if (!tr || !window.matchMedia('(max-width: 639.98px)').matches) return;
            if (e.target.closest('a, button, input, select, textarea, label, form, summary, details')) return;
            if (!tr.querySelector(':scope > [data-detail]')) return;
            tr.classList.toggle('is-open');
        });
    </script>
@endonce

<div class="tbl {{ $cards ? 'tbl-cards' : '' }} overflow-x-auto">
    <table class="w-full min-w-max text-left text-xs sm:text-sm">
        <thead class="border-b border-slate-200 dark:border-slate-700 bg-slate-50/90 dark:bg-slate-800/80 backdrop-blur">
            <tr>
                @if ($selectable)
                    <th scope="col" class="w-10 px-3 py-2 sm:px-4 sm:py-3">
                        <input type="checkbox" aria-label="Select all"
                            onclick="this.closest('table').querySelectorAll('tbody input[type=checkbox]').forEach(c => c.checked = this.checked)">
                    </th>
                @endif
                @foreach ($heads as $head)
                    @php
                        $label = is_array($head) ? $head['label'] : $head;
                        $align = is_array($head) ? ($head['align'] ?? 'left') : 'left';
                    @endphp
                    <th scope="col" class="whitespace-nowrap px-3 py-2 text-[11px] font-semibold uppercase text-slate-500 dark:text-slate-400 sm:px-4 sm:py-3 {{ ['left' => 'text-left', 'right' => 'text-right', 'center' => 'text-center'][$align] }}">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>

        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-200 [&>tr]:transition-colors [&>tr:hover]:bg-emerald-50/40 dark:[&>tr:hover]:bg-emerald-400/5 [&_td]:px-3 [&_td]:py-2 [&_td]:align-middle sm:[&_td]:px-4 sm:[&_td]:py-3.5">
            @if ($empty)
                <tr>
                    <td colspan="{{ count($heads) + ($selectable ? 1 : 0) }}" data-side="full" data-empty class="py-12! text-center">
                        <span class="mx-auto grid size-12 place-items-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500">
                            <svg class="size-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
                        </span>
                        <p class="mt-3 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $message }}</p>
                        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ $hint }}</p>
                    </td>
                </tr>
            @else
                {{ $slot }}
            @endif
        </tbody>
    </table>
</div>