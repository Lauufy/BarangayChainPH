<div class="space-y-4">
    <section class="mb-4 rounded-lg border border-gray-200/80 bg-white p-5 shadow-xs dark:border-gray-800 dark:bg-gray-900/50">
        <h3 class="border-b border-gray-100 pb-2.5 text-base font-bold text-(--blue-900) dark:border-gray-800 dark:text-(--blue-300)">
            Application Details
        </h3>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
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
                        'sm:col-span-2' => ! empty($f['wide']),
                    ])
                >
                    <x-form.input
                        :id="'f-' . $f['key']"
                        :name="$f['key']"
                        :label="$label"
                        :type="$f['type']"
                        :value="$f['value']"
                        :step="$f['step']"
                        :required="$f['required']"
                        :options="$options"
                        :data-other="! empty($f['other']) ? $f['key'] : null"
                        light
                        raised
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
                                maxlength="200"
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