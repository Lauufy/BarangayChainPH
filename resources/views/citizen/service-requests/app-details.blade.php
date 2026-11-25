<div class="space-y-3">
    {{ $head($detailsHeading, 'Provide details for the ' . strtolower($service['title']) . ' request.') }}

    <section class="{{ $card }}">
        <div class="{{ $grid }} mt-0">
            @foreach ($fields as $f)
                <div @if ($f['when']) data-depends-on="{{ $f['when'][0] }}" data-when="{{ $f['when'][1] }}" @endif @class([$span => $f['wide']])>
                    <x-form.input :id="'f-' . $f['key']" :name="$f['key']" :label="$f['label'] . ($f['required'] ? ' (required)' : ' (optional)')"
                        :type="$f['type']" :value="$f['value']" :step="$f['step']" :required="$f['required']"
                        :options="$f['type'] === 'select' ? array_combine($f['options'], $f['options']) : []"
                        :data-other="$f['other'] ? $f['key'] : null" light raised />

                    @if ($f['other'])
                        <div id="other-{{ $f['key'] }}" @class(['mt-3', 'hidden' => $f['value'] !== 'Others'])>
                            <x-form.input :id="'f-' . $f['key'] . '_other'" :name="$f['key'] . '_other'" label="Please describe (required)"
                                :value="$f['otherValue']" maxlength="200" light raised />
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
</div>