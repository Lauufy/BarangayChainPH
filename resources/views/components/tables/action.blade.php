{{--
    Use `icon-only` for each action in a row that has multiple actions.
    Link:      <x-tables.action label="Review" icon="eye" :href="route('x.show', $x)" />
    Icon link: <x-tables.action label="View" icon="eye" :href="route('x.show', $x)" icon-only />
    Form:      <x-tables.action label="Delete" icon="trash" variant="danger" :action="route('x.destroy', $x)" method="DELETE" confirm="Delete this record?" icon-only />
    Livewire:  <x-tables.action label="Delete" icon="trash" variant="danger" wire:click="delete({{ $x->id }})" wire:confirm="Delete this record?" />
    Modal/JS:  <x-tables.action label="Edit" icon="pencil" onclick="openEditModal({{ $x->id }})" />

    icons: eye, pencil, trash, shield, power      variants: default, success, danger
--}}
@props([
    'label',
    'icon',
    'href' => null,
    'action' => null,
    'method' => 'POST',
    'variant' => 'default',
    'confirm' => null,
    'iconOnly' => false,
])

@php
    $inner =
        [
            'eye' =>
                '<path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>',
            'pencil' =>
                '<path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.375 2.625a1 1 0 0 1 3 3l-9.013 9.014a2 2 0 0 1-.853.505l-2.873.84a.5.5 0 0 1-.62-.62l.84-2.873a2 2 0 0 1 .506-.852z"/>',
            'trash' =>
                '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/>',
            'shield' =>
                '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
            'power' => '<path d="M12 2v10"/><path d="M18.4 6.6a9 9 0 1 1-12.77.04"/>',
        ][$icon] ?? '';

    $svg =
        '<svg class="size-4.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' .
        $inner .
        '</svg>';

    $color =
        [
            'default' => 'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-[#17325c]',
            'success' => 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100',
            'danger' => 'bg-rose-50 text-rose-600 hover:bg-rose-100',
        ][$variant] ?? '';

    $btn =
        ($iconOnly
            ? 'grid size-8 place-items-center rounded-md text-slate-500'
            : 'inline-flex items-center justify-center rounded-md px-3 py-1.5 text-sm font-medium') .
        ' transition focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 ' .
        $color;
@endphp

<div class="group relative inline-flex">
    @if ($action)
        <form action="{{ $action }}" method="POST"
            @if ($confirm) onsubmit="return confirm('{{ e($confirm) }}')" @endif>
            @csrf
            @if (strtoupper($method) !== 'POST')
                @method($method)
            @endif
            <button type="submit" aria-label="{{ $label }}"
                {{ $attributes->class($btn) }}>
                @if ($iconOnly)
                    {!! $svg !!}
                @else
                    {{ $label }}
                @endif
            </button>
        </form>
    @elseif ($href)
        <a href="{{ $href }}" aria-label="{{ $label }}"
            {{ $attributes->class($btn) }}>
            @if ($iconOnly)
                {!! $svg !!}
            @else
                {{ $label }}
            @endif
        </a>
    @else
        <button type="button" aria-label="{{ $label }}"
            {{ $attributes->class($btn) }}>
            @if ($iconOnly)
                {!! $svg !!}
            @else
                {{ $label }}
            @endif
        </button>
    @endif

    @if ($iconOnly)
        <span role="tooltip"
            class="pointer-events-none absolute -top-8 left-1/2 z-10 -translate-x-1/2 whitespace-nowrap rounded bg-slate-900 px-2 py-1 text-xs font-medium text-white opacity-0 transition group-focus-within:opacity-100 group-hover:opacity-100">{{ $label }}</span>
    @endif
</div>