{{-- STEP 4: Review and consent --}}
<div data-panel="4" class="hidden space-y-3">
    <div>
        <h2 class="{{ $h2 }}">Review and submit</h2>
        <p class="{{ $sub }}">Check that everything is correct, then accept the notice below.</p>
    </div>

    <section class="{{ $card }}">
        <h3 class="{{ $h3 }}">Your details</h3>
        <dl class="mt-3 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <dt class="{{ $muted }}">Full name</dt>
                <dd class="{{ $ink }} mt-1 font-medium">{{ auth()->user()->name }}</dd>
            </div>
            <div>
                <dt class="{{ $muted }}">Email</dt>
                <dd class="{{ $ink }} mt-1 font-medium">{{ $formDetails['email'] ?? '—' }}</dd>
            </div>
            <div>
                <dt class="{{ $muted }}">Phone</dt>
                <dd class="{{ $ink }} mt-1 font-medium">{{ $formDetails['phone'] ?? '—' }}</dd>
            </div>
            <div>
                <dt class="{{ $muted }}">Date of birth</dt>
                <dd class="{{ $ink }} mt-1 font-medium">
                    {{ filled($formDetails['date_of_birth'] ?? null) ? \Illuminate\Support\Carbon::parse($formDetails['date_of_birth'])->format('M j, Y') : '—' }}</dd>
            </div>
            <div>
                <dt class="{{ $muted }}">Gender</dt>
                <dd class="{{ $ink }} mt-1 font-medium">{{ $formDetails['gender'] ?? '—' }}</dd>
            </div>
            <div>
                <dt class="{{ $muted }}">Civil status</dt>
                <dd class="{{ $ink }} mt-1 font-medium">{{ $formDetails['civil_status'] ?? '—' }}</dd>
            </div>
            <div>
                <dt class="{{ $muted }}">Nationality</dt>
                <dd class="{{ $ink }} mt-1 font-medium">{{ $formDetails['nationality'] ?? '—' }}</dd>
            </div>
            <div>
                <dt class="{{ $muted }}">Place of birth</dt>
                <dd class="{{ $ink }} mt-1 font-medium">{{ $formDetails['place_of_birth'] ?? '—' }}</dd>
            </div>
            <div>
                <dt class="{{ $muted }}">Religion</dt>
                <dd class="{{ $ink }} mt-1 font-medium">{{ $formDetails['religion'] ?? '—' }}</dd>
            </div>
            <div>
                <dt class="{{ $muted }}">Occupation</dt>
                <dd class="{{ $ink }} mt-1 font-medium">{{ $formDetails['occupation'] ?? '—' }}</dd>
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
                <dt class="{{ $muted }}">Address</dt>
                <dd class="{{ $ink }} mt-1 font-medium">
                    {{ collect([$formDetails['house_number'] ?? null, $formDetails['barangay'] ?? null, $formDetails['city_municipality'] ?? null, $formDetails['province'] ?? null, $formDetails['zip_code'] ?? null])->filter()->implode(', ') ?: '—' }}
                </dd>
            </div>
            <div>
                <dt class="{{ $muted }}">ID type</dt>
                <dd id="review-type" class="{{ $ink }} mt-1 font-medium"></dd>
            </div>
        </dl>
    </section>

    <section class="{{ $card }}">
        <h3 class="{{ $h3 }}">Photos</h3>
        <div class="mt-3 grid grid-cols-2 gap-4 sm:grid-cols-3">
            <figure>
                <div
                    class="bg-(--gray-50) ring-(--gray-200) dark:bg-(--gray-900) dark:ring-(--gray-700) flex h-32 items-center justify-center overflow-hidden rounded-md ring-1">
                    <img id="review-id-front" alt="Front of your ID" class="hidden h-full w-full object-contain">
                    <span id="review-id-front-empty"
                        class="{{ $muted }} px-2 text-center text-xs"></span>
                </div>
                <figcaption class="{{ $muted }} mt-2 text-xs">Front of ID</figcaption>
            </figure>
            <figure>
                <div
                    class="bg-(--gray-50) ring-(--gray-200) dark:bg-(--gray-900) dark:ring-(--gray-700) flex h-32 items-center justify-center overflow-hidden rounded-md ring-1">
                    <img id="review-id-back" alt="Back of your ID" class="hidden h-full w-full object-contain">
                    <span id="review-id-back-empty"
                        class="{{ $muted }} px-2 text-center text-xs"></span>
                </div>
                <figcaption class="{{ $muted }} mt-2 text-xs">Back of ID</figcaption>
            </figure>
            <figure>
                <div
                    class="bg-(--gray-50) ring-(--gray-200) dark:bg-(--gray-900) dark:ring-(--gray-700) flex h-32 items-center justify-center overflow-hidden rounded-md ring-1">
                    <img id="review-selfie" alt="Your selfie"
                        class="hidden h-full w-full object-contain">
                </div>
                <figcaption class="{{ $muted }} mt-2 text-xs">Selfie with ID</figcaption>
            </figure>
        </div>
    </section>

    {{-- Change this text to match your barangay's official privacy notice --}}
    <section class="{{ $card }}">
        <h3 class="{{ $h3 }}">Data privacy notice</h3>
        <p class="text-(--gray-600) dark:text-(--gray-300) mt-2 text-sm leading-6">
            We collect your personal details, ID, and selfie only to verify your identity for
            barangay online services.
            They are stored privately and can only be viewed by authorized barangay staff.
            They are not shared with other citizens or made public.
            Only a coded proof of the decision is kept in the system ledger. Your personal data is
            never put on it.
            We handle your data in line with the Data Privacy Act of 2012 (Republic Act 10173).
        </p>
    </section>

    <section class="{{ $card }}">
        <h3 class="{{ $h3 }}">Confirmation</h3>
        <div class="text-(--gray-700) dark:text-(--gray-200) mt-3 space-y-3 text-sm">
            <x-form.checkbox name="agree_truth" required data-error-message="Confirm that the details and photos belong to you.">
                I confirm that the information, ID, and photo I submitted are true and belong to me.
            </x-form.checkbox>
            <p id="agree_truth-error" class="text-sm text-(--red-600) empty:hidden"></p>
            <x-form.checkbox name="agree_privacy" required data-error-message="Read the privacy notice and confirm your agreement.">
                I have read the data privacy notice and I agree to the use of my personal data for
                identity verification.
            </x-form.checkbox>
            <p id="agree_privacy-error" class="text-sm text-(--red-600) empty:hidden"></p>
        </div>
    </section>
</div>
