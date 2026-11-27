@php
    $hasMultipleRequirements = count($service['requirements']) > 1;
@endphp

<section class="{{ $card }}">
    <h3 class="{{ $h3 }}">Supporting documents</h3>
    <p class="{{ $sub }} mt-0.5">
        Upload clear PDF, JPG, JPEG, or PNG files up to {{ $maxMb }} MB each.
    </p>

    <div @class([
        'mt-3 grid gap-4',
        'sm:grid-cols-2 lg:grid-cols-3' => $hasMultipleRequirements,
    ])>
        @foreach ($service['requirements'] as $key => $label)
            @php
                $rule = $service['requirement_rules'][$key] ?? 'required';
                $isRenewalOnly = $rule === 'required_if:application_type,Renewal';

                $note = match ($rule) {
                    'nullable' => 'optional',
                    'required_if:application_type,Renewal' => 'required for renewal',
                    default => 'required',
                };
            @endphp

            <x-form.upload-docu
                :id="'req-' . $key"
                :name="'requirements[' . $key . ']'"
                :label="$label . ' (' . $note . ')'"
                :max-mb="$maxMb"
                :required="$rule === 'required'"
                :data-required-for-renewal="$isRenewalOnly ? 'true' : null"
            />
        @endforeach
    </div>
</section>