<div class="space-y-3">
    {{ $head('Supporting documents', "Upload clear PDF, JPG, JPEG, or PNG files up to {$maxMb} MB each.") }}

    <section class="{{ $card }}">
        <div class="flex flex-wrap gap-4">
            @foreach ($service['requirements'] as $key => $label)
                @php
                    $rule = $service['requirement_rules'][$key] ?? 'required';
                    $status = match ($rule) {
                        'nullable' => 'optional',
                        'required_if:application_type,Renewal' => 'required for renewal',
                        default => 'required',
                    };
                @endphp
                <x-form.upload-docu :id="'req-' . $key" :name="'requirements[' . $key . ']'" :label="$label.' ('.$status.')'" :max-mb="$maxMb" :required="$rule === 'required'" :data-required-for-renewal="$rule === 'required_if:application_type,Renewal' ? 'true' : null" />
            @endforeach
        </div>
    </section>
</div>