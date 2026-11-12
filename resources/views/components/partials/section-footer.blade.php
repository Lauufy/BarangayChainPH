@props([
    'title' => 'Need help?',
    'description' => 'Reach the barangay office if you have questions about your requests or documents.',
    'contact' => true,
    'hours' => 'Monday to Friday, 8:00 AM to 5:00 PM',
])

@php
    $ring = 'focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-(--blue-700) dark:focus-visible:outline-(--blue-300)';
    $line = 'border-(--gray-200) dark:border-(--gray-700)';
    $heading = 'border-l-2 border-(--green-600) pl-3 text-sm font-bold text-(--gray-900) dark:text-(--gray-50)';
    $body = 'text-(--gray-600) dark:text-(--gray-400)';
    $link = 'rounded-sm font-semibold text-(--gray-900) transition-colors hover:text-(--blue-700) dark:text-(--gray-50) dark:hover:text-(--blue-300) ' . $ring;
    $term = 'text-xs text-(--gray-500) dark:text-(--gray-400)';
@endphp

<footer {{ $attributes->class([$line, 'mt-8 border-t pt-6']) }}>
    <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">

        <div class="max-w-md">
            <h3 class="{{ $heading }}">{{ $title }}</h3>
            <p class="{{ $body }} mt-2 pl-3.5 text-sm leading-6">{{ $description }}</p>
            @if ($hours)
                <p class="{{ $term }} mt-1 pl-3.5">{{ $hours }}</p>
            @endif
        </div>

        <div class="flex flex-col gap-3 text-sm sm:flex-row sm:flex-wrap sm:items-center sm:gap-x-6 md:justify-end">
            @if ($contact)
                <div>
                    <p class="{{ $term }}">Phone</p>
                    <a href="tel:+639233232322" class="{{ $link }}">+63 923 323 2322</a>
                </div>
                <div>
                    <p class="{{ $term }}">Email</p>
                    <a href="mailto:barangaychain@gmail.com" class="{{ $link }} break-all">barangaychain@gmail.com</a>
                </div>
            @endif

            {{ $slot }}
        </div>
    </div>
</footer>