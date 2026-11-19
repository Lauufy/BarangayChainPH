@props(['id', 'title', 'subtitle' => null, 'size' => 'max-w-3xl', 'open' => false, 'closeOnBackdrop' => true])

<div id="{{ $id }}" data-modal @if (!$closeOnBackdrop) data-static @endif role="dialog"
    aria-modal="true" aria-labelledby="{{ $id }}-title"
    class="{{ $open ? 'flex' : 'hidden' }} fixed inset-0 z-50 items-center justify-center bg-slate-950/55 p-3 backdrop-blur-[2px] sm:p-5">
    <section
        class="{{ $size }} border-(--gray-200) bg-(--white) text-(--gray-900) dark:bg-(--blue-850) dark:text-(--off-white) flex max-h-[92vh] w-full flex-col overflow-hidden rounded-xl border shadow-2xl dark:border-white/10">

        {{-- Header --}}
        <header
            class="border-(--gray-200) bg-(--white) dark:bg-(--blue-850) flex items-start justify-between gap-4 border-b px-5 py-4 sm:px-6 dark:border-white/10">
            <div class="flex min-w-0 items-start gap-3">
                @isset($icon)
                    <div
                        class="border-(--celadon-600)/30 bg-(--celadon-500)/10 text-(--blue-700) dark:text-(--celadon-500) grid size-10 shrink-0 place-items-center border dark:border-white/10 dark:bg-white/5">
                        {{ $icon }}
                    </div>
                @endisset

                <div class="min-w-0">
                    <h2 id="{{ $id }}-title"
                        class="mt-2 text-(--gray-900) dark:text-(--off-white) text-xl font-semibold tracking-tight">
                        {{ $title }}
                    </h2>

                    @if ($subtitle)
                        <p class="text-(--gray-500) dark:text-(--gray-300) text-sm leading-relaxed">
                            {{ $subtitle }}
                        </p>
                    @endif
                </div>
            </div>

            <button type="button" data-modal-close aria-label="Close dialog"
                class="text-(--gray-500) hover:bg-(--gray-100) hover:text-(--gray-900) focus-visible:ring-(--blue-500) dark:text-(--gray-300) dark:hover:text-(--celadon-500) dark:focus-visible:ring-(--celadon-500) grid size-9 shrink-0 place-items-center transition focus-visible:outline-none focus-visible:ring-2 dark:hover:bg-white/10">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12" />
                </svg>
            </button>
        </header>

        {{-- Content --}}
        <div data-modal-body
            class="bg-(--white) text-(--gray-800) dark:bg-(--blue-850) dark:text-(--gray-200) min-h-0 flex-1 overflow-y-auto overscroll-contain">
            {{ $slot }}
        </div>

        {{-- Footer --}}
        @isset($footer)
            <footer
                class="border-(--gray-200) bg-(--white) dark:bg-(--blue-850) border-t px-5 py-4 sm:px-6 dark:border-white/10">
                {{ $footer }}
            </footer>
        @endisset
    </section>
</div>

@once
    <script>
        (() => {
            const modals = () => [...document.querySelectorAll('[data-modal]')];
            const openModals = () => modals().filter(modal => !modal.classList.contains('hidden'));
            const focusReturn = new WeakMap();
            let mouseDownTarget = null;

            function close(modal) {
                if (!modal) return;
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            function focusable(modal) {
                return modal.querySelector(
                    '[data-modal-body] input:not([type="hidden"]):not([type="file"]):not([type="checkbox"]):not([disabled]), ' +
                    '[data-modal-body] select:not([disabled]), ' +
                    '[data-modal-body] textarea:not([disabled]), ' +
                    '[data-modal-body] button:not([disabled])'
                );
            }

            function sync(modal) {
                const isOpen = !modal.classList.contains('hidden');

                if (isOpen && !focusReturn.has(modal)) {
                    focusReturn.set(modal, document.activeElement);

                    const first = focusable(modal);
                    if (first) first.focus();
                }

                if (!isOpen && focusReturn.has(modal)) {
                    const previous = focusReturn.get(modal);
                    focusReturn.delete(modal);

                    if (previous?.isConnected && typeof previous.focus === 'function') {
                        previous.focus();
                    }
                }

                document.body.classList.toggle('overflow-hidden', openModals().length > 0);
            }

            function init() {
                const observer = new MutationObserver(records => {
                    records.forEach(record => sync(record.target));
                });

                modals().forEach(modal => {
                    observer.observe(modal, {
                        attributes: true,
                        attributeFilter: ['class']
                    });

                    if (!modal.classList.contains('hidden')) {
                        sync(modal);
                    }
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init, {
                    once: true
                });
            } else {
                init();
            }

            document.addEventListener('mousedown', event => {
                mouseDownTarget = event.target;
            });

            document.addEventListener('click', event => {
                const closeButton = event.target.closest('[data-modal-close]');

                if (closeButton) {
                    close(closeButton.closest('[data-modal]'));
                    return;
                }

                const modal = event.target;

                if (
                    modal.matches('[data-modal]') &&
                    mouseDownTarget === modal &&
                    !modal.hasAttribute('data-static')
                ) {
                    close(modal);
                }
            });

            document.addEventListener('keydown', event => {
                if (event.key !== 'Escape') return;

                const open = openModals();
                if (open.length) close(open[open.length - 1]);
            });
        })
        ();
    </script>
@endonce
