@php
    $roles = [
        'all' => ['All Accounts', 'total', 'M3 7l9-5 9 5-9 5-9-5Zm0 5 9 5 9-5M3 17l9 5 9-5'],
        'citizens' => ['Citizens', 'citizen', 'M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0ZM4 21v-2a5 5 0 0 1 5-5h6a5 5 0 0 1 5 5v2'],
        'employees' => ['Employees', 'staff', 'M3 7h18v14H3zM8 7V4h8v3M8 8v12m8-12v12'],
        'administrators' => ['Administrators', 'admin', 'M12 3 4 6v6c0 4.5 3.2 8 8 9 4.8-1 8-4.5 8-9V6l-8-3Zm-3 9 2 2 4-4'],
    ];
@endphp

<x-tables.toolbar key="users" :search="true" search-placeholder="Search by name, email, or phone">
    <x-slot:navigation>
        <nav aria-label="Table views" class="flex flex-wrap items-center gap-1">
            @foreach ($roles as $key => [$label, $count, $icon])
                <a href="{{ route('admin.users.index', array_merge($directoryQuery, ['users_tab' => $key])) }}"
                    @if ($view === $key) aria-current="page" @endif
                    class="inline-flex min-h-9 items-center gap-2 rounded-lg px-2.5 text-sm font-medium text-(--gray-600) hover:bg-(--gray-50) dark:text-(--gray-300) dark:hover:bg-(--blue-800)">
                    <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}" /></svg>
                    {{ $label }}
                    <span class="rounded-md bg-(--gray-100) px-1.5 py-0.5 text-xs tabular-nums text-(--blue-800) dark:bg-(--blue-800) dark:text-(--blue-100)">{{ $accountCounts[$count] }}</span>
                </a>
            @endforeach
        </nav>
    </x-slot:navigation>
    <x-slot:searchActions>
        <details class="relative">
            <summary class="flex h-10 cursor-pointer list-none items-center gap-2 rounded-lg border border-(--gray-200) px-3 text-sm font-medium text-(--blue-800) hover:bg-(--gray-50) dark:border-(--gray-700) dark:text-(--blue-100) dark:hover:bg-(--blue-800) [&::-webkit-details-marker]:hidden">
                <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M3 5h18M6 12h12m-9 7h6" /></svg>
                {{ $status ? $statuses[$status] : 'Filter' }}
            </summary>
            <div class="absolute right-0 z-20 mt-1 w-44 rounded-xl border border-(--gray-200) bg-(--white) p-1.5 shadow-md dark:border-(--gray-700) dark:bg-(--blue-900)">
                <p class="px-2 py-1.5 text-xs font-semibold uppercase text-(--gray-500) dark:text-(--gray-400)">Account status</p>
                @foreach (['' => 'All statuses'] + $statuses as $key => $label)
                    <a href="{{ route('admin.users.index', array_merge($directoryQuery, ['users_status' => $key ?: null])) }}"
                        @if (($status ?? '') === $key) aria-current="true" @endif
                        class="flex items-center justify-between rounded-md px-2 py-2 text-sm text-(--gray-800) hover:bg-(--gray-50) dark:text-(--gray-100) dark:hover:bg-(--blue-800)">
                        {{ $label }}
                        @if (($status ?? '') === $key)
                            <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6" /></svg>
                        @endif
                    </a>
                @endforeach
            </div>
        </details>
    </x-slot:searchActions>
</x-tables.toolbar>
