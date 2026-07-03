@media (min-width: 992px) {
    #detailProjectMpModal .modal-dialog.modal-xxl {
        max-width: 98%;
        width: 98%;
        margin-left: auto;
        margin-right: auto;
    }

    #detailProjectMpModal .modal-dialog.modal-xxl.modal-dialog-scrollable {
        height: calc(100% - 2rem);
    }
}

a.popup {
    text-decoration: none;
}

a.popup:hover {
    text-decoration: underline;
}

#detailProjectMpModalBack {
    flex-shrink: 0;
}

#detailProjectMpModal .modal-header .btn-close {
    margin: 0;
}

#detailProjectMpModal .card > .card-header:has(.card-title) {
    display: none !important;
}

#detailProjectMpModal .breadcrumb {
    display: none;
}

#detailProjectMpImagePreview {
    position: absolute;
    inset: 0;
    z-index: 20;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

#detailProjectMpImagePreview.d-none {
    display: none !important;
}

#detailProjectMpModal .modal-content {
    position: relative;
}

.detail-projectmp-image-preview-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.65);
}

.detail-projectmp-image-preview-panel {
    position: relative;
    z-index: 1;
    width: min(100%, 900px);
    max-height: calc(100% - 2rem);
    background: #fff;
    border-radius: 0.5rem;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.detail-projectmp-image-preview-body {
    padding: 1rem;
    overflow: auto;
}

.detail-projectmp-image-preview-img {
    max-height: 70vh;
    width: auto;
}

.detail-projectmp-image-preview-footer {
    padding: 0.75rem 1rem 1rem;
    border-top: 1px solid #dee2e6;
}

.projectmp-detail-image-thumb {
    display: inline-block;
}

#detailProjectMpModal .order-detail-produk-autocomplete {
    max-width: 600px;
    position: relative;
}

#detailProjectMpModal .order-detail-autocomplete-field {
    position: relative;
}

#detailProjectMpModal .order-detail-autocomplete-clear {
    position: absolute;
    right: 6px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 4;
}

#detailProjectMpModal .order-detail-autocomplete-clear:empty {
    display: none;
}

#detailProjectMpModal .order-detail-produk-autocomplete .autocomplete-input {
    width: 100% !important;
    padding-right: 42px !important;
}
