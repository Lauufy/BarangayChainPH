{{-- STEP 4: Full privacy notice first (accept / cancel), then review and consent --}}
<div data-panel="4" class="hidden space-y-3">

    {{-- GATE: shown first when step 4 opens. Cancel goes back to step 3, Accept reveals the review. --}}
    <section id="privacy-gate" class="{{ $card }} hidden">
        <h3 class="text-(--blue-900) dark:text-(--blue-300) text-xl font-bold">Identity Verification Privacy Notice</h3>
        <p class="{{ $muted }} mt-1 text-sm">BarangayChain PH &mdash; Barangay San Luis, Antipolo City, Rizal</p>
        <p class="{{ $muted }} mt-1 text-xs">In Compliance with Republic Act No. 10173 (Data Privacy Act of 2012)
        </p>

        <div class="text-(--gray-700) dark:text-(--gray-200) mt-5 space-y-5 text-sm leading-6">
            <section>
                <h4 class="{{ $h3 }}">1. Why This Notice Exists</h4>
                <p class="mt-1">
                    Before you can use barangay services on <strong>BarangayChain PH</strong>, you must verify your
                    identity. This notice explains what personal data we collect during identity verification, why we
                    collect it, and how we protect it. It supplements our general <a href="{{ route('privacy') }}"
                        target="_blank" rel="noopener"
                        class="text-(--blue-700) dark:text-(--blue-300) underline">Privacy Policy</a>.
                </p>
            </section>

            <section>
                <h4 class="{{ $h3 }}">2. Data We Collect for Identity Verification</h4>
                <p class="mt-1">In line with the principle of proportionality, we collect only what is needed to
                    confirm that you are a real person and the owner of the ID you submit:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li><strong>Personal Details:</strong> Your name, date of birth, address, and other details you
                        enter in the verification form.</li>
                    <li><strong>Government-Issued ID:</strong> A photo or scan of your valid ID, and the ID number
                        written on it.</li>
                    <li><strong>Selfie Photo:</strong> A photo of your face, taken with your device camera or uploaded,
                        used to compare you with your ID.</li>
                    <li><strong>Verification Records:</strong> Your submission dates, review status (pending, verified,
                        or rejected), rejection reasons, and the staff member who reviewed it.</li>
                </ul>
                <p class="mt-2">
                    Your ID photo and selfie are <strong>sensitive personal information</strong> and are handled with
                    extra care.
                </p>
            </section>

            <section>
                <h4 class="{{ $h3 }}">3. Purpose of Processing</h4>
                <p class="mt-1">Your identity data is used only for the following purposes:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li>Confirming that you are a real person and the rightful owner of the submitted ID;</li>
                    <li>Allowing authorized barangay personnel to approve or reject your verification;</li>
                    <li>Preventing fake accounts, impersonation, and duplicate use of the same ID; and</li>
                    <li>Unlocking access to barangay services such as clearances, permits, and certificates.</li>
                </ul>
                <p class="mt-2">We will not use your ID or selfie for advertising or for any purpose unrelated to
                    barangay services.</p>
            </section>

            <section>
                <h4 class="{{ $h3 }}">4. How We Handle Your ID Number</h4>
                <p class="mt-1">
                    To detect duplicate use of the same ID, your ID number is converted into a <strong>one-way
                        hash</strong>. The system compares hashes only, so it can tell that an ID was already used
                    without keeping a readable copy of the number for that check.
                </p>
            </section>

            <section>
                <h4 class="{{ $h3 }}">5. Storage, Access, and Security</h4>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li>ID images and selfies are kept in <strong>private storage</strong> and are not publicly
                        accessible.</li>
                    <li>Only authorized barangay administrators and staff with the proper permission can view your
                        verification files, and only to review your submission.</li>
                    <li>Your files are <strong>never placed on the blockchain</strong>. The blockchain only holds
                        document hashes and approval timestamps for issued permits.</li>
                    <li>We use encrypted connections (HTTPS), role-based access control, and audit logs to protect your
                        data.</li>
                </ul>
                <p
                    class="{{ $ink }} border-(--amber-500) mt-2 rounded-md border bg-[color-mix(in_srgb,var(--amber-500)_15%,transparent)] p-3">
                    <strong>Security Reminder:</strong> No online system is completely risk-free. Upload your ID only on
                    a trusted device and network, never share your password or OTP, and make sure you are on the
                    official BarangayChain PH website.
                </p>
            </section>

            <section>
                <h4 class="{{ $h3 }}">6. Sharing of Your Data</h4>
                <p class="mt-1">
                    We do not sell or trade your identity data. It is shared only with authorized Barangay San Luis
                    personnel performing official duties, or when disclosure is required by law, a court order, or a
                    lawful request from a government agency.
                </p>
            </section>

            <section>
                <h4 class="{{ $h3 }}">7. Retention and Disposal</h4>
                <p class="mt-1">
                    Your ID photo, selfie, and verification records are kept only as long as needed for the purposes
                    above and in accordance with the records retention schedules of the <em>National Archives of the
                        Philippines (R.A. 9470)</em> and the Commission on Audit (COA). After that period, they are
                    securely deleted or anonymized.
                </p>
            </section>

            <section>
                <h4 class="{{ $h3 }}">8. Your Rights as a Data Subject</h4>
                <p class="mt-1">Under the Data Privacy Act of 2012, you have the right to:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li><strong>Be informed</strong> about how your identity data is used;</li>
                    <li><strong>Access</strong> the identity information we hold about you;</li>
                    <li><strong>Correct</strong> wrong or outdated details;</li>
                    <li><strong>Object, or request blocking or erasure</strong>, subject to legal record-keeping duties;
                        and</li>
                    <li><strong>File a complaint</strong> with the National Privacy Commission (NPC).</li>
                </ul>
                <p class="mt-2">
                    If you withdraw your consent or ask for your identity data to be deleted, you may lose access to
                    services that require a verified identity.
                </p>
            </section>

            <section>
                <h4 class="{{ $h3 }}">9. Data Protection Officer (DPO)</h4>
                <p class="mt-1">To use your rights or ask a question about this notice, contact our Data Protection
                    Officer:</p>
                <div class="{{ $line }} bg-(--gray-50) dark:bg-(--gray-900) mt-2 rounded-md border p-4">
                    <p><strong>Data Protection Officer &mdash; BarangayChain PH</strong></p>
                    <p>Office of the Punong Barangay / Barangay Secretary</p>
                    <p>Barangay San Luis Hall, Antipolo City, Rizal, Philippines</p>
                </div>
            </section>
        </div>

        <div class="{{ $line }} mt-6 border-t pt-4">
            <p class="{{ $muted }} text-xs">
                <strong>Consent Notice:</strong> By accepting, you confirm that you have read this notice and consent to
                the processing of your personal data as described above.
            </p>
            <p class="{{ $ink }} mt-3 text-sm font-medium">
                Do you agree to the use of your personal data for identity verification?
            </p>
            <div class="mt-3 flex items-center gap-2">
                <button type="button" id="privacy-cancel" class="{{ $btnGhost }}">Cancel</button>
                <button type="button" id="privacy-accept" class="{{ $btnBlue }} flex-2 sm:flex-none">I
                    accept</button>
            </div>
        </div>
    </section>

    {{-- REVIEW: shown only after the privacy notice is accepted --}}
    <div id="review-content" class="hidden space-y-3">
        <section class="{{ $card }}">
            <h3 class="{{ $h3 }}">Your details</h3>
            <dl class="mt-3 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="{{ $muted }}">Full name</dt>
                    <dd class="{{ $ink }} mt-1 font-medium">{{ auth()->user()->name }}</dd>
                </div>
                <div>
                    <dt class="{{ $muted }}">Email</dt>
                    <dd class="{{ $ink }} mt-1 font-medium">
                        {{ auth()->user()->email ?? ($formDetails['email'] ?? '—') }}</dd>
                </div>
                <div>
                    <dt class="{{ $muted }}">Phone</dt>
                    <dd class="{{ $ink }} mt-1 font-medium">{{ $formDetails['phone'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="{{ $muted }}">Date of birth</dt>
                    <dd class="{{ $ink }} mt-1 font-medium">
                        {{ filled($formDetails['date_of_birth'] ?? null) ? \Illuminate\Support\Carbon::parse($formDetails['date_of_birth'])->format('M j, Y') : '—' }}
                    </dd>
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
                        {{ collect([$formDetails['house_number'] ?? null, $formDetails['barangay'] ?? null, $formDetails['city_municipality'] ?? null, $formDetails['province'] ?? null, $formDetails['zip_code'] ?? null])->filter()->implode(', ') ?:'—' }}
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
                        <span id="review-id-front-empty" class="{{ $muted }} px-2 text-center text-xs"></span>
                    </div>
                    <figcaption class="{{ $muted }} mt-2 text-xs">Front of ID</figcaption>
                </figure>
                <figure>
                    <div
                        class="bg-(--gray-50) ring-(--gray-200) dark:bg-(--gray-900) dark:ring-(--gray-700) flex h-32 items-center justify-center overflow-hidden rounded-md ring-1">
                        <img id="review-id-back" alt="Back of your ID" class="hidden h-full w-full object-contain">
                        <span id="review-id-back-empty" class="{{ $muted }} px-2 text-center text-xs"></span>
                    </div>
                    <figcaption class="{{ $muted }} mt-2 text-xs">Back of ID</figcaption>
                </figure>
                <figure>
                    <div
                        class="bg-(--gray-50) ring-(--gray-200) dark:bg-(--gray-900) dark:ring-(--gray-700) flex h-32 items-center justify-center overflow-hidden rounded-md ring-1">
                        <img id="review-selfie" alt="Your selfie" class="hidden h-full w-full object-contain">
                    </div>
                    <figcaption class="{{ $muted }} mt-2 text-xs">Face photo</figcaption>
                </figure>
            </div>
        </section>

        <section class="{{ $card }}">
            <h3 class="{{ $h3 }}">Confirmation notice</h3>
            <p class="text-(--gray-700) dark:text-(--gray-200) mt-2 text-sm leading-6">
                By submitting your identity verification, you confirm that you have read and agree to our
                <a href="{{ route('terms') }}" target="_blank" rel="noopener"
                    class="text-(--blue-700) dark:text-(--blue-300) hover:text-(--blue-900) dark:hover:text-(--blue-100) font-semibold underline transition">Terms
                    of Service</a>
                and
                <a href="{{ route('privacy') }}" target="_blank" rel="noopener"
                    class="text-(--blue-700) dark:text-(--blue-300) hover:text-(--blue-900) dark:hover:text-(--blue-100) font-semibold underline transition">Privacy
                    Policy</a>,
                and you consent to the processing of your personal data as described in them.
            </p>

            <div class="text-(--gray-700) dark:text-(--gray-200) mt-3 space-y-3 text-sm">
                <x-form.checkbox name="agree_truth" required
                    data-error-message="Confirm that the details and photos belong to you.">
                    I confirm that the information, ID, and photo I submitted are true and belong to me.
                </x-form.checkbox>
                <p id="agree_truth-error" class="text-(--red-600) text-sm empty:hidden"></p>

                {{-- Set to 1 when the user presses "I accept" on the privacy notice. Validate with the "accepted" rule on the server. --}}
                <input type="hidden" name="agree_privacy" id="agree_privacy" value="">
            </div>
        </section>
    </div>
</div>
