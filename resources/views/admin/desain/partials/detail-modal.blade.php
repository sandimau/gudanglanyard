<div class="modal fade" id="detailDesainModal" tabindex="-1" aria-labelledby="detailDesainModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailDesainModalLabel">Detail Desain</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body" id="detailDesainContent"></div>
        </div>
    </div>
</div>

@push('after-scripts')
    @include('admin.desain.partials.copy-link-script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('detailDesainModal');
            const content = document.getElementById('detailDesainContent');
            const failedId = @json(old('_desain_edit_popup') && $errors->any() ? old('desain_id') : null);
            let requestId = 0;
            let detailUrl = '';

            async function loadDetail(url, openEdit = false) {
                const currentRequest = ++requestId;
                detailUrl = url;
                content.innerHTML = '<p class="text-muted mb-0">Memuat detail desain...</p>';
                try {
                    const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
                    if (!response.ok) throw new Error('Gagal memuat detail desain.');
                    const html = await response.text();
                    if (currentRequest !== requestId) return;
                    content.innerHTML = html;
                    if (openEdit) content.querySelector('[data-desain-edit]')?.click();
                } catch (error) {
                    if (currentRequest === requestId) {
                        content.innerHTML = '<p class="text-danger mb-0">Gagal memuat detail desain. Tutup dan buka kembali untuk mencoba lagi.</p>';
                    }
                }
            }

            modal.addEventListener('show.bs.modal', function (event) {
                const link = event.relatedTarget;
                if (link?.dataset.detailUrl) loadDetail(link.dataset.detailUrl, String(failedId) === link.dataset.detailUrl.split('/').pop());
            });
            modal.addEventListener('hidden.bs.modal', function () { requestId++; });

            content.addEventListener('click', function (event) {
                const edit = event.target.closest('[data-desain-edit]');
                if (!edit) return;
                event.preventDefault();
                event.stopPropagation();
                modal.addEventListener('hidden.bs.modal', function () {
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('editDesainModal')).show(edit);
                }, { once: true });
                bootstrap.Modal.getOrCreateInstance(modal).hide();
            });

            content.addEventListener('submit', async function (event) {
                const form = event.target.closest('[data-desain-chat-form]');
                if (!form) return;
                event.preventDefault();
                const button = form.querySelector('button[type="submit"]');
                const error = form.querySelector('[data-desain-chat-error]');
                button.disabled = true;
                error.hidden = true;
                try {
                    const response = await fetch(form.action, {
                        method: 'POST', body: new FormData(form), credentials: 'same-origin',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    });
                    if (!response.ok) throw new Error(response.status === 422 ? 'Catatan wajib diisi (maksimal 5000 karakter).' : 'Gagal mengirim catatan.');
                    await loadDetail(detailUrl);
                } catch (failure) {
                    error.textContent = failure.message;
                    error.hidden = false;
                } finally {
                    if (button.isConnected) button.disabled = false;
                }
            });

            if (failedId) {
                const link = Array.from(document.querySelectorAll('[data-detail-url]'))
                    .find(function (item) { return item.dataset.detailUrl.split('/').pop() === String(failedId); });
                if (link) bootstrap.Modal.getOrCreateInstance(modal).show(link);
            }
        });
    </script>
@endpush
