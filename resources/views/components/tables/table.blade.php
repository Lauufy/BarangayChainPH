{{--
    Responsive table: table on sm+, stacked cards below 640px.
    Props: heads, empty, selectable, cards (false = scroll on mobile), message, hint.
    Mobile <td> attributes: data-title | data-side="right|full" | data-label | data-hide-mobile | data-detail
--}}
@props([
    'heads' => [],
    'empty' => false,
    'selectable' => false,
    'cards' => true,
    'message' => 'No records found.',
    'hint' => 'Try changing your search or filters.',
])

@php
    $aligns = ['left' => 'text-left', 'right' => 'text-right', 'center' => 'text-center'];
    $th = ($cards ? '' : 'whitespace-nowrap ') . 'bg-slate-50 px-3 py-2 text-xs font-medium text-slate-500 dark:bg-slate-800/60 dark:text-slate-400 sm:px-4 sm:py-3';
@endphp

@once
    <style>
        .tbl {
            --tbl-card: #fff;
            --tbl-card-active: #f8fafc;
            --tbl-border: #e2e8f0;
            --tbl-border-strong: #cbd5e1;
            --tbl-muted: #94a3b8;
            --tbl-sub: #64748b;
            --tbl-accent: #0f172a;
            --tbl-shadow: rgb(15 23 42 / .08);
            --tbl-check: #7c3aed;
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
            --tbl-check: #a78bfa;
        }

        .tbl input[type=checkbox] { width: 1rem; height: 1rem; border-radius: .25rem; accent-color: var(--tbl-check); cursor: pointer; }

        /* Desktop: soft rounded header bar */
        .tbl thead th { position: sticky; top: 0; z-index: 1; }
        .tbl thead th:first-child { border-radius: .5rem 0 0 .5rem; }
        .tbl thead th:last-child { border-radius: 0 .5rem .5rem 0; }

        .tbl-container { container: table-region / inline-size; min-width: 0; }

        /* Narrow table: wrap text and scale down everything inside (text, avatars, badges, buttons, spacing) */
        @container table-region (min-width: 40rem) and (max-width: 63.99rem) {
            .tbl-cards table { zoom: .9; }
            .tbl-cards th, .tbl-cards td { white-space: normal !important; overflow-wrap: break-word; padding-inline: .625rem !important; }
            .tbl-cards td * { min-width: 0; white-space: normal; }
            .tbl-cards td .truncate { overflow: visible; text-overflow: clip; }
            .tbl-cards td p { overflow-wrap: anywhere; }
            .tbl-cards th, .tbl-cards td :where(a, button) { white-space: nowrap !important; overflow-wrap: normal; }
        }
        @container table-region (min-width: 40rem) and (max-width: 51.99rem) {
            .tbl-cards table { zoom: .8; }
            .tbl-cards th, .tbl-cards td { padding-inline: .375rem !important; }
        }

        /* Mobile: stacked cards */
        @container table-region (max-width: 39.99rem) {
            .tbl-cards { overflow-x: visible; }
            .tbl-cards table { display: block; width: 100%; min-width: 0 !important; font-size: .875rem; }
            .tbl-cards thead { display: none; }
            .tbl-cards tbody { display: flex; flex-direction: column; gap: .75rem; padding: .125rem; }

            .tbl-cards tbody > tr {
                position: relative;
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                grid-auto-flow: row dense;
                gap: .375rem .875rem;
                align-items: center;
                padding: .875rem 1rem;
                overflow: hidden;
                border: 1px solid var(--tbl-border);
                border-radius: .375rem;
                background: var(--tbl-card);
                box-shadow: 0 1px 2px rgb(15 23 42 / .05), 0 1px 3px rgb(15 23 42 / .04);
                transition: box-shadow .15s, transform .15s, background-color .15s;
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
            .tbl-cards tbody > tr > td[data-side="right"] { grid-column: 2; justify-self: end; text-align: right; }
            .tbl-cards tbody > tr > td[data-title] { font-size: 1rem; font-weight: 600; line-height: 1.3; color: var(--tbl-accent); }
            .tbl-cards tbody > tr > td[data-side="full"] {
                grid-column: 1 / -1;
                margin-top: .375rem;
                padding-top: .625rem !important;
                border-top: 1px dashed var(--tbl-border);
                font-size: .75rem;
                color: var(--tbl-sub);
            }
            .tbl-cards tbody > tr > td[data-hide-mobile] { display: none; }
            .tbl-cards tbody > tr > td[data-empty] { text-align: center; }

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

            /* Tap to expand */
            .tbl-cards tbody > tr:has(> [data-detail]) { cursor: pointer; padding-bottom: 1.75rem; -webkit-tap-highlight-color: transparent; }
            .tbl-cards tbody > tr:has(> [data-detail])::after {
                content: "";
                position: absolute;
                left: 50%;
                bottom: .6rem;
                width: .45rem;
                height: .45rem;
                margin-left: -.225rem;
                border: solid var(--tbl-muted);
                border-width: 0 2px 2px 0;
                transform: rotate(45deg);
                transition: transform .2s;
            }
            .tbl-cards tbody > tr > td[data-detail] { display: none; }

            .tbl-cards tbody > tr.is-open { border-color: var(--tbl-border-strong); box-shadow: 0 4px 12px var(--tbl-shadow); }
            .tbl-cards tbody > tr.is-open::after { transform: rotate(-135deg) translate(-2px, -2px); }
            .tbl-cards tbody > tr.is-open > td[data-detail] { display: block; animation: tbl-detail-in .18s ease-out; }

            @keyframes tbl-detail-in {
                from { opacity: 0; transform: translateY(-4px); }
                to { opacity: 1; transform: none; }
            }
        }
    </style>
    <script>
        document.addEventListener('click', function (e) {
            var tr = e.target.closest('.tbl-cards tbody > tr');
            if (!tr || getComputedStyle(tr).display !== 'grid') return;
            if (e.target.closest('a, button, input, select, textarea, label, form, summary, details')) return;
            if (tr.querySelector(':scope > [data-detail]')) tr.classList.toggle('is-open');
        });
    </script>
@endonce

<div class="tbl-container">
<div class="tbl {{ $cards ? 'tbl-cards' : '' }} overflow-x-auto">
    <table class="w-full {{ $cards ? '' : 'min-w-max' }} text-left text-xs sm:text-sm">
        <thead>
            <tr>
                @if ($selectable)
                    <th scope="col" class="{{ $th }} w-10">
                        <input type="checkbox" aria-label="Select all"
                            onclick="this.closest('table').querySelectorAll('tbody input[type=checkbox]').forEach(c => c.checked = this.checked)">
                    </th>
                @endif

                @foreach ($heads as $head)
                    @php
                        $label = is_array($head) ? $head['label'] : $head;
                        $align = is_array($head) ? $head['align'] ?? 'left' : 'left';
                    @endphp
                    <th scope="col" class="{{ $th }} {{ $aligns[$align] ?? $aligns['left'] }}">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>

        <tbody class="divide-y divide-slate-100 text-slate-700 dark:divide-slate-800 dark:text-slate-200 [&>tr]:transition-colors [&>tr:hover]:bg-slate-50/70 dark:[&>tr:hover]:bg-white/5 [&_td]:px-3 [&_td]:py-2 [&_td]:align-middle sm:[&_td]:px-4 sm:[&_td]:py-4">
            @unless ($empty)
                {{ $slot }}
            @endunless
            <tr data-search-empty @if (!$empty) style="display: none" @endif>
                <td colspan="{{ count($heads) + ($selectable ? 1 : 0) }}" data-side="full" data-empty class="py-12! text-center">
                    <span class="mx-auto grid size-12 place-items-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                        <svg class="size-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
                    </span>
                    <p data-empty-message data-default-message="{{ $message }}" class="mt-3 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $message }}</p>
                    <p data-empty-hint data-default-hint="{{ $hint }}" class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ $hint }}</p>
                </td>
            </tr>
        </tbody>
    </table>
</div>
</div>