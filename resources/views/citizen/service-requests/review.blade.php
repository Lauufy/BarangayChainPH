@php
    // Account/security flags that should not appear in the profile cards
    $hiddenProfileFields = ['two factor enabled', 'google password setup required'];
@endphp

<div class="space-y-3">
    {{ $head('Review and confirmation', 'Check your details and application information before submitting.') }}

    @foreach ($profile as $title => $items)
        @php
            $visibleItems = collect($items)->reject(
                fn ($value, $label) => in_array(strtolower(trim($label)), $hiddenProfileFields, true),
            );
        @endphp

        @continue($visibleItems->isEmpty())

        <section class="{{ $card }}">
            <h3 class="{{ $h3 }}">{{ $title }}</h3>
            <div class="{{ $grid }}">
                @foreach ($visibleItems as $label => $value)
                    <div @class([$span => str_contains(strtolower($label), 'address')])>
                        <x-form.input :label="$label" :name="'review_profile_' . Str::slug($label, '_')" :value="$value" :use-old="false" light raised disabled />
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    <section class="{{ $card }}">
        <h3 class="{{ $h3 }}">{{ $detailsHeading }}</h3>
        <div class="{{ $grid }}">
            @foreach ($fields as $f)
                <div @class([$span => $f['wide']])>
                    <x-form.input :id="'rv-f-' . $f['key']" :name="'review_' . $f['key']" :label="$f['label']"
                        :type="$f['type'] === 'textarea' ? 'textarea' : 'text'" :data-review="$f['key']" :use-old="false" light raised disabled />
                </div>
            @endforeach
        </div>
    </section>

    @if ($service['requirements'])
    <section class="{{ $card }}">
        <h3 class="{{ $h3 }}">Supporting documents</h3>
        <div class="{{ $grid }}">
            @foreach ($service['requirements'] as $key => $label)
                <x-form.input :id="'rv-req-' . $key" :name="'review_req_' . $key" :label="$label" :data-review-file="$key" :use-old="false" light raised disabled />
            @endforeach
        </div>
    </section>

    @endif

    <section class="{{ $card }}">
        <h3 class="{{ $h3 }}">Confirmation</h3>
        <div class="mt-3 text-sm text-(--gray-700) dark:text-(--gray-200)">
            <x-form.checkbox name="agree_truth" required>
                I confirm that the information I provided is accurate and true.
            </x-form.checkbox>
        </div>
        <p class="{{ $muted }} mt-3 text-xs">
            Submission creates an application reference number. After approval, you may be asked to pay the
            barangay fee before you can get your document.
        </p>
    </section>
</div>
