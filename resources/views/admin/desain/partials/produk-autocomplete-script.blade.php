@push('after-scripts')
    <script src="{{ asset('js/autocomplete.min.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('js/autocomplete.css') }}">
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-desain-produk]').forEach(function (container) {
                if (container.dataset.ready === '1') {
                    return;
                }
                container.dataset.ready = '1';

                const input = container.querySelector('.autocomplete-input');
                const hidden = container.querySelector('input[name="produk_id"]');
                const clear = container.querySelector('.desain-produk__clear');

                new Autocomplete('#' + container.id, {
                    search: function (text) {
                        if (text.length < 1) {
                            return Promise.resolve([]);
                        }

                        const url = "{{ url('admin/produk/api') }}?q=" + encodeURIComponent(text)
                            + "&stok_tersedia=1"
                            + "&produksi_tanpa_stok=1";

                        return fetch(url).then(function (response) {
                            return response.json();
                        });
                    },
                    getResultValue: function (result) {
                        return result.varian
                            ? result.kategori + ' - ' + result.nama + ' - ' + result.varian
                            : result.kategori + ' - ' + result.nama;
                    },
                    onSubmit: function (result) {
                        hidden.value = result.id;
                        if (clear) {
                            clear.style.display = 'block';
                        }
                    }
                });

                input.addEventListener('input', function () {
                    hidden.value = '';
                    if (clear) {
                        clear.style.display = 'none';
                    }
                });

                if (clear) {
                    clear.addEventListener('click', function () {
                        input.value = '';
                        hidden.value = '';
                        clear.style.display = 'none';
                        input.focus();
                    });
                }
            });
        });
    </script>
    <style>
        .desain-produk {
            position: relative;
            max-width: 600px;
        }

        .desain-produk__input {
            width: 100% !important;
        }

        .desain-produk__clear {
            position: absolute;
            right: .35rem;
            top: 50%;
            transform: translateY(-50%);
        }

        .desain-produk__clear .btnClose {
            padding: 4px 8px;
            border: 0;
            border-radius: 50px;
            background: #fdc54c;
        }
    </style>
@endpush
