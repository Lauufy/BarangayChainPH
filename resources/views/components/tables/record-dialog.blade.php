<dialog data-record-dialog aria-label="Record details" class="record-sheet bg-white text-slate-900 dark:bg-slate-900 dark:text-slate-100">
    <div class="mx-auto mb-4 h-1 w-10 rounded-full bg-slate-200 dark:bg-slate-700" aria-hidden="true"></div>
    <button type="button" data-sheet-close aria-label="Close details" class="absolute right-2 top-3 grid size-11 place-items-center text-slate-500">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
    </button>
    <p data-sheet-reference class="pr-10 font-mono text-xs text-indigo-600 dark:text-indigo-300"></p>
    <h2 data-sheet-title class="mt-1 pr-10 text-lg font-semibold"></h2>
    <dl class="my-4 grid gap-2 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-700 dark:bg-slate-800"></dl>
    <p data-sheet-note class="mb-4 whitespace-pre-line text-sm text-slate-600 dark:text-slate-300"></p>
    <div data-sheet-actions></div>
    <button type="button" data-sheet-close class="record-done" autofocus>Done</button>
</dialog>
