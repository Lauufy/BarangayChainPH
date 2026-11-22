{{--
    <x-form.upload name="document" label="Valid ID" :max-mb="10" accept=".jpg,.jpeg,.png,.pdf" required />

    Props: name, id, label, accept, max-mb, text. Extra attributes (required, etc.) go on the file input.
    Card with a title, a drag & drop box (shows a preview when the file is a photo), and a file row with open / remove.
    File type and size are checked here, so no extra JavaScript is needed on the page.
--}}
@props(['name', 'id' => null, 'label' => null, 'accept' => '.jpg,.jpeg,.png,.pdf', 'maxMb' => 10, 'text' => 'Drag & Drop or click to choose files'])

@php
    $fieldId = $id ?? $name;
    $btn = 'flex h-9 w-9 items-center justify-center rounded-lg border border-(--gray-200) text-(--gray-600) hover:bg-(--gray-100) dark:border-(--gray-600) dark:text-(--gray-300) dark:hover:bg-(--gray-700)';
    $svg = 'h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true';
@endphp

<div data-upload data-accept="{{ $accept }}" data-max="{{ $maxMb }}"
    class="flex min-w-0 flex-1 basis-80 flex-col gap-3 rounded-2xl border border-(--gray-200) bg-(--white) p-4 shadow-sm dark:border-(--gray-700) dark:bg-(--gray-800)">

    @if ($label)
        <span class="text-base font-semibold text-(--gray-800) dark:text-(--gray-100)">{{ $label }}</span>
    @endif

    {{-- Drop box + photo preview --}}
    <label for="{{ $fieldId }}" data-dropzone
        class="flex h-56 cursor-pointer overflow-hidden flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-(--gray-300) p-4 text-center transition focus-within:border-(--blue-600) hover:border-(--blue-600) hover:bg-(--gray-50) data-[dragging=true]:border-(--blue-600) data-[dragging=true]:bg-(--blue-50) dark:border-(--gray-600) dark:hover:bg-(--gray-700) dark:data-[dragging=true]:bg-(--gray-700)">
        <input id="{{ $fieldId }}" name="{{ $name }}" type="file" accept="{{ $accept }}" {{ $attributes->class('sr-only') }}>

        <img data-preview hidden alt="Preview" class="h-full w-full rounded-lg object-contain">

        <div data-placeholder class="flex flex-col items-center gap-2 text-(--gray-800) dark:text-(--gray-100) [[hidden]]:hidden">
            <svg class="{!! $svg !!}"><circle cx="12" cy="12" r="9.25" /><path d="M12 8v8M8 12h8" /></svg>
            <span class="text-sm font-semibold">{{ $text }}</span>
            <span class="text-xs text-(--gray-500) dark:text-(--gray-400)">Max file size: {{ $maxMb }} MB</span>
        </div>
    </label>

    {{-- Chosen file --}}
    <div data-file hidden class="flex items-center gap-3 rounded-xl border border-(--gray-200) p-3 dark:border-(--gray-700) [[hidden]]:hidden">
        <div class="min-w-0 flex-1">
            <p data-name class="truncate text-sm font-semibold text-(--gray-900) dark:text-(--gray-50)"></p>
            <p data-meta class="text-xs text-(--gray-500) dark:text-(--gray-400)"></p>
        </div>
        <button type="button" data-open class="{{ $btn }}" aria-label="Open file" title="Open file">
            <svg class="{!! $svg !!}"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5" /></svg>
        </button>
        <button type="button" data-remove class="{{ $btn }} hover:text-(--red-600)" aria-label="Remove file" title="Remove file">
            <svg class="{!! $svg !!}"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12h10l1-12M9 7V4h6v3" /></svg>
        </button>
    </div>

    <p data-error role="alert" class="text-sm text-(--red-600) empty:hidden dark:text-(--red-500)"></p>
</div>

@once
    <script>
        (() => {
            const size = (b) => b >= 1048576 ? (b / 1048576).toFixed(2) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB';
            const urls = new WeakMap();

            // Is the file an accepted type (".pdf", "image/*", "application/pdf")?
            const allowed = (f, accept) => accept.split(',').some((a) => {
                a = a.trim().toLowerCase();
                return a.startsWith('.') ? f.name.toLowerCase().endsWith(a) : a.endsWith('/*') ? f.type.startsWith(a.slice(0, -1)) : f.type === a;
            });

            function refresh(box) {
                const $ = (s) => box.querySelector(s);
                const input = $('input');
                const f = input.files[0];
                const error = !f ? '' : !allowed(f, box.dataset.accept) ? 'Allowed file types: ' + box.dataset.accept.replaceAll(',', ', ')
                    : f.size > box.dataset.max * 1048576 ? 'The file must not be larger than ' + box.dataset.max + ' MB.' : '';
                if (error) input.value = '';

                const file = error ? null : f;
                const isImage = !!file && file.type.startsWith('image/');

                URL.revokeObjectURL(urls.get(box));
                urls.set(box, isImage ? URL.createObjectURL(file) : '');

                $('[data-error]').textContent = error;
                $('[data-file]').hidden = !file;
                $('[data-preview]').hidden = !isImage;
                $('[data-placeholder]').hidden = isImage;
                if (isImage) $('[data-preview]').src = urls.get(box);
                if (file) {
                    $('[data-name]').textContent = file.name;
                    $('[data-meta]').textContent = '.' + file.name.split('.').pop().toLowerCase() + ' | ' + size(file.size);
                }
            }

            document.addEventListener('change', (e) => {
                if (e.target.type === 'file' && e.target.closest('[data-upload]')) refresh(e.target.closest('[data-upload]'));
            });

            document.addEventListener('click', (e) => {
                const box = e.target.closest('[data-upload]');
                const input = box?.querySelector('input');
                if (!input) return;
                if (e.target.closest('[data-open]') && input.files[0]) window.open(URL.createObjectURL(input.files[0]), '_blank');
                if (e.target.closest('[data-remove]')) { input.value = ''; refresh(box); }
            });

            // Drag and drop onto the box
            ['dragover', 'dragleave', 'drop'].forEach((type) => document.addEventListener(type, (e) => {
                const zone = e.target.closest?.('[data-dropzone]');
                if (!zone) return;
                e.preventDefault();
                zone.dataset.dragging = type === 'dragover';
                if (type === 'drop' && e.dataTransfer.files.length) {
                    zone.querySelector('input').files = e.dataTransfer.files;
                    refresh(zone.closest('[data-upload]'));
                }
            }));
        })();
    </script>
@endonce