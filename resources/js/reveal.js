const SELECTOR = '[data-reveal]';
const GROUP_SELECTOR = '[data-reveal-group]';
const DEFAULT_STEP = 90;

const prefersReducedMotion = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

let observer = null;

function getObserver() {
    if (observer || !('IntersectionObserver' in window)) return observer;

    observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                const el = entry.target;

                if (entry.isIntersecting) {
                    el.classList.add('is-visible');
                    if (!el.hasAttribute('data-reveal-repeat')) observer.unobserve(el);
                } else if (el.hasAttribute('data-reveal-repeat')) {
                    el.classList.remove('is-visible');
                }
            });
        },
        { threshold: 0.12, rootMargin: '0px 0px -8% 0px' }
    );

    return observer;
}

/** Children of a group inherit its animation type and get a staggered delay. */
function applyGroups(root) {
    const groups = [...root.querySelectorAll(GROUP_SELECTOR)];
    if (root.matches?.(GROUP_SELECTOR)) groups.unshift(root);

    groups.forEach((group) => {
        const type = group.dataset.revealGroup || 'up';
        const step = Number(group.dataset.revealStep) || DEFAULT_STEP;

        [...group.children].forEach((child, i) => {
            child.dataset.reveal ||= type;
            child.style.setProperty('--reveal-delay', `${i * step}ms`);
        });
    });
}

function observe(el) {
    if (el.dataset.revealBound) return;
    el.dataset.revealBound = '1';

    if (el.dataset.revealDelay) {
        el.style.setProperty('--reveal-delay', `${Number(el.dataset.revealDelay)}ms`);
    }

    const io = getObserver();

    if (prefersReducedMotion() || !io) {
        el.classList.add('is-visible');
        return;
    }

    io.observe(el);
}

function init(root = document) {
    if (!root) return;

    applyGroups(root);

    const targets = [...root.querySelectorAll(SELECTOR)];
    if (root.matches?.(SELECTOR)) targets.unshift(root);

    targets.forEach(observe);
}

function show(el) {
    el.classList.add('is-visible');
    observer?.unobserve(el);
}

function reset(el) {
    el.classList.remove('is-visible');
    delete el.dataset.revealBound;
    observe(el);
}

const Reveal = { init, observe, show, reset };

// Auto-start once the DOM is ready.
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => init());
} else {
    init();
}

// Optional: re-scan after Livewire / Turbo navigations so new content animates too.
document.addEventListener('livewire:navigated', () => init());
document.addEventListener('turbo:load', () => init());

window.Reveal = Reveal;

export { init, observe, show, reset };
export default Reveal;