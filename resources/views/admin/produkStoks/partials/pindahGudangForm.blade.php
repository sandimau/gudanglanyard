<div class="modal fade" id="modalPindahGudang" tabindex="-1" aria-labelledby="modalPindahGudangLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="modalPindahGudangLabel">Pindah Gudang</h5>
                    <small class="text-muted">Isi maksimal 10 produk dalam satu transaksi.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <form id="formPindahGudang" method="POST" action="{{ route('pindahGudang.store') }}">
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
                        <div class="col-md-6">
                            <label class="form-label" for="gudang_asal_id">Gudang asal</label>
                            <select class="form-select @error('gudang_asal_id') is-invalid @enderror" id="gudang_asal_id" name="gudang_asal_id" required>
                                <option value="">Pilih gudang asal</option>
                                @foreach ($cabangs as $cabang)
                                    <option value="{{ $cabang->id }}" @selected(old('gudang_asal_id', cabang_id() ?? resolve_cabang_id()) == $cabang->id)>
                                        {{ $cabang->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="gudang_tujuan_id">Gudang tujuan</label>
                            <select class="form-select @error('gudang_tujuan_id') is-invalid @enderror" id="gudang_tujuan_id" name="gudang_tujuan_id" required>
                                <option value="">Pilih gudang tujuan</option>
                                @foreach ($cabangs as $cabang)
                                    <option value="{{ $cabang->id }}" @selected(old('gudang_tujuan_id') == $cabang->id)>
                                        {{ $cabang->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-2 fw-semibold">Daftar produk</div>
                    <div class="border rounded mb-3">
                        @for ($index = 0; $index < 10; $index++)
                            @php
                                $produkId = old("items.$index.produk_id");
                                $produkLabel = $produkLabels->get($produkId, '');
                            @endphp
                            <div class="row g-2 align-items-center p-3 {{ $index < 9 ? 'border-bottom' : '' }}">
                                <div class="col-12 col-md-auto fw-semibold text-muted" style="min-width: 2.5rem">{{ $index + 1 }}.</div>
                                <div class="col-12 col-md-8">
                                    <label class="form-label visually-hidden" for="produk-search-{{ $index }}">Produk {{ $index + 1 }}</label>
                                    <div class="pindah-produk autocomplete">
                                        <input id="produk-search-{{ $index }}" type="text"
                                            class="autocomplete-input form-control @error("items.$index.produk_id") is-invalid @enderror"
                                            placeholder="Cari produk atau varian" autocomplete="off"
                                            value="{{ $produkLabel }}">
                                        <ul class="autocomplete-result-list"></ul>
                                        <input type="hidden" class="pindah-produk-id" name="items[{{ $index }}][produk_id]" value="{{ $produkId }}">
                                    </div>
                                </div>
                                <div class="col-12 col-md">
                                    <label class="form-label visually-hidden" for="jumlah-{{ $index }}">Jumlah produk {{ $index + 1 }}</label>
                                    <input id="jumlah-{{ $index }}" class="form-control @error("items.$index.jumlah") is-invalid @enderror"
                                        type="number" min="1" step="1" name="items[{{ $index }}][jumlah]"
                                        value="{{ old("items.$index.jumlah") }}" placeholder="Jumlah">
                                </div>
                            </div>
                        @endfor
                    </div>

                    <div>
                        <label class="form-label" for="keterangan">Keterangan (opsional)</label>
                        <input class="form-control @error('keterangan') is-invalid @enderror" type="text"
                            id="keterangan" name="keterangan" maxlength="150" value="{{ old('keterangan') }}"
                            placeholder="Contoh: pengisian stok cabang">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" form="formPindahGudang" class="btn btn-primary">Simpan Pindah Gudang</button>
            </div>
        </div>
    </div>
</div>
