<div id="tConfirmation" class="fixed inset-0 hidden items-center justify-center p-4" style="z-index: 2147483647"
    role="alertdialog" aria-modal="true" aria-labelledby="tConfirmationTitle" aria-describedby="tConfirmationText">
    <div class="tc-box w-full max-w-md rounded-2xl p-6 shadow-2xl">
        {{-- Header: title on the left, close button on the right --}}
        <div class="flex items-start justify-between gap-4">
            <h3 id="tConfirmationTitle" class="tc-title text-lg font-semibold leading-snug"></h3>
            <button type="button" data-close aria-label="Close"
                class="tc-close -mr-1 -mt-1 grid size-8 shrink-0 place-items-center rounded-lg transition">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 6 6 18" />
                    <path d="m6 6 12 12" />
                </svg>
            </button>
        </div>

        {{-- Message --}}
        <p id="tConfirmationText" class="tc-text mt-4 text-sm leading-relaxed"></p>

        {{-- Actions: two equal buttons --}}
        <div class="mt-6 grid grid-cols-2 gap-3">
            <button type="button" data-cancel class="tc-cancel h-11 rounded-lg text-sm font-semibold transition"></button>
            <button type="button" data-confirm class="tc-ok h-11 rounded-lg text-sm font-semibold transition"></button>
        </div>
    </div>
</div>

@once
    <style>
        /* Dim + blur everything behind the dialog while it is open (same look as the Create modal) */
        #tConfirmation { background: rgb(15 23 42 / .6); -webkit-backdrop-filter: blur(4px); backdrop-filter: blur(4px); }

        /* Light */
        #tConfirmation .tc-box { background: var(--white); border: 1px solid var(--gray-200); }
        #tConfirmation .tc-title { color: var(--gray-900); }
        #tConfirmation .tc-text { color: var(--gray-500); }
        #tConfirmation .tc-close { color: var(--gray-400); }
        #tConfirmation .tc-close:hover { background: var(--gray-100); color: var(--gray-700); }
        #tConfirmation .tc-cancel { background: var(--gray-100); color: var(--gray-700); border: 1px solid var(--gray-200); }
        #tConfirmation .tc-cancel:hover { background: var(--gray-200); }
        #tConfirmation .tc-ok { background: var(--blue-600); color: var(--white); border: 1px solid transparent; }
        #tConfirmation .tc-ok:hover { background: var(--blue-700); }
        #tConfirmation button:focus-visible { outline: 2px solid var(--blue-400); outline-offset: 2px; }

        /* Dark */
        .dark #tConfirmation .tc-box, [data-theme="dark"] #tConfirmation .tc-box { background: var(--blue-900); border-color: var(--gray-700); }
        .dark #tConfirmation .tc-title, [data-theme="dark"] #tConfirmation .tc-title { color: var(--white); }
        .dark #tConfirmation .tc-text, [data-theme="dark"] #tConfirmation .tc-text { color: var(--gray-400); }
        .dark #tConfirmation .tc-close, [data-theme="dark"] #tConfirmation .tc-close { color: var(--gray-500); }
        .dark #tConfirmation .tc-close:hover, [data-theme="dark"] #tConfirmation .tc-close:hover { background: rgb(255 255 255 / .08); color: var(--gray-100); }
        .dark #tConfirmation .tc-cancel, [data-theme="dark"] #tConfirmation .tc-cancel { background: var(--gray-700); color: var(--gray-100); border-color: var(--gray-600); }
        .dark #tConfirmation .tc-cancel:hover, [data-theme="dark"] #tConfirmation .tc-cancel:hover { background: var(--gray-600); }
        .dark #tConfirmation .tc-ok, [data-theme="dark"] #tConfirmation .tc-ok { background: var(--blue-500); }
        .dark #tConfirmation .tc-ok:hover, [data-theme="dark"] #tConfirmation .tc-ok:hover { background: var(--blue-400); }
        #tConfirmation .tc-ok[data-variant="danger"] { background: var(--red-600); }
        #tConfirmation .tc-ok[data-variant="danger"]:hover { filter: brightness(.9); }
        #tConfirmation .tc-ok[data-variant="danger"]:focus-visible { outline-color: var(--red-500); }
        .dark #tConfirmation .tc-ok[data-variant="danger"],
        [data-theme="dark"] #tConfirmation .tc-ok[data-variant="danger"] { background: var(--red-500); }
    </style>

    <script>
        window.tConfirmation = function (o = {}) {
            var box = document.getElementById('tConfirmation');
            var ok = box.querySelector('[data-confirm]');
            var no = box.querySelector('[data-cancel]');
            var x = box.querySelector('[data-close]');

            document.getElementById('tConfirmationTitle').textContent = o.title || 'Are you sure?';
            document.getElementById('tConfirmationText').textContent = o.text || '';
            ok.textContent = o.confirmLabel || 'Yes, delete';
            no.textContent = o.cancelLabel || 'Cancel';
            ok.dataset.variant = o.confirmVariant || 'default';

            box.classList.remove('hidden');
            box.classList.add('flex');
            no.focus(); // safe default for a delete action

            return new Promise(function (resolve) {
                function done(result) {
                    box.classList.add('hidden');
                    box.classList.remove('flex');
                    ok.onclick = no.onclick = x.onclick = box.onclick = null;
                    document.removeEventListener('keydown', onKey);
                    resolve(result);
                }
                function onKey(e) { if (e.key === 'Escape') done(false); }

                ok.onclick = function () { done(true); };
                no.onclick = function () { done(false); };
                x.onclick = function () { done(false); };
                box.onclick = function (e) { if (e.target === box) done(false); };
                document.addEventListener('keydown', onKey);
            });
        };
    </script>
@endonce