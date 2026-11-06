{{--
    Put ONCE inside <head> of every layout, before @vite, so the saved theme is applied before first paint:
        <x-partials.theme-init />
--}}
<script>
    (() => {
        try {
            const saved = localStorage.getItem('theme');
            if (saved === 'dark' || (!saved && matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        } catch (e) {}
    })();
</script>
<style>
    html.dark {
        color-scheme: dark;
    }
</style>