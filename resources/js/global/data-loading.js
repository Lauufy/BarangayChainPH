const SPINNER = `
    <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg"
        fill="none" viewBox="0 0 24 24" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10"
            stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor"
            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
    </svg>`;

const LOCKABLE_FIELDS = [
    "input:not([type]):not(:disabled)",
    "input[type='text']:not(:disabled)",
    "input[type='search']:not(:disabled)",
    "input[type='url']:not(:disabled)",
    "input[type='tel']:not(:disabled)",
    "input[type='email']:not(:disabled)",
    "input[type='password']:not(:disabled)",
    "input[type='date']:not(:disabled)",
    "input[type='month']:not(:disabled)",
    "input[type='week']:not(:disabled)",
    "input[type='time']:not(:disabled)",
    "input[type='datetime-local']:not(:disabled)",
    "input[type='number']:not(:disabled)",
    "textarea:not(:disabled)",
].join(", ");

function startLoading(el) {
    if (el.dataset.loadingActive === "true") return;

    el.dataset.originalHtml = el.innerHTML;
    el.dataset.loadingActive = "true";

    const text = el.dataset.loadingText || "Loading...";

    el.innerHTML = SPINNER + `<span>${text}</span>`;
    el.setAttribute("aria-busy", "true");
    el.classList.add("pointer-events-none", "opacity-70");

    if ("disabled" in el) el.disabled = true;
}

function stopLoading(el) {
    if (el.dataset.loadingActive !== "true") return;

    el.innerHTML = el.dataset.originalHtml;
    delete el.dataset.originalHtml;
    delete el.dataset.loadingActive;

    el.removeAttribute("aria-busy");
    el.classList.remove("pointer-events-none", "opacity-70");

    if ("disabled" in el) el.disabled = false;
}

function setFormLocked(form, locked) {
    if (locked) {
        form.dataset.loadingFieldsLocked = "true";
    } else {
        delete form.dataset.loadingFieldsLocked;
    }

    form.querySelectorAll(LOCKABLE_FIELDS).forEach((field) => {
        if (locked) {
            if (field.readOnly) return;

            field.dataset.loadingWasReadOnly = "false";
            const ariaReadonly = field.getAttribute("aria-readonly");
            field.dataset.loadingHadAriaReadonly = String(ariaReadonly !== null);

            if (ariaReadonly !== null) {
                field.dataset.loadingAriaReadonly = ariaReadonly;
            }

            field.readOnly = true;
            field.setAttribute("aria-readonly", "true");
            return;
        }

        if (field.dataset.loadingWasReadOnly !== "false") return;

        field.readOnly = false;
        delete field.dataset.loadingWasReadOnly;

        if (field.dataset.loadingHadAriaReadonly === "true") {
            field.setAttribute("aria-readonly", field.dataset.loadingAriaReadonly);
        } else {
            field.removeAttribute("aria-readonly");
        }

        delete field.dataset.loadingHadAriaReadonly;
        delete field.dataset.loadingAriaReadonly;
    });
}

// Form submission
document.addEventListener("submit", (e) => {
    const form = e.target;

    if (!form.matches("form")) return;

    const submitter = e.submitter;

    const buttons = submitter?.matches("[data-loading]")
        ? [submitter]
        : [...form.querySelectorAll('button[type="submit"][data-loading]')];

    if (buttons.length > 0) setFormLocked(form, true);

    buttons.forEach(startLoading);
});

// Link navigation
document.addEventListener("click", (e) => {
    const link = e.target.closest("a[data-loading]");

    if (!link || e.defaultPrevented || e.button !== 0) return;
    if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
    if (link.target && link.target !== "_self") return;

    startLoading(link);
});

// Restore after browser back/forward navigation
window.addEventListener("pageshow", () => {
    document.querySelectorAll("[data-loading]").forEach(stopLoading);
    document.querySelectorAll("form[data-loading-fields-locked]").forEach((form) => {
        setFormLocked(form, false);
    });
});
