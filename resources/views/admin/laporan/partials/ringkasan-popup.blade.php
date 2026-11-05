<div class="modal fade" id="ringkasanModal" tabindex="-1" aria-labelledby="ringkasanModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ringkasanModalTitle">Rincian data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body" id="ringkasanModalBody"></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div>
        </div>
    </div>
</div>
@push('after-scripts')
    <script>
        (function () {
            const modal = document.getElementById('ringkasanModal');
            const body = document.getElementById('ringkasanModalBody');
            let controller;
            document.addEventListener('click', async function (event) {
                const trigger = event.target.closest('[data-ringkasan-url]');
                if (!trigger) return;
                event.preventDefault();
                if (controller) controller.abort();
                controller = new AbortController();
                body.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Memuat rincian…</p></div>';
                bootstrap.Modal.getOrCreateInstance(modal).show();
                try {
                    const response = await fetch(trigger.dataset.ringkasanUrl, { signal: controller.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!response.ok) throw new Error('Rincian tidak dapat dimuat (' + response.status + ').');
                    body.innerHTML = await response.text();
                } catch (error) {
                    if (error.name !== 'AbortError') body.textContent = error.message;
                }
            });
            modal.addEventListener('hidden.bs.modal', function () { if (controller) controller.abort(); });
        })();
    </script>
@endpush
