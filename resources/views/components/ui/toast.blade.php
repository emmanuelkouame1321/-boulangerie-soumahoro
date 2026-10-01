@once
    <div
        id="app-toast"
        class="fixed right-5 bottom-5 z-[100] hidden items-center gap-2.5 rounded-control bg-ink px-4 py-3 text-sm text-white shadow-elevated"
        role="status"
        aria-live="polite"
    >
        <x-ui.icon name="check-circle" :size="16" class="text-brand-300" />
        <span id="app-toast-message"></span>
    </div>

    <script>
        window.toast = function (message) {
            const toast = document.getElementById('app-toast');
            if (!toast) return;

            document.getElementById('app-toast-message').textContent = message;
            toast.classList.remove('hidden');
            toast.classList.add('flex');

            clearTimeout(window.__appToastTimer);
            window.__appToastTimer = setTimeout(() => {
                toast.classList.add('hidden');
                toast.classList.remove('flex');
            }, 2400);
        };
    </script>
@endonce
