@php
    $card = 'rounded-lg border border-gray-200/80 bg-white p-3 shadow-xs sm:p-5 dark:border-gray-800 dark:bg-gray-900/50';
    $h3 = 'border-b border-gray-100 pb-2.5 text-base font-bold text-(--blue-900) dark:border-gray-800 dark:text-(--blue-300)';
    $h4 = 'text-sm font-bold text-(--blue-900) dark:text-(--blue-300)';
    $muted = 'text-(--gray-500) dark:text-(--gray-400)';
    $btnBase = 'inline-flex items-center justify-center rounded-md px-4 py-2.5 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 sm:py-2';

    $fields = collect($fields);
    $requirements = collect($service['requirements'] ?? []);
@endphp

<div class="space-y-4">

    {{-- GATE: shown first when this step opens. Cancel goes back, Accept reveals the review. --}}
    <section id="privacy-gate" class="{{ $card }} hidden">
        <h3 class="text-xl font-bold text-(--blue-900) dark:text-(--blue-300)">Application Privacy Notice</h3>
        <p class="{{ $muted }} mt-1 text-sm">BarangayChain PH &mdash; Barangay San Luis, Antipolo City, Rizal</p>
        <p class="{{ $muted }} mt-1 text-xs">In compliance with Republic Act No. 10173 (Data Privacy Act of 2012)</p>

        <div class="mt-5 space-y-5 text-sm leading-6 text-(--gray-700) dark:text-(--gray-200)">
            <section>
                <h4 class="{{ $h4 }}">1. What we collect</h4>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li><strong>Verified profile details:</strong> your name, address, age, birthdate, and other details from your verified profile.</li>
                    <li><strong>Application information:</strong> the answers you entered for this {{ $service['title'] }}.</li>
                    @if ($requirements->isNotEmpty())
                        <li><strong>Supporting documents:</strong> the files you attached to this application.</li>
                    @endif
                </ul>
            </section>

            <section>
                <h4 class="{{ $h4 }}">2. Why we collect it</h4>
                <p class="mt-1">
                    Your data is used only to process this application: to let barangay personnel review it, to issue
                    your document, and to keep an official record. It is not used for advertising or for any purpose
                    unrelated to barangay services.
                </p>
            </section>

            <section>
                <h4 class="{{ $h4 }}">3. Storage and security</h4>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li>Uploaded files are kept in private storage, not publicly accessible.</li>
                    <li>Only authorized barangay staff can view your application, and only to process it.</li>
                    <li>Your files and personal details are <strong>never placed on the blockchain</strong>. It only holds document hashes and approval timestamps for issued documents.</li>
                    <li>We use encrypted connections (HTTPS), role-based access, and audit logs.</li>
                </ul>
            </section>

            <section>
                <h4 class="{{ $h4 }}">4. Sharing and retention</h4>
                <p class="mt-1">
                    We do not sell or trade your data. It is shared only with authorized Barangay San Luis personnel,
                    or when required by law, a court order, or a lawful request from a government agency. Records are
                    kept only as long as needed and according to government records retention rules, then securely
                    deleted or anonymized.
                </p>
            </section>

            <section>
                <h4 class="{{ $h4 }}">5. Your rights</h4>
                <p class="mt-1">
                    Under the Data Privacy Act you may be informed about, access, correct, object to, or request
                    erasure of your data, and file a complaint with the National Privacy Commission. To use these
                    rights, contact the Data Protection Officer at the Office of the Punong Barangay / Barangay
                    Secretary, Barangay San Luis Hall, Antipolo City, Rizal. See our full
                    <a href="{{ route('privacy') }}" target="_blank" rel="noopener"
                        class="text-(--blue-700) underline dark:text-(--blue-300)">Privacy Policy</a>.
                </p>
            </section>
        </div>

        <div class="mt-6 border-t border-(--gray-200) pt-4 dark:border-(--gray-700)">
            <p class="{{ $muted }} text-xs">
                <strong>Consent notice:</strong> By accepting, you confirm that you have read this notice and consent
                to the processing of your personal data as described above.
            </p>
            <p class="mt-3 text-sm font-medium text-(--gray-800) dark:text-(--gray-100)">
                Do you agree to the use of your personal data for this application?
            </p>
            <div class="mt-3 flex items-center gap-2">
                <button type="button" id="privacy-cancel"
                    class="{{ $btnBase }} flex-1 border border-(--gray-300) bg-(--white) text-(--gray-700) hover:bg-(--gray-50) focus-visible:ring-(--blue-300) sm:flex-none dark:border-(--gray-600) dark:bg-(--gray-800) dark:text-(--gray-200) dark:hover:bg-(--gray-700)">Cancel</button>
                <button type="button" id="privacy-accept"
                    class="{{ $btnBase }} flex-2 bg-(--blue-600) text-(--white) hover:bg-(--blue-700) focus-visible:ring-(--blue-600) sm:flex-none">I accept</button>
            </div>
        </div>
    </section>

    {{-- REVIEW: shown only after the privacy notice is accepted --}}
    <div id="review-content" class="hidden space-y-4">
        <div>
            <h2 class="text-lg font-bold text-(--blue-900) dark:text-(--blue-300)">
                Review and confirmation
            </h2>
            <p class="mt-0.5 text-sm text-(--gray-600) dark:text-(--gray-300)">
                Check your details and application information before submitting.
            </p>
        </div>

        {{-- Verified profile --}}
        @include('citizen.service-requests.app-info', ['prefix' => 'review_profile_'])

        {{-- Application details (filled in by the page script from data-review) --}}
        <section class="{{ $card }}">
            <h3 class="{{ $h3 }}">
                {{ $slug === 'business-clearance' ? 'Applicant information' : 'Application details' }}
            </h3>

            <div class="mt-4 flex flex-wrap gap-4">
                @foreach ($fields as $f)
                    <div @class([
                        'min-w-0 grow basis-full',
                        'sm:basis-[calc(50%-0.5rem)]' => empty($f['wide']),
                    ])>
                        <x-form.input
                            :id="'rv-f-' . $f['key']"
                            :name="'review_' . $f['key']"
                            :label="$f['label']"
                            :type="$f['type'] === 'textarea' ? 'textarea' : 'text'"
                            :data-review="$f['key']"
                            :use-old="false"
                            light
                            raised
                            disabled
                        />
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Uploaded files (filled in by the page script from data-review-file) --}}
        @if ($requirements->isNotEmpty())
            <section class="{{ $card }}">
                <h3 class="{{ $h3 }}">Supporting documents</h3>

                <div @class(['mt-4 grid gap-4', 'sm:grid-cols-2' => $requirements->count() > 1])>
                    @foreach ($requirements as $key => $label)
                        <x-form.input
                            :id="'rv-req-' . $key"
                            :name="'review_req_' . $key"
                            :label="$label"
                            :data-review-file="$key"
                            :use-old="false"
                            light
                            raised
                            disabled
                        />
                    @endforeach
                </div>
            </section>
        @endif

       <section class="{{ $card }}">
            <h3 class="{{ $h3 }}">Confirmation notice</h3>
            <p class="text-(--gray-700) dark:text-(--gray-200) mt-2 text-sm leading-6">
                By submitting your permit application, you confirm that you have read and agree to our
                <a href="{{ route('terms') }}" target="_blank" rel="noopener"
                    class="text-(--blue-700) dark:text-(--blue-300) hover:text-(--blue-900) dark:hover:text-(--blue-100) font-semibold underline transition">Terms
                    of Service</a>
                and
                <a href="{{ route('privacy') }}" target="_blank" rel="noopener"
                    class="text-(--blue-700) dark:text-(--blue-300) hover:text-(--blue-900) dark:hover:text-(--blue-100) font-semibold underline transition">Privacy
                    Policy</a>,
                and you consent to the processing of your application data and supporting documents as described in them.
            </p>

            <div class="text-(--gray-700) dark:text-(--gray-200) mt-3 space-y-3 text-sm">
                <x-form.checkbox name="agree_truth" required class="aria-invalid:border-(--red-600)"
                    data-error-message="Confirm that the application details and supporting documents are accurate and valid.">
                    I confirm that all information and supporting documents submitted for this permit request are true, accurate, and valid.
                </x-form.checkbox>
                <p id="agree_truth-error" data-error-for="agree_truth" class="text-(--red-600) text-sm empty:hidden"></p>

                {{-- Set to 1 when the user presses "I accept" on the privacy notice. Validate with the "accepted" rule on the server. --}}
                <input type="hidden" name="agree_privacy" id="agree_privacy" value="">
            </div>
        </section>
    </div>
</div>