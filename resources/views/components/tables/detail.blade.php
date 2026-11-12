{{--
    A table cell that is a normal column on desktop, and on mobile stays hidden
    inside the card until the card is tapped (works with <x-tables.table>).

    Value cell:
    <x-tables.detail label="Units">3 units · lecture</x-tables.detail>
    <x-tables.detail label="Term" side="right">AY 2026-2027</x-tables.detail>

    Actions cell (buttons row at the bottom of the expanded card):
    <x-tables.detail actions>
        <x-tables.action label="View" icon="eye" :href="route('courses.show', $course)" />
        <x-tables.action label="Edit" icon="pencil" :href="route('courses.edit', $course)" />
        <x-tables.action label="Delete" icon="trash" variant="danger" :action="route('courses.destroy', $course)" method="DELETE" confirm="Delete this course?" />
    </x-tables.detail>

    Props:
      label     caption shown above the value on mobile (not shown on desktop)
      side      left | right | full   (mobile column; default left)
      actions   shortcut: full-width footer with buttons laid out in a row
      always    keep visible on mobile (no tap needed)
--}}
@props([
    'label' => null,
    'side' => 'left',
    'actions' => false,
    'always' => false,
])

@php
    $side = $actions ? 'full' : $side;
@endphp

<td @unless ($always) data-detail @endunless
    @if ($label) data-label="{{ $label }}" @endif
    @if ($side !== 'left') data-side="{{ $side }}" @endif
    {{ $attributes }}>
    @if ($actions)
        <div class="flex flex-wrap items-center justify-center gap-2">
            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endif
</td>