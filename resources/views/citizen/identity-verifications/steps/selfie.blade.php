{{-- STEP 3: Selfie (upload or camera with face check) --}}
<div data-panel="3" class="{{ $step === 3 ? '' : 'hidden' }} space-y-3">
    <div>
        <h2 class="{{ $h2 }}">Take a selfie with your ID</h2>
        <p class="{{ $sub }}">Hold your ID next to your face. Use the camera or upload a photo.
        </p>
    </div>

    <div id="selfie-server-errors" role="alert"
        class="border-(--red-600) bg-(--white) text-(--red-600) dark:bg-(--gray-800) hidden rounded-md border p-3 text-sm"></div>

    <section class="{{ $card }}">
        <ul
            class="bg-(--gray-50) text-(--gray-700) dark:bg-(--gray-900) dark:text-(--gray-200) space-y-1.5 rounded-lg p-4 text-sm">
            <li>✓ Your face and your ID are both clearly visible.</li>
            <li>✓ The lighting is good. No hat or sunglasses.</li>
            <li>✓ The ID text is sharp and not covered.</li>
        </ul>

        <input type="hidden" name="selfie_source" id="selfie-source" value="{{ $draft['selfie_source'] ?? 'upload' }}">

        <div role="tablist" aria-label="Selfie method"
            class="bg-(--gray-100) dark:bg-(--gray-900) mt-4 inline-flex rounded-lg p-1 text-sm font-semibold">
            <button type="button" role="tab" id="tab-upload" aria-selected="true"
                class="bg-(--white) text-(--gray-900) dark:bg-(--gray-700) dark:text-(--gray-50) rounded-md px-4 py-2 shadow-sm transition">Upload
                photo</button>
            <button type="button" role="tab" id="open-camera" aria-selected="false"
                class="text-(--gray-600) hover:text-(--gray-900) dark:text-(--gray-300) dark:hover:text-(--gray-50) rounded-md px-4 py-2 transition">Use
                camera</button>
        </div>

        <div class="mt-4">
            <label for="selfie" id="selfie-drop" class="{{ $dropzone }}">
                <input id="selfie" name="selfie" type="file" @required(blank($draft['selfie_path'] ?? null))
                    accept="image/jpeg,image/png" data-error-message="Take or upload a selfie holding your ID." class="sr-only">

                <div data-empty class="{{ !empty($draft['selfie_path']) ? 'hidden' : 'flex' }} flex-col items-center">
                    <span class="{{ $iconBox }}">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.6" aria-hidden="true">
                            <circle cx="12" cy="9" r="3.5" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M5 20a7 7 0 0 1 14 0" />
                        </svg>
                    </span>
                    <span class="{{ $ink }} mt-3 text-sm font-semibold">Choose a selfie or drop
                        it here</span>
                    <span class="{{ $muted }} mt-1 text-xs">JPG or PNG. Photos are shrunk
                        automatically.</span>
                </div>

                <div data-filled
                    class="{{ !empty($draft['selfie_path']) ? 'flex' : 'hidden' }} w-full flex-col items-center">
                    <img alt="Selfie preview"
                        src="{{ $draftPreviewUrls['selfie'] ?? '' }}"
                        class="{{ !empty($draft['selfie_path']) ? '' : 'hidden' }} {{ $thumb }} max-h-56 w-auto max-w-full object-contain">
                    <span data-name
                        class="{{ $ink }} mt-3 max-w-full truncate text-sm font-medium">{{ !empty($draft['selfie_path']) ? 'Saved selfie' : '' }}</span>
                    <span class="{{ $link }} mt-0.5">Replace photo</span>
                </div>
            </label>

            <div id="camera-box" class="hidden">
                <div class="bg-(--gray-900) relative mx-auto w-full max-w-md overflow-hidden rounded-lg">
                    <video id="camera-video" autoplay playsinline muted
                        class="aspect-4/3 w-full -scale-x-100 object-cover"></video>
                    <div class="pointer-events-none absolute inset-0 flex items-center justify-center"
                        aria-hidden="true">
                        <div id="face-guide"
                            class="h-3/4 w-2/5 rounded-[50%] border-2 border-dashed border-white/70 transition-colors">
                        </div>
                    </div>
                </div>
                <p id="face-status"
                    class="text-(--gray-600) dark:text-(--gray-300) mt-3 text-center text-sm font-medium"
                    role="status" aria-live="polite"></p>
                <div class="mt-3 flex justify-center">
                    <button type="button" id="take-photo"
                        class="{{ $btn }} bg-(--blue-600) text-(--white) hover:bg-(--blue-700) focus-visible:ring-(--blue-600)">Take
                        photo</button>
                </div>
            </div>

            <p id="selfie-error" class="text-(--red-600) mt-1.5 text-sm" role="alert"></p>
        </div>
    </section>
</div>
