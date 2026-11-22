{{-- 2: Application details --}}
<div data-panel="2" class="hidden space-y-3">
    {{ $head('Application details', 'Provide details for the ' . strtolower($service['title']) . ' request.') }}

    <section class="{{ $card }}">
        <div class="{{ $grid }} mt-0">
            @foreach ($fields as $f)
                <div @class([$span => $f['wide']])>
                    <x-form.input :id="'f-' . $f['key']" :name="$f['key']" :label="$f['label'] . ($f['required'] ? '' : ' (optional)')"
                        :type="$f['type']" :value="$f['value']" :required="$f['required']"
                        :options="$f['type'] === 'select' ? array_combine($f['options'], $f['options']) : []"
                        :data-other="$f['other'] ? $f['key'] : null" light raised />

                    @if ($f['other'])
                        <div id="other-{{ $f['key'] }}" @class(['mt-3', 'hidden' => $f['value'] !== 'Others'])>
                            <x-form.input :id="'f-' . $f['key'] . '_other'" :name="$f['key'] . '_other'" label="Please describe"
                                :value="$f['otherValue']" maxlength="200" light raised />
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
</div>