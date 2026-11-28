@props(['type', 'aria', 'labels', 'values' => [], 'datasets' => null, 'empty' => false,
    'height' => 'h-64', 'unit' => '', 'center' => null, 'stacked' => false, 'uniform' => false])

<div data-chart-frame class="relative w-full min-w-0 {{ $height }}">
    @if ($empty)
        <p class="flex h-full items-center justify-center text-sm">No data in this period.</p>
    @else
        <canvas data-report-chart="{{ $type }}" role="img" aria-label="{{ $aria }}"
            data-labels="{{ json_encode($labels) }}" data-values="{{ json_encode($values) }}"
            data-datasets="{{ json_encode($datasets) }}" data-unit="{{ $unit }}"
            @if ($center) data-center-label="{{ $center }}" @endif
            @if ($stacked) data-stacked="true" @endif
            @if ($uniform) data-uniform="true" @endif></canvas>
    @endif
</div>
