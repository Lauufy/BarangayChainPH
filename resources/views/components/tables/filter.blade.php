{{--
    Reusable dropdown filter. Picking an option reloads the page with ?param=value.

    <x-tables.filter param="users_status" title="Account status" all-label="All statuses"
        :options="$statuses" :selected="$status" :reset="['users_page']" />
--}}
@props([
    'param',
    'options' => [],
    'selected' => null,
    'label' => 'Filter',
    'title' => null,
    'allLabel' => 'All',
    'reset' => [],
])

@php
    $selected = (string) $selected;
    $choices = ['' => $allLabel] + $options;
    $resetQuery = array_fill_keys(array_merge(['page', 'search'], $reset), null);
@endphp

<details {{ $attributes->class('relative') }}>
    <summary class="flex h-10 cursor-pointer list-none items-center gap-2 rounded-lg border border-(--gray-200) px-3 text-sm font-medium text-(--blue-800) hover:bg-(--gray-50) dark:border-(--gray-700) dark:text-(--blue-100) dark:hover:bg-(--blue-800) [&::-webkit-details-marker]:hidden">
        <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M3 5h18M6 12h12m-9 7h6" /></svg>
        {{ $selected !== '' ? ($options[$selected] ?? $label) : $label }}
    </summary>
    <div class="absolute right-0 z-20 mt-1 w-44 rounded-xl border border-(--gray-200) bg-(--white) p-1.5 shadow-md dark:border-(--gray-700) dark:bg-(--blue-900)">
        @if ($title)
            <p class="px-2 py-1.5 text-xs font-semibold uppercase text-(--gray-500) dark:text-(--gray-400)">{{ $title }}</p>
        @endif

        @foreach ($choices as $value => $text)
            @php $isActive = $selected === (string) $value; @endphp
            <a href="{{ request()->fullUrlWithQuery(array_merge($resetQuery, [$param => $value === '' ? null : $value])) }}"
                @if ($isActive) aria-current="true" @endif
                class="flex items-center justify-between rounded-md px-2 py-2 text-sm text-(--gray-800) hover:bg-(--gray-50) dark:text-(--gray-100) dark:hover:bg-(--blue-800)">
                {{ $text }}
                @if ($isActive)
                    <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6" /></svg>
                @endif
            </a>
        @endforeach
    </div>
</details>