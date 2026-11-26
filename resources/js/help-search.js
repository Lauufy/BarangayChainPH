function initializeHelpSearch() {
    const search = document.getElementById('help-search');
    if (!search) return;
    const input = search.querySelector('input');
    const categories = document.getElementById('help-categories');
    const popular = document.getElementById('help-popular');
    const results = document.getElementById('help-results');
    const heading = results.querySelector('h2');
    const articles = Array.from(results.querySelectorAll('[data-category] details'), card => ({
        card,
        text: (card.closest('[data-category]').dataset.category + ' ' + card.textContent).toLowerCase(),
    }));

    function filterArticles() {
        const query = input.value.trim().toLowerCase();
        categories.hidden = popular.hidden = Boolean(query);
        results.hidden = !query;
        if (!query) return;

        const words = query.split(/\s+/);
        let count = 0;
        for (const { card, text } of articles) {
            card.hidden = !words.every(word => text.includes(word));
            card.open = false;
            if (!card.hidden) count++;
        }

        heading.textContent = count
            ? `Search results (${count})`
            : 'No matching articles. Try another search or open a ticket below.';
    }

    input.addEventListener('input', filterArticles);
    for (const link of document.querySelectorAll('[data-help-topic]')) {
        link.addEventListener('click', event => {
            if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            input.value = link.dataset.helpTopic;
            input.focus();
            filterArticles();
        });
    }

    filterArticles();
}

initializeHelpSearch();
document.addEventListener('citizen:content-swapped', initializeHelpSearch);
