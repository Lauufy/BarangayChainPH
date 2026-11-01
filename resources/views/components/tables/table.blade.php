{{--
    <x-tables.table :heads="['Name', 'Status', ['label' => 'Actions', 'align' => 'center']]" :empty="$items->isEmpty()">
        <tr>
            <td>plain td: padding and alignment are applied automatically</td>
            <td class="text-center">...</td>
        </tr>
    </x-tables.table>

    Add `selectable` for a checkbox column (add your own <td><input type="checkbox"></td> per row).
--}}
@props([
    'heads' => [],
    'empty' => false,
    'selectable' => false,
    'message' => 'No records found.',
    'hint' => 'Try changing your search or filters.',
])

@once
    <style>
        .tbl input[type=checkbox] { width: 1rem; height: 1rem; border-radius: .25rem; accent-color: #059669; cursor: pointer; }
    </style>
@endonce

<div class="tbl overflow-x-auto">
    <table class="w-full min-w-max text-left text-xs sm:text-sm">
        <thead class="border-b border-slate-200 bg-slate-50">
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
                    <th scope="col" class="whitespace-nowrap px-3 py-2 text-xs font-semibold text-slate-600 sm:px-4 sm:py-3 {{ ['left' => 'text-left', 'right' => 'text-right', 'center' => 'text-center'][$align] }}">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>

        <tbody class="divide-y divide-slate-100 text-slate-700 [&>tr]:transition-colors [&>tr:hover]:bg-slate-50 [&_td]:px-3 [&_td]:py-2 [&_td]:align-middle sm:[&_td]:px-4 sm:[&_td]:py-3">
            @if ($empty)
                <tr>
                    <td colspan="{{ count($heads) + ($selectable ? 1 : 0) }}" class="py-12! text-center">
                        <span class="mx-auto grid size-10 place-items-center rounded-full bg-slate-100 text-slate-400">
                            <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
                        </span>
                        <p class="mt-3 font-medium text-slate-900">{{ $message }}</p>
                        <p class="mt-0.5 text-sm text-slate-500">{{ $hint }}</p>
                    </td>
                </tr>
            @else
                {{ $slot }}
            @endif
        </tbody>
    </table>
</div>