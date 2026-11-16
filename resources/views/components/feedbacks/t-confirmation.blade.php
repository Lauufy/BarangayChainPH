{{--
    Confirmation toast. Include once in the layout: <x-feedbacks.t-confirmation />
    Open it from JS:
        tConfirmation({ title: 'Delete this user?', text: 'This action cannot be undone.' })
            .then(ok => { if (ok) form.submit(); });
    Options: title, text, confirmLabel (default "Yes, delete"), cancelLabel (default "Cancel").
    Colors follow the site palette: light box in light mode, dark box in dark mode (.dark / data-theme="dark").
--}}
<div id="tConfirmation" class="fixed inset-0 hidden items-center justify-center p-4" style="z-index: 2147483647"
    role="alertdialog" aria-modal="true" aria-labelledby="tConfirmationTitle" aria-describedby="tConfirmationText">
    <div class="tc-box w-72 max-w-full rounded-2xl p-4 text-center shadow-2xl">
        <h3 id="tConfirmationTitle" class="tc-title text-base font-semibold"></h3>
        <p id="tConfirmationText" class="tc-text mt-2 text-sm leading-relaxed"></p>

        <div class="mt-4 grid grid-cols-2 gap-2">
            <button type="button" data-cancel class="tc-cancel h-10 rounded-lg text-sm font-medium transition"></button>
            <button type="button" data-confirm class="tc-ok h-10 rounded-lg text-sm font-medium transition"></button>
        </div>
    </div>
</div>

@once
    <style>
        /* Dim + blur everything behind the toast while it is open (same look as the Create modal) */
        #tConfirmation { background: rgb(15 23 42 / .6); -webkit-backdrop-filter: blur(4px); backdrop-filter: blur(4px); }

        /* Light */
        #tConfirmation .tc-box { background: var(--white); border: 1px solid var(--gray-200); }
        #tConfirmation .tc-title { color: var(--gray-900); }
        #tConfirmation .tc-text { color: var(--gray-500); }
        #tConfirmation .tc-cancel { background: var(--gray-100); color: var(--gray-700); }
        #tConfirmation .tc-cancel:hover { background: var(--gray-200); }
        #tConfirmation .tc-ok { background: var(--blue-600); color: var(--white); }
        #tConfirmation .tc-ok:hover { background: var(--blue-700); }
        #tConfirmation button:focus-visible { outline: 2px solid var(--blue-400); outline-offset: 2px; }

        /* Dark */
        .dark #tConfirmation .tc-box, [data-theme="dark"] #tConfirmation .tc-box { background: var(--blue-900); border-color: var(--gray-700); }
        .dark #tConfirmation .tc-title, [data-theme="dark"] #tConfirmation .tc-title { color: var(--white); }
        .dark #tConfirmation .tc-text, [data-theme="dark"] #tConfirmation .tc-text { color: var(--gray-400); }
        .dark #tConfirmation .tc-cancel, [data-theme="dark"] #tConfirmation .tc-cancel { background: var(--gray-700); color: var(--gray-100); }
        .dark #tConfirmation .tc-cancel:hover, [data-theme="dark"] #tConfirmation .tc-cancel:hover { background: var(--gray-600); }
        .dark #tConfirmation .tc-ok, [data-theme="dark"] #tConfirmation .tc-ok { background: var(--blue-500); }
        .dark #tConfirmation .tc-ok:hover, [data-theme="dark"] #tConfirmation .tc-ok:hover { background: var(--blue-400); }
    </style>

    <script>
        window.tConfirmation = function (o = {}) {
            var box = document.getElementById('tConfirmation');
            var ok = box.querySelector('[data-confirm]');
            var no = box.querySelector('[data-cancel]');

            document.getElementById('tConfirmationTitle').textContent = o.title || 'Are you sure?';
            document.getElementById('tConfirmationText').textContent = o.text || '';
            ok.textContent = o.confirmLabel || 'Yes, delete';
            no.textContent = o.cancelLabel || 'Cancel';

            box.classList.remove('hidden');
            box.classList.add('flex');
            no.focus(); // safe default for a delete action

            return new Promise(function (resolve) {
                function done(result) {
                    box.classList.add('hidden');
                    box.classList.remove('flex');
                    ok.onclick = no.onclick = box.onclick = null;
                    document.removeEventListener('keydown', onKey);
                    resolve(result);
                }
                function onKey(e) { if (e.key === 'Escape') done(false); }

                ok.onclick = function () { done(true); };
                no.onclick = function () { done(false); };
                box.onclick = function (e) { if (e.target === box) done(false); };
                document.addEventListener('keydown', onKey);
            });
        };
    </script>
@endonce