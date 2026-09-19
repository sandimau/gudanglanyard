@media (min-width: 992px) {
    .modal-xxl {
        max-width: 96%;
    }

    .modal-xxl.modal-dialog-scrollable {
        height: calc(100% - 2rem);
    }
}

a.popup {
    text-decoration: none;
}

a.popup:hover {
    text-decoration: underline;
}

#detailOrderModalBack {
    flex-shrink: 0;
}

#detailOrderModal .modal-header .btn-close {
    margin: 0;
}

#detailOrderModal .card > .card-header:has(.card-title):not(.payment-history-header) {
    display: none !important;
}

#detailOrderModal .order-detail-produk-autocomplete {
    max-width: 600px;
    position: relative;
}

#detailOrderModal .order-detail-autocomplete-field {
    position: relative;
}

#detailOrderModal .order-detail-autocomplete-clear {
    position: absolute;
    right: 6px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 4;
    line-height: 1;
}

#detailOrderModal .order-detail-autocomplete-clear:empty {
    display: none;
}

#detailOrderModal .order-detail-produk-autocomplete .autocomplete-input {
    width: 100% !important;
    margin-right: 0 !important;
    padding-right: 42px !important;
    box-sizing: border-box;
}

#detailOrderModal .order-detail-produk-autocomplete .autocomplete-result-list {
    z-index: 1070;
}

#detailOrderModal .order-detail-produk-autocomplete[data-loading="true"]:after {
    right: 42px;
}

#detailOrderModal .order-detail-produk-autocomplete .autocomplete-input.is-invalid,
#detailOrderModal .order-detail-produk-autocomplete .autocomplete-input.invalid {
    border: solid 1px red;
}

#detailOrderModal input[type="hidden"][name="nota"] {
    display: none !important;
}

#detailOrderImagePreview {
    position: absolute;
    inset: 0;
    z-index: 20;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

#detailOrderImagePreview.d-none {
    display: none !important;
}

#detailOrderModal .modal-content {
    position: relative;
}

.detail-order-image-preview-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.65);
}

.detail-order-image-preview-panel {
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

.detail-order-image-preview-body {
    padding: 1rem;
    overflow: auto;
}

.detail-order-image-preview-img {
    max-height: 70vh;
    width: auto;
}

.detail-order-image-preview-footer {
    padding: 0.75rem 1rem 1rem;
    border-top: 1px solid #dee2e6;
}

.order-detail-image-thumb {
    display: inline-block;
}
