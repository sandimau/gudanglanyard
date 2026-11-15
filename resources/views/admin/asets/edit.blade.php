@extends('layouts.app')

@section('title')
    Penyesuaian Aset
@endsection

@section('content')
    <div class="container-fluid">
        @include('layouts.includes.messages')

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-0">Penyesuaian Aset</h5>
                    <small class="text-muted">Penyesuaian manual tidak membuat jurnal otomatis.</small>
                </div>
                <a href="{{ route('aset.index') }}" class="btn btn-secondary">Kembali</a>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">Produk</div>
                            <div class="fw-semibold">{{ $aset->nama_produk }}</div>
                            <div class="text-muted small mt-1">{{ $aset->kontak->nama ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 h-100 bg-light">
                            <div class="text-muted small">Nilai Perolehan</div>
                            <div class="fw-semibold">Rp {{ number_format($aset->nilai_awal, 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 h-100 bg-primary bg-opacity-10 border-primary">
                            <div class="text-muted small">Saldo Saat Ini</div>
                            <div class="fw-bold text-primary">Rp {{ number_format($aset->saldo, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>

                <form action="{{ route('aset.update', $aset) }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Jenis Penyesuaian</label>
                            <select name="jenis_penyesuaian" id="jenis_penyesuaian"
                                class="form-select @error('jenis_penyesuaian') is-invalid @enderror" required>
                                <option value="tambah" {{ old('jenis_penyesuaian') === 'tambah' ? 'selected' : '' }}>Tambah nilai</option>
                                <option value="kurang" {{ old('jenis_penyesuaian') === 'kurang' ? 'selected' : '' }}>Kurangi nilai</option>
                            </select>
                            @error('jenis_penyesuaian')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Nominal</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="nominal" id="nominal" min="1"
                                    class="form-control @error('nominal') is-invalid @enderror"
                                    value="{{ old('nominal') }}" required>
                            </div>
                            @error('nominal')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Saldo Setelah Penyesuaian</label>
                            <div class="form-control bg-light fw-bold" id="saldo-setelah">
                                Rp {{ number_format($aset->saldo, 0, ',', '.') }}
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Keterangan</label>
                            <input type="text" name="keterangan"
                                class="form-control @error('keterangan') is-invalid @enderror"
                                value="{{ old('keterangan') }}" placeholder="Alasan penyesuaian (opsional)">
                            @error('keterangan')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="alert alert-warning mt-4 mb-0">
                        Pengurangan nilai dicatat sebagai detail <code>sst</code>, penambahan nilai sebagai detail
                        <code>pls</code>. Saldo aset langsung mengikuti detail tersebut.
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Simpan Penyesuaian</button>
                        <a href="{{ route('aset.riwayat', $aset) }}" class="btn btn-light">Lihat Riwayat</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('after-scripts')
    <script>
        const saldoAwal = {{ (int) $aset->saldo }};
        const rupiah = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        });

        function hitungSaldo() {
            const jenis = document.getElementById('jenis_penyesuaian').value;
            const nominal = parseFloat(document.getElementById('nominal').value || 0);
            const saldo = jenis === 'tambah' ? saldoAwal + nominal : saldoAwal - nominal;

            document.getElementById('saldo-setelah').textContent = rupiah.format(saldo);
        }

        document.getElementById('jenis_penyesuaian').addEventListener('change', hitungSaldo);
        document.getElementById('nominal').addEventListener('input', hitungSaldo);
    </script>
@endpush
