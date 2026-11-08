{{-- 1: Applicant information (read-only x-form.input fields) --}}
<div data-panel="1" class="space-y-3">
    {{ $head('Applicant information', 'These details come from your verified citizen profile.') }}

    @foreach ($profile as $title => $items)
        <section class="{{ $card }}">
            <h3 class="{{ $h3 }}">{{ $title }}</h3>

            <div class="{{ $grid }}">
                @foreach ($items as $label => $value)
                    <div @class([$span => str_contains(strtolower($label), 'address')])>
                        <x-form.input :label="$label" :name="'profile_' . Str::slug($label, '_')" :value="$value"
                            :use-old="false" light raised readonly />
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    <div class="rounded-md border border-(--blue-200) bg-(--blue-50) px-4 py-3 text-sm text-(--gray-800) dark:border-(--blue-800) dark:bg-[color-mix(in_srgb,var(--blue-900)_40%,transparent)] dark:text-(--gray-200)">
        Need to correct your profile? Update it through your
        <a href="{{ route('citizen.profile') }}" class="font-semibold text-(--blue-700) underline hover:text-(--blue-800) dark:text-(--blue-300) dark:hover:text-(--blue-200)">verified profile page</a>
        before applying.
    </div>
</div>