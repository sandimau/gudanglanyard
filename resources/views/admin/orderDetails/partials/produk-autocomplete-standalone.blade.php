<script src="{{ asset('js/autocomplete.min.js') }}"></script>
<link rel="stylesheet" href="{{ asset('js/autocomplete.css') }}">
<script>
    (function() {
        const root = document.querySelector('.order-detail-produk-autocomplete');
        if (!root || root.closest('#detailOrderBody') || root.closest('#detailProjectMpBody')) return;

        const produkIdInput = root.querySelector('.order-detail-produk-id');
        const clearWrap = root.querySelector('.order-detail-autocomplete-clear');
        const produkInput = root.querySelector('.autocomplete-input');
        const hargaInput = root.closest('form')?.querySelector('[name="harga"]');

        function showClearBtn() {
            clearWrap.innerHTML =
                '<button type="button" class="btn btn-warning btn-sm text-white order-detail-clear-produk"><i class="bx bx-x-circle"></i></button>';
        }

        function clearProduk() {
            clearWrap.innerHTML = '';
            produkInput.value = '';
            produkIdInput.value = '';
        }

        clearWrap.addEventListener('click', function(e) {
            if (e.target.closest('.order-detail-clear-produk')) {
                clearProduk();
            }
        });

        new Autocomplete(root, {
            search: input => {
                const url = "{{ url('admin/produk/api?q=') }}" + encodeURIComponent(input);
                return new Promise(resolve => {
                    if (input.length < 1) return resolve([]);
                    fetch(url).then(r => r.json()).then(resolve).catch(() => resolve([]));
                });
            },
            getResultValue: result => result.varian ?
                result.kategori + ' - ' + result.nama + ' - ' + result.varian :
                result.kategori + ' - ' + result.nama,
            onSubmit: result => {
                produkIdInput.value = result.id;
                if (hargaInput && result.harga) hargaInput.value = result.harga;
                showClearBtn();
            },
        });
    })();
</script>
<style>
    .order-detail-produk-autocomplete {
        max-width: 600px;
        position: relative;
    }

    .order-detail-autocomplete-field {
        position: relative;
    }

    .order-detail-autocomplete-clear {
        position: absolute;
        right: 6px;
        top: 50%;
        transform: translateY(-50%);
        z-index: 4;
    }

    .order-detail-autocomplete-clear:empty {
        display: none;
    }

    .order-detail-produk-autocomplete .autocomplete-input {
        width: 100% !important;
        padding-right: 42px !important;
    }
</style>
