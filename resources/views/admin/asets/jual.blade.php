@extends('layouts.app')

@section('title')
    Jual Aset
@endsection

@section('content')
    @php
        $nilaiAset = $aset->nilai_awal + $aset->total_penambahan;
    @endphp

    <div class="container-fluid">
        @include('layouts.includes.messages')

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-0">Jual Aset</h5>
                    <small class="text-muted">Jurnal penjualan dibuat otomatis saat disimpan.</small>
                </div>
                <a href="{{ route('aset.index') }}" class="btn btn-secondary">Kembali</a>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">Produk</div>
                            <div class="fw-semibold">{{ $aset->nama_produk }}</div>
                            <div class="text-muted small mt-1">{{ $aset->kontak->nama ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="border rounded p-3 h-100 bg-light">
                            <div class="text-muted small">Nilai Perolehan</div>
                            <div class="fw-semibold">Rp {{ number_format($nilaiAset, 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 h-100 bg-light">
                            <div class="text-muted small">Akumulasi Penyusutan</div>
                            <div class="fw-semibold">Rp {{ number_format($totalPenyusutan, 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 h-100 bg-primary bg-opacity-10 border-primary">
                            <div class="text-muted small">Nilai Buku</div>
                            <div class="fw-bold text-primary">Rp {{ number_format($nilaiBuku, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>

                <form action="{{ route('aset.jual.store', $aset) }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Harga Jual</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="harga_jual" id="harga_jual" min="0"
                                    class="form-control @error('harga_jual') is-invalid @enderror"
                                    value="{{ old('harga_jual') }}" required>
                            </div>
                            @error('harga_jual')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Akun Kas Penerima</label>
                            <select name="akun_detail_id" id="akun_detail_id"
                                class="form-select @error('akun_detail_id') is-invalid @enderror" required>
                                <option value="">Pilih akun kas</option>
                                @foreach ($kas as $akun)
                                    <option value="{{ $akun->id }}" {{ old('akun_detail_id') == $akun->id ? 'selected' : '' }}>
                                        {{ $akun->nama }}
                                    </option>
                                @endforeach
                            </select>
                            @error('akun_detail_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Tanggal Jual</label>
                            <input type="date" name="tanggal"
                                class="form-control @error('tanggal') is-invalid @enderror"
                                value="{{ old('tanggal', date('Y-m-d')) }}">
                            @error('tanggal')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Keterangan</label>
                            <input type="text" name="keterangan"
                                class="form-control @error('keterangan') is-invalid @enderror"
                                value="{{ old('keterangan') }}" placeholder="Keterangan penjualan (opsional)">
                            @error('keterangan')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                <div class="d-flex justify-content-between">
                                    <span>Selisih harga jual terhadap nilai buku</span>
                                    <strong id="selisih">Rp 0</strong>
                                </div>
                                <div class="text-muted small mt-1" id="status-selisih">Masukkan harga jual.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-success"
                            onclick="return confirm('Simpan penjualan aset ini? Saldo aset akan menjadi nol.')">
                            Simpan Penjualan
                        </button>
                        <a href="{{ route('aset.index') }}" class="btn btn-light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('after-scripts')
    <script>
        const nilaiBuku = {{ (int) $nilaiBuku }};
        const rupiah = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        });

        function hitungSelisih() {
            const hargaJual = parseFloat(document.getElementById('harga_jual').value || 0);
            const selisih = hargaJual - nilaiBuku;
            const target = document.getElementById('selisih');
            const status = document.getElementById('status-selisih');

            target.textContent = rupiah.format(selisih);

            if (selisih > 0) {
                target.className = 'text-success fw-bold';
                status.textContent = 'Keuntungan penjualan aset.';
            } else if (selisih < 0) {
                target.className = 'text-danger fw-bold';
                status.textContent = 'Kerugian penjualan aset.';
            } else {
                target.className = 'fw-bold';
                status.textContent = 'Harga jual sama dengan nilai buku.';
            }
        }

        document.getElementById('harga_jual').addEventListener('input', hitungSelisih);
        hitungSelisih();
    </script>
@endpush
