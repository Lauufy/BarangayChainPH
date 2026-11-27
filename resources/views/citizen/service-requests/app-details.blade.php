<div class="space-y-4">
    <section class="mb-4 rounded-lg border border-gray-200/80 bg-white p-5 shadow-xs dark:border-gray-800 dark:bg-gray-900/50">
        <h3 class="border-b border-gray-100 pb-2.5 text-base font-bold text-(--blue-900) dark:border-gray-800 dark:text-(--blue-300)">
            Application Details
        </h3>

        <div class="mt-4 flex flex-wrap gap-4">
            @foreach ($fields as $f)
                @php
                    $label = $f['label'] . ($f['required'] ? ' (required)' : ' (optional)');
                    $options = $f['type'] === 'select'
                        ? array_combine($f['options'], $f['options'])
                        : [];
                @endphp

                <div
                    @if (! empty($f['when']))
                        data-depends-on="{{ $f['when'][0] }}"
                        data-when="{{ $f['when'][1] }}"
                    @endif
                    @class([
                        'min-w-0 grow basis-full',
                        'sm:basis-[calc(50%-0.5rem)]' => empty($f['wide']),
                    ])
                >
                    <x-form.input
                        :id="'f-' . $f['key']"
                        :name="$f['key']"
                        :label="$label"
                        :type="$f['type']"
                        :value="$f['value']"
                        :error-message="match (true) {
                            $f['type'] === 'tel' => 'Enter 10 digits starting with 9, for example 9171234567.',
                            $f['type'] === 'email' => 'Enter a valid email address.',
                            $f['type'] === 'select' => 'Choose an option for '.strtolower($f['label']).'.',
                            $f['key'] === 'resident_since' => 'Enter a four-digit year between 1000 and '.date('Y').'.',
                            $f['type'] === 'number' => 'Enter a nonnegative amount with up to two decimal places.',
                            $f['type'] === 'textarea' => 'Use up to 1,000 characters.',
                            default => 'Enter '.strtolower($f['label']).' using up to 255 characters.',
                        }"
                        :min="$f['type'] === 'number' ? ($f['key'] === 'resident_since' ? 1000 : 0) : null"
                        :max="$f['key'] === 'resident_since' ? date('Y') : null"
                        :step="$f['step']"
                        :prefix="$f['type'] === 'tel' ? '+63' : null"
                        :placeholder="match ($f['type']) { 'tel' => '9171234567', 'select' => 'Select an option', default => ' ' }"
                        :hint="$f['type'] === 'tel' ? '' : null"
                        :maxlength="match ($f['type']) { 'text', 'email' => 255, 'textarea' => 1000, default => null }"
                        :required="$f['required']"
                        :options="$options"
                        :data-other="! empty($f['other']) ? $f['key'] : null"
                        light
                    />

                    @if (! empty($f['other']))
                        <div
                            id="other-{{ $f['key'] }}"
                            @class([
                                'mt-3',
                                'hidden' => ($f['value'] ?? null) !== 'Others',
                            ])
                        >
                            <x-form.input
                                :id="'f-' . $f['key'] . '_other'"
                                :name="$f['key'] . '_other'"
                                label="Please describe (required)"
                                :value="$f['otherValue'] ?? null"
                                error-message="Describe your answer in 3 to 200 characters."
                                minlength="3" maxlength="200"
                                light
                                raised
                            />
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
</div>