<script>
    /**
     * Polls a small version endpoint and calls refresh() only when the version changed.
     *
     * url               version endpoint (already scoped to admin or citizen)
     * version           version the server rendered this page with
     * refresh           async function that fetches and swaps the page content
     * hasUnsavedInput   function returning true while the user has unsent input
     */
    function startLiveSync({
        url,
        version,
        refresh,
        hasUnsavedInput,
        interval = 5000
    }) {
        let lastVersion = version;
        let running = false;

        const isEditing = () =>
            document.activeElement?.matches('input, textarea, select, [contenteditable="true"]');

        async function check() {
            if (document.hidden || running) return;
            running = true;

            try {
                const res = await fetch(url, {
                    cache: 'no-store',
                    headers: {
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                });

                // Session ended (e.g. signed out in another tab): go to the login page
                if (res.status === 401 || res.redirected) {
                    location.reload();
                    return;
                }
                if (!res.ok) return;

                const {
                    v
                } = await res.json();
                if (v === lastVersion) return; // nothing changed, skip the page fetch
                if (isEditing() || hasUnsavedInput()) return; // retry on the next tick

                await refresh();
                lastVersion = v;
            } catch (error) {
                console.error(error);
            } finally {
                running = false;
            }
        }

        setInterval(check, interval);

        // Catch up right away when the user returns to the tab
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) check();
        });
    }
</script>