<!-- Modal Detail Order -->
<link rel="stylesheet" href="{{ asset('js/autocomplete.css') }}">
<div class="modal fade" id="detailOrderModal" tabindex="-1" aria-labelledby="detailOrderModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-fullscreen-lg-down modal-dialog-scrollable modal-dialog-centered modal-xxl">
        <div class="modal-content">
            <div class="modal-header align-items-center">
                <h5 class="modal-title flex-grow-1 mb-0" id="detailOrderModalLabel">Detail Order</h5>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <button type="button" class="btn btn-warning btn-sm text-white" id="detailOrderModalBack"
                        style="display: none;" aria-label="Kembali">
                        <i class='bx bx-arrow-back'></i> back
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body" id="detailOrderBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div id="detailOrderImagePreview" class="detail-order-image-preview d-none" aria-hidden="true">
                <div class="detail-order-image-preview-backdrop" data-image-preview-close></div>
                <div class="detail-order-image-preview-panel">
                    <div class="detail-order-image-preview-body text-center">
                        <img class="img-fluid detail-order-image-preview-img" src="" alt="Gambar order">
                    </div>
                    <div class="detail-order-image-preview-footer d-flex justify-content-center gap-2">
                        <a href="#" class="btn btn-primary detail-order-image-preview-edit d-none">Edit Gambar</a>
                        <button type="button" class="btn btn-secondary" data-image-preview-close>Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('js/autocomplete.min.js') }}"></script>
@include('admin.partials.image-zoom-preview')
