<div class="space-y-4">
    <section class="rounded-lg border border-gray-200/80 bg-white p-3 shadow-xs sm:p-5 dark:border-gray-800 dark:bg-gray-900/50">
        <h3 class="border-b border-gray-100 pb-2.5 text-base font-bold text-(--blue-900) dark:border-gray-800 dark:text-(--blue-300)">
            Certificate Preview
        </h3>

        <div class="mt-4">
            @if (in_array($slug, ['barangay-clearance', 'business-clearance', 'certificate-of-indigency'], true))
                {{-- Holder is as wide as the screen; the scaler shrinks the full-size page to fit it and centers it --}}
                <div id="preview-holder"
                    class="w-full overflow-hidden rounded-md border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800">
                    <div id="preview-scaler" class="origin-top-left shadow-md" style="width: 816px;">
                        <iframe
                            id="certificate-preview"
                            title="Draft certificate preview"
                            scrolling="no"
                            class="block w-full border-0 bg-white"
                            style="height: 1056px;"
                            srcdoc="{{ view('permits.san-luis', [
                                'type' => $service['type'],
                                'name' => $personalInfo['Name'],
                                'age' => $personalInfo['Age'],
                                'address' => $personalInfo['Address'],
                                'nationality' => $personalInfo['Nationality'],
                                'purposeText' => '[[purpose]]',
                                'details' => [],
                            ])->render() }}"
                        ></iframe>
                    </div>
                </div>

                <script>
                    (() => {
                        const PAGE_W = 816; // paper width in px (8.5in at 96dpi). Change if your permit page is wider.
                        const holder = document.getElementById('preview-holder');
                        const scaler = document.getElementById('preview-scaler');
                        const frame = document.getElementById('certificate-preview');

                        const fit = () => {
                            const width = holder.clientWidth;
                            if (!width) return; // step is hidden, so measure later

                            const scale = Math.min(1, width / PAGE_W);
                            const page = frame.contentDocument?.documentElement;
                            const height = Math.max(page?.scrollHeight ?? 0, 1056);

                            frame.style.height = height + 'px';
                            scaler.style.transform = `scale(${scale})`;
                            scaler.style.marginLeft = Math.max(0, (width - PAGE_W * scale) / 2) + 'px';
                            holder.style.height = height * scale + 'px';
                        };

                        // Runs when the step becomes visible, the window resizes, or the page loads
                        new ResizeObserver(fit).observe(holder);
                        frame.addEventListener('load', fit);
                    })();
                </script>
            @else
                <div class="mx-auto max-w-3xl rounded-md border border-(--gray-200) bg-(--white) p-4 text-(--gray-900) shadow-sm sm:p-10 dark:border-(--gray-700)">
                    <div class="text-center">
                        <p class="text-xs font-semibold uppercase tracking-wide sm:text-sm">Republic of the Philippines</p>
                        <p class="text-xs sm:text-sm">Barangay San Luis, Antipolo City, Rizal</p>
                        <h4 class="mt-5 text-lg font-bold uppercase tracking-wide sm:mt-8 sm:text-2xl">
                            {{ $service['title'] }}
                        </h4>
                    </div>

                    <div class="mt-6 space-y-4 break-words text-xs leading-6 text-(--gray-700) sm:mt-10 sm:space-y-6 sm:text-sm sm:leading-7">
                        <p>To whom it may concern:</p>

                        <p>
                            This is to certify that
                            <strong>{{ $personalInfo['Name'] }}</strong>,
                            {{ $personalInfo['Age'] ?? '—' }} years old,
                            and a resident of
                            <strong>{{ $personalInfo['Address'] }}</strong>,
                            is requesting a {{ $service['title'] }}
                            for the following purpose:
                        </p>

                        <p id="preview-purpose" class="min-h-12 border-b border-(--gray-400) pb-2 font-semibold sm:min-h-20"></p>

                        <p>This certification is issued upon the request of the interested party for whatever legal purpose it may serve.</p>
                    </div>

                    <div class="mt-10 text-center text-xs sm:mt-16 sm:text-sm">
                        <p class="font-semibold">Barangay Official</p>
                        <p class="mt-8 border-t border-(--gray-400) pt-2 sm:mt-10">Authorized signature</p>
                    </div>
                </div>
            @endif
        </div>
    </section>
</div>