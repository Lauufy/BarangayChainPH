{{-- Popup messages (top right). Put <x-toast /> once, before </body> in your layout. --}}

{{-- Where the popups appear --}}
<div id="toast-container"
    class="pointer-events-none fixed right-4 top-4 z-50 flex w-[min(20rem,calc(100vw-2rem))] flex-col gap-3"
    aria-live="polite"></div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('toast-container');

        // Look of each type: box colors, icon color, icon symbol, default title
        const styles = {
            success: { box: 'border-green-400 bg-green-950', icon: 'bg-green-400', glyph: '✓', title: 'Success' },
            error: { box: 'border-red-400 bg-red-950', icon: 'bg-red-400', glyph: '!', title: 'Error' },
            warning: { box: 'border-orange-400 bg-orange-950', icon: 'bg-orange-400', glyph: '!', title: 'Warning' },
            info: { box: 'border-blue-400 bg-blue-950', icon: 'bg-blue-400', glyph: 'i', title: 'Info' },
        };

        const closeIcon =
            '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">' +
            '<path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" /></svg>';

        // Small helper to make an element with classes and text
        const make = (tag, classes, text) => {
            const el = document.createElement(tag);
            el.className = classes;
            if (text) el.textContent = text;
            return el;
        };

        // Show one popup: showToast('success', 'Saved!')
        window.showToast = (type, message, title) => {
            const style = styles[type] || styles.info;

            // Popup box (starts hidden on the right)
            const toast = make(
                'div',
                'pointer-events-auto flex translate-x-full items-center gap-3 rounded-md border-l-4 px-4 py-3 opacity-0 shadow-lg shadow-black/30 transition-all duration-300 ease-out ' + style.box
            );
            toast.setAttribute('role', type === 'error' ? 'alert' : 'status');

            // Icon
            const icon = make(
                'span',
                'flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white ' + style.icon,
                style.glyph
            );
            icon.setAttribute('aria-hidden', 'true');

            // Title and message
            const text = make('div', 'min-w-0 flex-1');
            text.appendChild(make('p', 'text-sm font-semibold leading-5 text-white', title || style.title));
            text.appendChild(make('p', 'text-xs leading-5 text-slate-300', message));

            // Close button
            const closeButton = make(
                'button',
                'shrink-0 rounded p-0.5 text-white/80 transition hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60'
            );
            closeButton.type = 'button';
            closeButton.setAttribute('aria-label', 'Dismiss');
            closeButton.innerHTML = closeIcon;

            toast.append(icon, text, closeButton);
            container.appendChild(toast);

            // Slide out to the right, then remove
            let timer;

            const close = () => {
                clearTimeout(timer);
                toast.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            };

            // Auto close after 5 seconds (pauses while the mouse is on it)
            const startTimer = () => (timer = setTimeout(close, 5000));

            closeButton.addEventListener('click', close);
            toast.addEventListener('mouseenter', () => clearTimeout(timer));
            toast.addEventListener('mouseleave', startTimer);

            // Slide in from the right
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    toast.classList.remove('translate-x-full', 'opacity-0');
                });
            });

            startTimer();
        };

        // Messages sent from the server
        @if (session('success'))
            showToast('success', @json(session('success')));
        @endif

        @if (session('status'))
            showToast('success', @json(session('status')));
        @endif

        @if (session('error'))
            showToast('error', @json(session('error')));
        @endif

        @if (session('warning'))
            showToast('warning', @json(session('warning')));
        @endif

        @if (session('info'))
            showToast('info', @json(session('info')));
        @endif
    });
</script>