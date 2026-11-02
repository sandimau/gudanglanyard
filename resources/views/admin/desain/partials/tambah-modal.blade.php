@php
    $gagalTambah = old('_desain_popup');
    $produkLama = $gagalTambah && old('produk_id')
        ? \App\Models\Produk::with('produkModel.kategori')->find(old('produk_id'))
        : null;
    $labelProdukLama = $produkLama
        ? collect([
            $produkLama->produkModel?->kategori?->nama,
            $produkLama->produkModel?->nama,
            $produkLama->nama,
        ])->filter()->implode(' - ')
        : '';
@endphp

<div class="modal fade" id="tambahDesainModal" tabindex="-1" aria-labelledby="tambahDesainModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('desain.store') }}">
                @csrf
                <input type="hidden" name="asal" value="{{ $modalAsal ?? 'daftar' }}">
                <input type="hidden" name="daftar_q" value="{{ $modalQ ?? '' }}">
                <input type="hidden" name="daftar_tipe" value="{{ $modalTipe ?? '' }}">
                <input type="hidden" name="daftar_page" value="{{ $modalPage ?? '' }}">
                <input type="hidden" name="_desain_popup" value="1">
                <div class="modal-header">
                    <h5 class="modal-title" id="tambahDesainModalLabel">Tambah Desain</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="popup-nama-konsumen">Nama Konsumen</label>
                            <input type="text" id="popup-nama-konsumen" name="nama_konsumen" required maxlength="255"
                                class="form-control {{ $gagalTambah && $errors->has('nama_konsumen') ? 'is-invalid' : '' }}"
                                value="{{ $gagalTambah ? old('nama_konsumen') : '' }}">
                            @if ($gagalTambah) @error('nama_konsumen') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="popup-desain-produk-input">Produk</label>
                            @include('admin.desain.partials.produk-autocomplete', [
                                'containerId' => 'popup-desain-produk',
                                'inputId' => 'popup-desain-produk-input',
                                'hiddenId' => 'popup-desain-produk-id',
                                'valueId' => $gagalTambah ? old('produk_id') : '',
                                'valueLabel' => $labelProdukLama,
                                'invalid' => $gagalTambah && $errors->has('produk_id'),
                            ])
                            @if ($gagalTambah) @error('produk_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror @endif
                        </div>
                        <div class="col-12">
                            <label class="form-label d-block">Tipe Desain</label>
                            <div class="form-check form-check-inline">
                                <input type="radio" id="popup-tipe-offline" name="tipe" value="offline" required
                                    class="form-check-input {{ $gagalTambah && $errors->has('tipe') ? 'is-invalid' : '' }}"
                                    {{ $gagalTambah && old('tipe') === 'offline' ? 'checked' : '' }}>
                                <label class="form-check-label" for="popup-tipe-offline">Offline</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input type="radio" id="popup-tipe-online" name="tipe" value="online" required
                                    class="form-check-input {{ $gagalTambah && $errors->has('tipe') ? 'is-invalid' : '' }}"
                                    {{ $gagalTambah && old('tipe') === 'online' ? 'checked' : '' }}>
                                <label class="form-check-label" for="popup-tipe-online">Online</label>
                            </div>
                            @if ($gagalTambah) @error('tipe') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror @endif
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="popup-tema">Tema</label>
                            <input type="text" id="popup-tema" name="tema" maxlength="255"
                                class="form-control {{ $gagalTambah && $errors->has('tema') ? 'is-invalid' : '' }}"
                                value="{{ $gagalTambah ? old('tema') : '' }}">
                            @if ($gagalTambah) @error('tema') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="popup-keterangan">Keterangan</label>
                            <textarea id="popup-keterangan" name="keterangan" rows="4"
                                class="form-control {{ $gagalTambah && $errors->has('keterangan') ? 'is-invalid' : '' }}">{{ $gagalTambah ? old('keterangan') : '' }}</textarea>
                            @if ($gagalTambah) @error('keterangan') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Desain</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('after-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('tambahDesainModal');

            @if ($gagalTambah)
                bootstrap.Modal.getOrCreateInstance(modal).show();
            @endif
        });
    </script>
@endpush
