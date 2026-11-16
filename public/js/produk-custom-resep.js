(function () {
    window.initProdukCustomResep = function (root) {
        const form = root.querySelector('#formResepCustom');
        if (!form || form.dataset.resepReady === '1' || typeof Autocomplete !== 'function') return;
        form.dataset.resepReady = '1';

        const jenis = form.querySelector('#jenisResep');
        const perOrder = form.querySelector('#perOrderResep');
        const jumlah = form.querySelector('#jumlahResep');
        const jumlahWrap = form.querySelector('#jumlahResepWrap');
        const autocomplete = form.querySelector('#autocompleteKomponenResep');
        const input = form.querySelector('#komponenResep');
        const modelId = form.querySelector('#komponenModelId');

        new Autocomplete(autocomplete, {
            search: function (text) {
                if (!text.trim()) return Promise.resolve([]);
                const url = new URL(autocomplete.dataset.searchUrl, window.location.href);
                url.searchParams.set('q', text);
                return fetch(url, { credentials: 'same-origin' })
                    .then(response => response.ok ? response.json() : [])
                    .catch(() => []);
            },
            getResultValue: result => result.label,
            onSubmit: function (result) {
                modelId.value = result.id;
                input.value = result.label;
                input.setCustomValidity('');
            },
        });

        // Jangan mengirim ID lama bila label hasil pilihan diedit manual.
        input.addEventListener('input', function () {
            modelId.value = '';
            input.setCustomValidity('');
        });

        function tampilkanKolom() {
            const internal = jenis.value === 'internal';
            form.querySelectorAll('[data-resep-jenis]').forEach(section => {
                const active = section.dataset.resepJenis === jenis.value;
                section.classList.toggle('d-none', !active);
                section.querySelectorAll('input, select').forEach(field => field.disabled = !active);
            });
            input.required = !internal;
            form.querySelector('#biayaResep').required = internal;
            const sekaliPerOrder = internal && perOrder.checked;
            jumlahWrap.classList.toggle('d-none', sekaliPerOrder);
            if (sekaliPerOrder) jumlah.value = 1;
        }

        jenis.addEventListener('change', tampilkanKolom);
        perOrder.addEventListener('change', tampilkanKolom);
        form.addEventListener('submit', function (event) {
            if (jenis.value === 'bahan' && !modelId.value) {
                event.preventDefault();
                input.setCustomValidity('Pilih komponen dari hasil pencarian.');
                input.reportValidity();
            }
        });
        tampilkanKolom();
    };
})();
