<div class="modal fade" id="modalPecahStok" tabindex="-1" aria-labelledby="modalPecahStokLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="modalPecahStokLabel">Pecah Stok</h5>
                    <small class="text-muted">Kurangi stok produk roll, lalu tambahkan hasilnya ke produk satuan di gudang yang sama.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <form id="formPecahStok" method="POST" action="{{ route('pecahStok.store') }}">
                    @csrf
                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label" for="gudang_id">Gudang</label>
                            <select class="form-select @error('gudang_id') is-invalid @enderror" id="gudang_id" name="gudang_id" required>
                                <option value="">Pilih gudang</option>
                                @foreach ($cabangs as $cabang)
                                    <option value="{{ $cabang->id }}" @selected(old('gudang_id', cabang_id() ?? resolve_cabang_id()) == $cabang->id)>
                                        {{ $cabang->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="jumlah_asal">Jumlah produk asal</label>
                            <input id="jumlah_asal" class="form-control @error('jumlah_asal') is-invalid @enderror"
                                type="number" min="1" step="1" name="jumlah_asal"
                                value="{{ old('jumlah_asal') }}" placeholder="Contoh: 1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="rasio">Hasil per 1 produk asal</label>
                            <input id="rasio" class="form-control @error('rasio') is-invalid @enderror"
                                type="number" min="1" step="1" name="rasio"
                                value="{{ old('rasio') }}" placeholder="Contoh: 100">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label" for="produk-search-asal">Produk asal</label>
                            @php
                                $asalId = old('produk_asal_id');
                                $asalLabel = $produkLabels->get($asalId, '');
                            @endphp
                            <div class="pecah-produk autocomplete">
                                <input id="produk-search-asal" type="text"
                                    class="autocomplete-input form-control @error('produk_asal_id') is-invalid @enderror"
                                    placeholder="Cari produk asal" autocomplete="off"
                                    value="{{ $asalLabel }}">
                                <ul class="autocomplete-result-list"></ul>
                                <input type="hidden" class="pecah-produk-id" name="produk_asal_id" value="{{ $asalId }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="produk-search-tujuan">Produk tujuan (satuan)</label>
                            @php
                                $tujuanId = old('produk_tujuan_id');
                                $tujuanLabel = $produkLabels->get($tujuanId, '');
                            @endphp
                            <div class="pecah-produk autocomplete">
                                <input id="produk-search-tujuan" type="text"
                                    class="autocomplete-input form-control @error('produk_tujuan_id') is-invalid @enderror"
                                    placeholder="Cari produk tujuan" autocomplete="off"
                                    value="{{ $tujuanLabel }}">
                                <ul class="autocomplete-result-list"></ul>
                                <input type="hidden" class="pecah-produk-id" name="produk_tujuan_id" value="{{ $tujuanId }}">
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info d-flex align-items-center gap-2" role="status">
                        <span>Hasil pecahan:</span>
                        <strong id="hasil_pecah">0</strong>
                        <span>satuan produk tujuan</span>
                    </div>

                    <div>
                        <label class="form-label" for="keterangan">Keterangan (opsional)</label>
                        <input class="form-control @error('keterangan') is-invalid @enderror" type="text"
                            id="keterangan" name="keterangan" maxlength="150" value="{{ old('keterangan') }}"
                            placeholder="Contoh: pecah roll untuk stok toko">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" form="formPecahStok" class="btn btn-primary">Simpan Pecah Stok</button>
            </div>
        </div>
    </div>
</div>
