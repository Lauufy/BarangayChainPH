const SPINNER = `
    <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
    </svg>`;

// Show the spinner and block the button or link
function startLoading(el) {
    el.dataset.html = el.innerHTML;
    el.innerHTML = SPINNER + (el.dataset.loadingText || "Loading...");
    el.disabled = true;
    el.classList.add("pointer-events-none", "opacity-70");
}

// Put everything back
function stopLoading(el) {
    if (!el.dataset.html) return;
    el.innerHTML = el.dataset.html;
    el.disabled = false;
    el.classList.remove("pointer-events-none", "opacity-70");
    delete el.dataset.html;
}

// When a form is submitted
document.addEventListener("submit", (e) => {
    const buttons = e.target.querySelectorAll("button[data-loading]");
    setTimeout(() => buttons.forEach(startLoading), 0);
});

// When a link is clicked
document.addEventListener("click", (e) => {
    const link = e.target.closest("a[data-loading]");
    if (link) startLoading(link);
});

// When the user presses the Back button
window.addEventListener("pageshow", () => {
    document.querySelectorAll("[data-loading]").forEach(stopLoading);
});
