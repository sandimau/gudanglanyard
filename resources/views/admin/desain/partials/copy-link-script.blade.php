<script>
    document.addEventListener('click', async function (event) {
        const button = event.target.closest('[data-desain-copy-link]');
        if (!button) return;

        const link = button.dataset.desainCopyLink;
        if (!link) return;

        function copyFallback() {
            const input = document.createElement('textarea');
            input.value = link;
            input.style.position = 'fixed';
            input.style.opacity = '0';
            document.body.appendChild(input);
            input.select();
            const copied = document.execCommand('copy');
            input.remove();
            if (!copied) throw new Error('Tidak bisa menyalin link.');
        }

        try {
            if (navigator.clipboard?.writeText) {
                try {
                    await navigator.clipboard.writeText(link);
                } catch (error) {
                    copyFallback();
                }
            } else {
                copyFallback();
            }

            const original = button.innerHTML;
            button.innerHTML = '<i class="bx bx-check" aria-hidden="true"></i> Tersalin';
            setTimeout(function () { if (button.isConnected) button.innerHTML = original; }, 1500);
        } catch (error) {
            const original = button.innerHTML;
            button.textContent = 'Gagal menyalin';
            setTimeout(function () { if (button.isConnected) button.innerHTML = original; }, 2000);
        }
    });
</script>
