const fieldText = cell => {
    const user = cell.querySelector('[data-user-cell]');
    if (user) return [...user.querySelectorAll('p')].map(p => p.textContent.trim()).join('\n');
    return cell.textContent.trim().replace(/\s+/g, ' ');
};

document.addEventListener('click', event => {
    const opener = event.target.closest('[data-record-open]');
    if (!opener) return;

    const row = opener.closest('tr');
    const sheet = row.closest('.tbl-container').querySelector('[data-record-dialog]');
    const title = row.querySelector('[data-title]') || row.cells[0];
    const reference = row.querySelector('[data-card-reference]')?.textContent.trim() || '';
    const name = title.querySelector('[data-user-cell] p')?.textContent.trim() || fieldText(title);
    sheet.querySelector('[data-sheet-reference]').textContent = reference;
    sheet.querySelector('[data-sheet-title]').textContent = name;
    sheet.setAttribute('aria-label', name + ' details');
    const note = sheet.querySelector('[data-sheet-note]');
    note.textContent = row.dataset.recordNote || '';
    note.hidden = !note.textContent;

    const list = sheet.querySelector('dl');
    list.replaceChildren();
    const headers = row.closest('table').tHead.rows[0].cells;
    for (const cell of row.cells) {
        if (cell.matches('[data-card-footer], [data-side="full"], [data-reference]')) continue;
        if (cell === title && !title.querySelector('[data-user-cell] p + p')) continue;
        const label = cell.dataset.label || headers[cell.cellIndex]?.textContent.trim();
        if (!label) continue;
        const field = document.createElement('div');
        const term = document.createElement('dt');
        const value = document.createElement('dd');
        term.textContent = label;
        const link = cell.querySelector('a[href]');
        if (link) {
            value.append(link.cloneNode(true));
        } else {
            value.textContent = cell.querySelector('[data-changes]')?.dataset.changes || fieldText(cell);
        }
        field.append(term, value);
        list.append(field);
    }
    const actions = sheet.querySelector('[data-sheet-actions]');
    const groups = [...row.querySelectorAll('td[data-side="full"], td[data-sheet-action-cell]')];
    // Move the original controls together with their forms and registered listeners.
    const contents = groups.map(cell => ({ cell, nodes: [...cell.childNodes] }));
    for (const { nodes } of contents) actions.append(...nodes);
    sheet.addEventListener('close', () => {
        for (const { cell, nodes } of contents) cell.append(...nodes);
    }, { once: true });
    sheet.showModal();
});

document.addEventListener('click', event => {
    const sheet = event.target.closest('[data-record-dialog]');
    if (sheet && (event.target === sheet || event.target.closest('[data-sheet-close], [data-sheet-actions] :is(button, a)'))) {
        sheet.close();
    }
}, true);

window.matchMedia('(min-width: 40rem)').addEventListener('change', event => {
    if (event.matches) document.querySelectorAll('[data-record-dialog][open]').forEach(sheet => sheet.close());
});
