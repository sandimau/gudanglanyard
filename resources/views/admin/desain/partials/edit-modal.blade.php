@php
    $editFailedValues = old('_desain_edit_popup') ? [
        'desainId' => old('desain_id'),
        'namaKonsumen' => old('nama_konsumen'),
        'produkId' => old('produk_id'),
        'produkLabel' => old('produk_label'),
        'tema' => old('tema'),
        'tipe' => old('tipe'),
        'keterangan' => old('keterangan'),
    ] : null;

    if ($editFailedValues && $editFailedValues['produkId']) {
        $produkEdit = \App\Models\Produk::with('produkModel.kategori')->find($editFailedValues['produkId']);
        $editFailedValues['produkLabel'] = $produkEdit
            ? collect([
                $produkEdit->produkModel?->kategori?->nama,
                $produkEdit->produkModel?->nama,
                $produkEdit->nama,
            ])->filter()->implode(' - ')
            : '';
    }
@endphp

<div class="modal fade" id="editDesainModal" tabindex="-1" aria-labelledby="editDesainModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDesainModalLabel">Edit Desain</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form id="editDesainForm" method="POST" action="">
                @csrf @method('PATCH')
                <input type="hidden" name="asal" value="{{ $modalAsal ?? 'daftar' }}">
                <input type="hidden" name="daftar_q" value="{{ $modalQ ?? '' }}">
                <input type="hidden" name="daftar_tipe" value="{{ $modalTipe ?? '' }}">
                <input type="hidden" name="daftar_page" value="{{ $modalPage ?? '' }}">
                <input type="hidden" name="_desain_edit_popup" value="1">
                <input type="hidden" name="desain_id" id="edit-desain-id">
            </form>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="edit-desain-nama-konsumen">Nama Konsumen</label>
                        <input type="text" id="edit-desain-nama-konsumen" name="nama_konsumen" form="editDesainForm"
                            required maxlength="255"
                            class="form-control {{ $editFailedValues && $errors->has('nama_konsumen') ? 'is-invalid' : '' }}">
                        @if ($editFailedValues) @error('nama_konsumen') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="edit-desain-produk-input">Produk</label>
                        @include('admin.desain.partials.produk-autocomplete', [
                            'containerId' => 'edit-desain-produk',
                            'inputId' => 'edit-desain-produk-input',
                            'hiddenId' => 'edit-desain-produk-id',
                            'formId' => 'editDesainForm',
                            'valueId' => $editFailedValues['produkId'] ?? '',
                            'valueLabel' => $editFailedValues['produkLabel'] ?? '',
                            'invalid' => $editFailedValues && $errors->has('produk_id'),
                        ])
                        @if ($editFailedValues) @error('produk_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="col-12">
                        <label class="form-label d-block">Tipe Desain</label>
                        <div class="form-check form-check-inline">
                            <input type="radio" id="edit-desain-tipe-offline" name="tipe" value="offline" form="editDesainForm"
                                required class="form-check-input {{ $editFailedValues && $errors->has('tipe') ? 'is-invalid' : '' }}">
                            <label class="form-check-label" for="edit-desain-tipe-offline">Offline</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input type="radio" id="edit-desain-tipe-online" name="tipe" value="online" form="editDesainForm"
                                required class="form-check-input {{ $editFailedValues && $errors->has('tipe') ? 'is-invalid' : '' }}">
                            <label class="form-check-label" for="edit-desain-tipe-online">Online</label>
                        </div>
                        @if ($editFailedValues) @error('tipe') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="edit-desain-tema">Tema</label>
                        <input type="text" id="edit-desain-tema" name="tema" form="editDesainForm" maxlength="255"
                            class="form-control {{ $editFailedValues && $errors->has('tema') ? 'is-invalid' : '' }}">
                        @if ($editFailedValues) @error('tema') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="edit-desain-keterangan">Keterangan</label>
                        <textarea id="edit-desain-keterangan" name="keterangan" form="editDesainForm" rows="4"
                            class="form-control {{ $editFailedValues && $errors->has('keterangan') ? 'is-invalid' : '' }}"></textarea>
                        @if ($editFailedValues) @error('keterangan') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <form id="hapusDesainForm" method="POST" action="" onsubmit="return confirm('Hapus desain ini?')">
                    @csrf @method('DELETE')
                    <input type="hidden" name="asal" value="{{ $modalAsal ?? 'daftar' }}">
                    <input type="hidden" name="daftar_q" value="{{ $modalQ ?? '' }}">
                    <input type="hidden" name="daftar_tipe" value="{{ $modalTipe ?? '' }}">
                    <input type="hidden" name="daftar_page" value="{{ $modalPage ?? '' }}">
                    <button type="submit" class="btn btn-outline-danger">Hapus</button>
                </form>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" form="editDesainForm" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('after-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('editDesainModal');
            const form = document.getElementById('editDesainForm');
            const hapusForm = document.getElementById('hapusDesainForm');
            const gagalEdit = @json($editFailedValues);
            const kolom = {
                namaKonsumen: document.getElementById('edit-desain-nama-konsumen'),
                tema: document.getElementById('edit-desain-tema'),
                keterangan: document.getElementById('edit-desain-keterangan'),
            };
            const produkInput = document.getElementById('edit-desain-produk-input');
            const produkHidden = document.getElementById('edit-desain-produk-id');
            const produkClear = document.getElementById('edit-desain-produk-input-clear');
            const tipeRadios = modal.querySelectorAll('input[name="tipe"]');

            modal.addEventListener('show.bs.modal', function (event) {
                const tombol = event.relatedTarget;
                if (!tombol?.dataset.desainId) {
                    return;
                }

                form.action = tombol.dataset.action;
                hapusForm.action = tombol.dataset.deleteAction;
                document.getElementById('edit-desain-id').value = tombol.dataset.desainId;

                const data = gagalEdit && String(gagalEdit.desainId) === tombol.dataset.desainId
                    ? gagalEdit
                    : tombol.dataset;

                kolom.namaKonsumen.value = data.namaKonsumen ?? '';
                kolom.tema.value = data.tema ?? '';
                kolom.keterangan.value = data.keterangan ?? '';
                tipeRadios.forEach(function (radio) {
                    radio.checked = radio.value === (data.tipe ?? '');
                });
                produkInput.value = data.produkLabel ?? '';
                produkHidden.value = data.produkId ?? '';
                if (produkClear) {
                    produkClear.style.display = data.produkId ? 'block' : 'none';
                }

                const tampilkanError = data === gagalEdit;
                modal.querySelectorAll('.is-invalid').forEach(function (input) {
                    input.classList.toggle('is-invalid', tampilkanError);
                });
                modal.querySelectorAll('.invalid-feedback').forEach(function (pesan) {
                    pesan.style.display = tampilkanError ? '' : 'none';
                });
            });

            if (gagalEdit && @json($errors->any())) {
                const tombol = Array.from(document.querySelectorAll('[data-bs-target="#editDesainModal"]'))
                    .find(function (item) { return item.dataset.desainId === String(gagalEdit.desainId); });
                if (tombol) {
                    bootstrap.Modal.getOrCreateInstance(modal).show(tombol);
                }
            }
        });
    </script>
@endpush
