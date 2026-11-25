{{-- STEP 2: ID --}}
<div data-panel="2" class="{{ $step === 2 ? '' : 'hidden' }} space-y-3">
    <div>
        <h2 class="{{ $h2 }}">Upload your ID</h2>
        <p class="{{ $sub }}">Choose the ID type, then upload clear photos of both sides. Image checks run on this device.</p>
    </div>

    <div id="id-server-errors" role="alert"
        class="border-(--red-600) bg-(--white) text-(--red-600) dark:bg-(--gray-800) hidden rounded-md border p-3 text-sm"></div>

    <section class="{{ $card }} space-y-4">
        <x-form.input light raised name="document_type" label="Type of ID" type="select" required
            placeholder="Select" error-message="Choose the type of ID you are uploading." :options="array_combine($documentTypes, $documentTypes)" :value="$draft['document_type'] ?? null" />

        <ul class="bg-(--gray-50) text-(--gray-700) dark:bg-(--gray-900) dark:text-(--gray-200) space-y-1.5 rounded-lg p-4 text-sm">
            <li>✓ Keep all four corners visible.</li>
            <li>✓ Use even lighting and avoid reflections.</li>
            <li>✓ Hold the camera steady and keep the ID straight.</li>
        </ul>

        <x-scanner.id-scanner target="document" target-back="document_back" upload-target="id-uploads" />

        <div id="id-uploads" class="space-y-4">
            @php
                $frontIsPdf = strtolower(pathinfo((string) ($draft['document_path'] ?? ''), PATHINFO_EXTENSION)) === 'pdf';
                $frontUrl = $draftPreviewUrls['document'];
                $frontName = $frontUrl ? 'Saved front of ID' : null;
                $frontRequired = blank($draft['document_path'] ?? null);
                $frontPreviewIsImage = filled($draft['document_path'] ?? null) && ! $frontIsPdf;
                $backIsPdf = strtolower(pathinfo((string) ($draft['document_back_key'] ?? ''), PATHINFO_EXTENSION)) === 'pdf';
                $backUrl = $draftPreviewUrls['document_back'];
                $backName = $backUrl ? 'Saved back of ID' : null;
                $backRequired = blank($draft['document_back_key'] ?? null);
                $backPreviewIsImage = filled($draft['document_back_key'] ?? null) && ! $backIsPdf;
            @endphp

            <div class="space-y-3">
                <x-form.upload-docu name="document" id="document" label="Front of ID" accept=".jpg,.jpeg,.png,.pdf"
                    :max-mb="5" text="Choose or capture the front of your ID" :preview-url="$frontUrl"
                    :preview-name="$frontName" :preview-is-image="$frontPreviewIsImage"
                    capture="environment" data-id-upload="front" data-error-message="Upload the front of your ID." :required="$frontRequired" />

            </div>

            <div id="id-back-upload" class="space-y-3">
                <x-form.upload-docu name="document_back" id="document_back" label="Back of ID" accept=".jpg,.jpeg,.png,.pdf"
                    :max-mb="5" text="Choose or capture the back of your ID" :preview-url="$backUrl"
                    :preview-name="$backName" :preview-is-image="$backPreviewIsImage"
                    capture="environment" data-id-upload="back" data-error-message="Upload the back of your ID." :required="$backRequired" />
            </div>
        </div>
    </section>
</div>
