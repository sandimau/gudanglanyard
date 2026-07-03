<!-- Modal Detail Project Marketplace -->
<link rel="stylesheet" href="{{ asset('js/autocomplete.css') }}">
<div class="modal fade" id="detailProjectMpModal" tabindex="-1" aria-labelledby="detailProjectMpModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-fullscreen-lg-down modal-dialog-scrollable modal-dialog-centered modal-xxl">
        <div class="modal-content">
            <div class="modal-header align-items-center">
                <h5 class="modal-title flex-grow-1 mb-0" id="detailProjectMpModalLabel">Detail Project</h5>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <button type="button" class="btn btn-warning btn-sm text-white" id="detailProjectMpModalBack"
                        style="display: none;" aria-label="Kembali">
                        <i class='bx bx-arrow-back'></i> back
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body" id="detailProjectMpBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div id="detailProjectMpImagePreview" class="detail-projectmp-image-preview d-none" aria-hidden="true">
                <div class="detail-projectmp-image-preview-backdrop" data-image-preview-close></div>
                <div class="detail-projectmp-image-preview-panel">
                    <div class="detail-projectmp-image-preview-body text-center">
                        <img class="img-fluid detail-projectmp-image-preview-img" src="" alt="Gambar project">
                    </div>
                    <div class="detail-projectmp-image-preview-footer d-flex justify-content-center gap-2">
                        <a href="#" class="btn btn-primary detail-projectmp-image-preview-edit d-none">Edit Gambar</a>
                        <button type="button" class="btn btn-secondary" data-image-preview-close>Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('js/autocomplete.min.js') }}"></script>
