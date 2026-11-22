{{-- 3: Supporting documents (uses <x-form.upload>, laid out with flex) --}}
<div data-panel="3" class="hidden space-y-3">
    {{ $head('Supporting documents', "Upload clear PDF, JPG, JPEG, or PNG files up to {$maxMb} MB each. All documents below are required.") }}

    <section class="{{ $card }}">
        <div class="flex flex-wrap gap-4">
            @foreach ($service['requirements'] as $key => $label)
                <x-form.upload-docu :id="'req-' . $key" :name="'requirements[' . $key . ']'" :label="$label" :max-mb="$maxMb" required />
            @endforeach
        </div>
    </section>
</div>