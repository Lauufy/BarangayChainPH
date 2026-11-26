document.addEventListener('change', event => {
    const input = event.target;
    if (!input.matches('[data-ticket-composer] input[type=file]')) return;
    const selection = input.form.querySelector('[data-selection]');
    if (!selection) return;
    selection.textContent = input.files[0] ? input.files[0].name + ' · ' : '';
});

document.addEventListener('keydown', event => {
    if (event.key === 'Enter' && !event.shiftKey && !event.isComposing && event.target.matches('[data-ticket-composer]:not([data-help-request]) textarea')) {
        event.preventDefault();
        event.target.form.requestSubmit();
    }
});

document.addEventListener('submit', async event => {
    const form = event.target;
    if (!form.matches('[data-ticket-composer]')) return;
    event.preventDefault();
    if (form.dataset.sending) return;

    const body = form.elements.body;
    const attachment = form.elements.attachment;
    const file = attachment?.files[0];
    const bubble = form.querySelector('[data-outgoing]');
    const status = form.querySelector('[data-status]');
    const send = form.querySelector('button[type=submit]');
    if (!body.value.trim() && !file) {
        body.focus();
        body.setCustomValidity('Write a message or attach a file.');
        body.reportValidity();
        body.setCustomValidity('');
        return;
    }

    const payload = new FormData(form);
    if (bubble) {
        const image = bubble.querySelector('img');
        if (image.src.startsWith('blob:')) URL.revokeObjectURL(image.src);
        image.removeAttribute('src');
        image.hidden = !file || !['image/jpeg', 'image/png', 'image/webp'].includes(file.type);
        if (!image.hidden) image.src = URL.createObjectURL(file);
        bubble.querySelector('[data-message]').textContent = body.value;
        bubble.querySelector('[data-file]').textContent = file && image.hidden ? file.name : '';
        bubble.hidden = false;
    }
    status.textContent = 'Sending…';
    form.dataset.sending = 'true';
    send.disabled = body.readOnly = true;
    if (attachment) attachment.disabled = true;
    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: { Accept: 'application/json' },
            body: payload,
        });
        if (response.status === 429) {
            const seconds = Number(response.headers.get('Retry-After'));
            throw new Error(seconds > 0
                ? `Please wait ${seconds} ${seconds === 1 ? 'second' : 'seconds'} before sending again. Your draft is still here.`
                : 'Please wait a moment before sending again. Your draft is still here.');
        }
        const result = await response.json();
        if (!response.ok) {
            const message = Object.values(result.errors ?? {}).flat().join(' ');
            throw new Error(message || (response.status < 500 ? result.message : ''));
        }
        if (!result.url) throw new Error();
        status.textContent = 'Sent';
        window.location.assign(result.url);
    } catch (error) {
        status.textContent = error instanceof Error && error.message && !(error instanceof TypeError || error instanceof SyntaxError)
            ? error.message
            : 'Delivery unconfirmed. Check your tickets before sending again.';
        delete form.dataset.sending;
        send.disabled = body.readOnly = false;
        if (attachment) attachment.disabled = false;
    }
});
