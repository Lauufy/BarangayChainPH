<section class="rounded-lg border border-gray-200/80 bg-white p-5 shadow-xs dark:border-gray-800 dark:bg-gray-900/50">
    <h3 class="border-b border-gray-100 pb-2.5 text-base font-bold text-(--blue-900) dark:border-gray-800 dark:text-(--blue-300)">
        Personal Information
    </h3>

    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        @foreach ($personalInfo as $label => $value)
            <div @class(['sm:col-span-2' => $label === 'Address'])>
                <x-form.input
                    :label="$label"
                    :name="$prefix . Str::slug($label, '_')"
                    :type="match ($label) { 'Email' => 'email', 'Phone' => 'tel', default => 'text' }"
                    :value="$value"
                    :use-old="false"
                    light
                    disabled
                />
            </div>
        @endforeach
    </div>
</section>
