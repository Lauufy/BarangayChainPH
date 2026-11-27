@php
    $hiddenFields = [
        'privacy policy version',
        'consent ip',
        'two factor enabled',
        'google password setup required',
    ];

    // Display order. Labels not listed here go to the end.
    $order = ['name', 'civil status', 'address', 'age', 'nationality', 'birthdate', 'birthplace'];
@endphp

<div class="space-y-4">
    @foreach ($sections as $title => $items)
        @php
            $visibleItems = collect($items)
                ->reject(fn ($value, $label) => in_array(strtolower(trim($label)), $hiddenFields, true))
                ->sortBy(function ($value, $label) use ($order) {
                    $i = array_search(strtolower(trim($label)), $order, true);
                    return $i === false ? 99 : $i;
                });
            $hasMultipleItems = $visibleItems->count() > 1;
        @endphp

        @if ($visibleItems->isNotEmpty())
            <section class="rounded-lg border border-gray-200/80 bg-white p-5 shadow-xs dark:border-gray-800 dark:bg-gray-900/50">
                <h3 class="border-b border-gray-100 pb-2.5 text-base font-bold text-(--blue-900) dark:border-gray-800 dark:text-(--blue-300)">
                    Personal Information
                </h3>

                <div @class([
                    'mt-4 grid gap-4',
                    'sm:grid-cols-2' => $hasMultipleItems,
                ])>
                    @foreach ($visibleItems as $label => $value)
                        <div @class([
                            'sm:col-span-2' => $hasMultipleItems && str_contains(strtolower($label), 'address'),
                        ])>
                            <x-form.input
                                :label="$label"
                                :name="'profile_' . Str::slug($label, '_')"
                                :value="$value"
                                :use-old="false"
                                light
                                raised
                                readonly
                            />
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach
</div>