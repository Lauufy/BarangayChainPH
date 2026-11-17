@props([
    'id',
    'title',
    'subtitle' => null,
    'size' => 'max-w-3xl',
    'open' => false,
    'closeOnBackdrop' => true,
])

<div id="{{ $id }}" data-modal @if (!$closeOnBackdrop) data-static @endif role="dialog" aria-modal="true"
    aria-labelledby="{{ $id }}-title"
    class="{{ $open ? 'flex' : 'hidden' }} fixed inset-0 z-50 items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
    <section
        class="flex max-h-[90vh] w-full {{ $size }} flex-col overflow-hidden rounded-none border border-slate-200 border-t-4 border-t-(--gold-400) bg-white text-slate-900 shadow-2xl">

        {{-- Header --}}
        <header class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
            <div class="flex min-w-0 items-start gap-4">
                @isset($icon)
                    <span class="grid size-11 shrink-0 place-items-center bg-(--blue-700) text-white">
                        {{ $icon }}
                    </span>
                @endisset

                <div class="min-w-0">
                    <h2 id="{{ $id }}-title" class="text-lg font-semibold leading-tight text-slate-900">
                        {{ $title }}</h2>
                    @if ($subtitle)
                        <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>

            <button type="button" data-modal-close aria-label="Close"
                class="grid size-8 shrink-0 place-items-center text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-(--blue-500)">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 6 6 18" />
                    <path d="m6 6 12 12" />
                </svg>
            </button>
        </header>

        {{-- Body (the only part that scrolls) --}}
        <div data-modal-body class="min-h-0 flex-1 overflow-y-auto">
            {{ $slot }}
        </div>

        {{-- Footer --}}
        @isset($footer)
            <footer class="border-t border-slate-200 bg-slate-50 px-6 py-4">
                {{ $footer }}
            </footer>
        @endisset
    </section>
</div>

@once
    <script>
        (function() {
            var openers = new WeakMap();
            var downTarget = null;

            function all() {
                return Array.prototype.slice.call(document.querySelectorAll('[data-modal]'));
            }

            function isOpen(modal) {
                return !modal.classList.contains('hidden');
            }

            function close(modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            function firstField(modal) {
                var fields = modal.querySelectorAll(
                    '[data-modal-body] input:not([type="hidden"]):not([type="file"]):not([type="checkbox"]):not([disabled]),' +
                    '[data-modal-body] select:not([disabled]),' +
                    '[data-modal-body] textarea:not([disabled])'
                );

                for (var i = 0; i < fields.length; i++) {
                    if (fields[i].offsetParent !== null) return fields[i];
                }

                return null;
            }

            // Runs whenever a modal's classes change (opened or closed by any script).
            function onChange(modal) {
                var open = isOpen(modal);

                if (open && !openers.has(modal)) {
                    openers.set(modal, document.activeElement);
                    var field = firstField(modal);
                    if (field) field.focus();
                } else if (!open && openers.has(modal)) {
                    var back = openers.get(modal);
                    openers.delete(modal);
                    if (back && back.focus && document.contains(back)) back.focus();
                }

                document.body.classList.toggle('overflow-hidden', all().some(isOpen));
            }

            function init() {
                var observer = new MutationObserver(function(records) {
                    records.forEach(function(record) {
                        onChange(record.target);
                    });
                });

                all().forEach(function(modal) {
                    observer.observe(modal, {
                        attributes: true,
                        attributeFilter: ['class']
                    });
                    if (isOpen(modal)) onChange(modal);
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }

            // Backdrop: close only when the press AND the release both happen on the backdrop,
            // so drag-selecting text that ends outside the modal never closes it.
            document.addEventListener('mousedown', function(e) {
                downTarget = e.target;
            });

            document.addEventListener('click', function(e) {
                var closeBtn = e.target.closest('[data-modal-close]');

                if (closeBtn) {
                    close(closeBtn.closest('[data-modal]'));
                    return;
                }

                if (e.target.matches('[data-modal]') && downTarget === e.target && !e.target.hasAttribute(
                        'data-static')) {
                    close(e.target);
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key !== 'Escape') return;
                var open = all().filter(isOpen);
                if (open.length) close(open[open.length - 1]);
            });
        })();
    </script>
@endonce