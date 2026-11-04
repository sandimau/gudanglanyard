@if (auth()->user()->can('gagal_produksi_access'))
    @php
        $bolehInputGagal = auth()->user()->can('gagal_produksi_manage') && ($canEditCabang ?? false);
    @endphp
    <div id="gagal-produksi" class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white pt-3">
            <h6 class="fw-semibold mb-3">Gagal Produksi</h6>
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                @if ($bolehInputGagal)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" type="button" data-bs-toggle="tab"
                            data-bs-target="#catat-gagal-produksi" role="tab">Catat Gagal Produksi</button>
                    </li>
                @endif
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $bolehInputGagal ? '' : 'active' }}" type="button" data-bs-toggle="tab"
                        data-bs-target="#riwayat-gagal-produksi" role="tab">Riwayat ({{ $gagalProduksiRecords->count() }})</button>
                </li>
            </ul>
        </div>
        <div class="card-body tab-content">
            @if ($bolehInputGagal)
                <div class="tab-pane fade show active" id="catat-gagal-produksi" role="tabpanel">
                    @include('layouts.includes.errors')
                    @if ($gagalProduksiOptions->isEmpty())
                        <p class="text-muted mb-0">Belum ada produk pada order ini.</p>
                    @else
                        <form method="post" action="{{ $gagalProduksiStoreUrl }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="produk-gagal-order">Produk dari order</label>
                                    <select class="form-select" name="{{ $gagalProduksiField }}" id="produk-gagal-order" required>
                                        <option value="">Pilih produk</option>
                                        @foreach ($gagalProduksiOptions as $option)
                                            <option value="{{ $option['value'] }}" {{ old($gagalProduksiField) == $option['value'] ? 'selected' : '' }}>
                                                {{ $option['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="tanggal-gagal-order">Tanggal kejadian</label>
                                    <input class="form-control" type="date" name="tanggal" id="tanggal-gagal-order"
                                        value="{{ old('tanggal', now()->toDateString()) }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="jumlah-gagal-order">Jumlah gagal</label>
                                    <input class="form-control" type="number" name="jumlah" id="jumlah-gagal-order"
                                        min="1" max="1000000000" value="{{ old('jumlah') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="tahap-gagal-order">Tahap proses (opsional)</label>
                                    <input class="form-control" type="text" name="tahap" id="tahap-gagal-order"
                                        maxlength="255" value="{{ old('tahap') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="keterangan-gagal-order">Penyebab / keterangan</label>
                                    <input class="form-control" type="text" name="keterangan" id="keterangan-gagal-order"
                                        maxlength="2000" value="{{ old('keterangan') }}">
                                </div>
                            </div>
                            <p class="text-muted small mt-3 mb-2">Kerugian dihitung dari jumlah gagal × HPP produk saat dicatat. Tidak mengubah stok atau jumlah order.</p>
                            <button class="btn btn-primary" type="submit">Simpan produk gagal</button>
                        </form>
                    @endif
                </div>
            @endif
            <div class="tab-pane fade {{ $bolehInputGagal ? '' : 'show active' }}" id="riwayat-gagal-produksi" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Total kerugian order: <strong>Rp {{ number_format($gagalProduksiRecords->sum('kerugian'), 0, ',', '.') }}</strong></span>
                    <a href="{{ route('gagalProduksi.index') }}" class="small">Laporan harian</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Tanggal</th><th>Produk</th><th>Tahap</th><th>Jumlah gagal</th><th>Kerugian</th><th>Keterangan</th></tr></thead>
                        <tbody>
                            @forelse ($gagalProduksiRecords as $item)
                                <tr>
                                    <td>{{ $item->tanggal->format('d/m/Y') }}</td>
                                    <td>{{ $item->nama_produk ?: ($item->orderDetail?->produk?->nama_lengkap ?? '-') }}</td>
                                    <td>{{ $item->tahap ?: '-' }}</td>
                                    <td>{{ $item->jumlah }}</td>
                                    <td>Rp {{ number_format($item->kerugian, 0, ',', '.') }}</td>
                                    <td>{{ $item->keterangan ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted text-center">Belum ada catatan gagal produksi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endif
