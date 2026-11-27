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
    $th =
        ($cards ? '' : 'whitespace-nowrap ') .
        'bg-slate-50 px-3 py-2 text-xs font-medium uppercase tracking-wide text-slate-500 dark:bg-slate-800/60 dark:text-slate-400 sm:px-4 sm:py-3';
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

        .dark .tbl,
        [data-theme="dark"] .tbl {
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

        .tbl input[type=checkbox] {
            width: 1rem;
            height: 1rem;
            border-radius: .25rem;
            accent-color: var(--tbl-check);
            cursor: pointer;
        }

        /* Desktop: soft rounded header bar */
        .tbl thead th {
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .tbl thead th:first-child {
            border-radius: .5rem 0 0 .5rem;
        }

        .tbl thead th:last-child {
            border-radius: 0 .5rem .5rem 0;
        }

        .tbl-container {
            container: table-region / inline-size;
            min-width: 0;
        }

        /* Narrow table: wrap text and scale down everything inside (text, avatars, badges, buttons, spacing) */
        @container table-region (min-width: 40rem) and (max-width: 63.99rem) {
            .tbl-cards table {
                zoom: .9;
            }

            .tbl-cards th,
            .tbl-cards td {
                white-space: normal !important;
                overflow-wrap: break-word;
                padding-inline: .625rem !important;
            }

            .tbl-cards td * {
                min-width: 0;
                white-space: normal;
            }

            .tbl-cards td .truncate {
                overflow: visible;
                text-overflow: clip;
            }

            .tbl-cards td p {
                overflow-wrap: anywhere;
            }

            .tbl-cards th,
            .tbl-cards td :where(a, button) {
                white-space: nowrap !important;
                overflow-wrap: normal;
            }
        }

        @container table-region (min-width: 40rem) and (max-width: 51.99rem) {
            .tbl-cards table {
                zoom: .8;
            }

            .tbl-cards th,
            .tbl-cards td {
                padding-inline: .375rem !important;
            }
        }

    </style>
@endonce

<div class="tbl-container">
    <div class="tbl {{ $cards ? 'tbl-cards' : '' }} overflow-x-auto">
        <table class="{{ $cards ? '' : 'min-w-max' }} w-full text-left text-xs sm:text-sm">
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
                        <th scope="col" class="{{ $th }} {{ $aligns[$align] ?? $aligns['left'] }}">
                            {{ $label }}</th>
                    @endforeach
                </tr>
            </thead>

            <tbody
                class="divide-y divide-slate-100 text-slate-700 dark:divide-slate-800 dark:text-slate-200 [&>tr:hover]:bg-slate-50/70 dark:[&>tr:hover]:bg-white/5 [&>tr]:transition-colors [&_td]:px-3 [&_td]:py-2 [&_td]:align-middle sm:[&_td]:px-4 sm:[&_td]:py-4">
                @unless ($empty)
                    {{ $slot }}
                @endunless
                <tr data-search-empty @if (!$empty) style="display: none" @endif>
                    <td colspan="{{ count($heads) + ($selectable ? 1 : 0) }}" data-side="full" data-empty
                        class="py-12! text-center">
                        <span
                            class="mx-auto grid size-12 place-items-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                            <svg class="size-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                aria-hidden="true">
                                <polyline points="22 12 16 12 14 15 10 15 8 12 2 12" />
                                <path
                                    d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z" />
                            </svg>
                        </span>
                        <p data-empty-message data-default-message="{{ $message }}"
                            class="mt-3 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $message }}
                        </p>
                        <p data-empty-hint data-default-hint="{{ $hint }}"
                            class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ $hint }}</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    @if ($cards)
        <x-tables.record-dialog />
    @endif
</div>
