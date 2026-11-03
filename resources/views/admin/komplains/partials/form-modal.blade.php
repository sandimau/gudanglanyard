<div class="modal fade" id="komplainModal" tabindex="-1" aria-labelledby="komplainModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="komplainForm" action="{{ route('komplain.store') }}" method="post">
                {{ csrf_field() }}
                <input type="hidden" name="komplain_target" id="komplainTarget">
                <input type="hidden" name="detail_id" id="komplainDetailId">
                <input type="hidden" name="tipe" id="komplainTipe">

                <div class="modal-header">
                    <h5 class="modal-title" id="komplainModalLabel">Form Komplain</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Produk</label>
                        <input type="text" id="komplainProduk" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bagian yang salah <span class="text-danger">*</span></label>
                        <input type="text" name="bagian_salah" class="form-control" maxlength="255" required
                            placeholder="Contoh: sablon nama salah, tali putus, warna tidak sesuai">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jumlah produk <span class="text-danger">*</span></label>
                        <input type="number" name="jumlah" id="komplainJumlah" class="form-control" min="1"
                            required>
                        <div class="form-text" id="komplainJumlahInfo">
                            Jumlah mengikuti detail order yang dipilih.
                        </div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Isi komplain <span class="text-danger">*</span></label>
                        <textarea name="keterangan" class="form-control" rows="4" maxlength="2000" required
                            placeholder="Jelaskan komplain dari konsumen"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Kirim Komplain</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('after-scripts')
    <script>
        (function() {
            var modalEl = document.getElementById('komplainModal');
            if (!modalEl) {
                return;
            }

            // Detail pages can also be rendered inside the AJAX detail modal.
            // Keep only the newest complaint modal and move it to <body> so it
            // is not hidden when the surrounding detail modal is toggled.
            document.querySelectorAll('#komplainModal').forEach(function(existing) {
                if (existing !== modalEl) {
                    existing.remove();
                }
            });

            if (modalEl.parentNode !== document.body) {
                document.body.appendChild(modalEl);
            }

            modalEl.addEventListener('hidden.bs.modal', function() {
                // Bootstrap removes modal-open when the inner modal closes;
                // keep it while the surrounding detail modal is still open.
                if (document.querySelector('.modal.show')) {
                    document.body.classList.add('modal-open');
                }
            });

            // A single delegated handler is enough, including after the detail
            // content is replaced by the AJAX modal navigation.
            if (window.komplainModalHandlerAttached) {
                return;
            }
            window.komplainModalHandlerAttached = true;

            document.addEventListener('click', function(event) {
                var link = event.target.closest('.komplain-link');
                if (!link) {
                    return;
                }

                var currentModalEl = document.getElementById('komplainModal');
                if (!currentModalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
                    // Let the link fallback navigate to the complaint list.
                    return;
                }

                event.preventDefault();

                var form = document.getElementById('komplainForm');
                var targetInput = document.getElementById('komplainTarget');
                var detailInput = document.getElementById('komplainDetailId');
                var tipeInput = document.getElementById('komplainTipe');
                var produkInput = document.getElementById('komplainProduk');
                var jumlahInput = document.getElementById('komplainJumlah');
                var jumlahInfo = document.getElementById('komplainJumlahInfo');

                var max = parseInt(link.getAttribute('data-jumlah') || '1', 10);
                if (!max || max < 1) {
                    max = 1;
                }

                if (form) {
                    form.reset();
                }
                targetInput.value = link.getAttribute('data-komplain-target') || '';
                detailInput.value = link.getAttribute('data-komplain-id') || '';
                tipeInput.value = link.getAttribute('data-komplain-tipe') || 'offline';
                produkInput.value = link.getAttribute('data-produk') || '-';
                jumlahInput.value = max;
                jumlahInput.max = max;
                jumlahInfo.textContent = 'Jumlah maksimal sesuai detail order: ' + max + ' pcs';

                currentModalEl.style.zIndex = '1065';
                bootstrap.Modal.getOrCreateInstance(currentModalEl).show(link);

                // Place the inner backdrop above the surrounding detail modal.
                var backdrops = document.querySelectorAll('.modal-backdrop');
                if (backdrops.length) {
                    backdrops[backdrops.length - 1].style.zIndex = '1060';
                }
            });
        })();
    </script>
@endpush
