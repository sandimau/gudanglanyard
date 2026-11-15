@extends('layouts.app')

@section('title')
    Riwayat Aset
@endsection

@section('content')
    <div class="container-fluid">
        @include('layouts.includes.messages')

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <h4 class="mb-1">Riwayat Aset</h4>
                <p class="text-muted mb-0">{{ $aset->nama_produk }} &bull; {{ $aset->kontak->nama ?? '-' }}</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('aset.index') }}" class="btn btn-secondary">Kembali</a>
                @can('aset_manage')
                    @if ($aset->saldo > 0)
                        <a href="{{ route('aset.edit', $aset) }}" class="btn btn-warning">Penyesuaian</a>
                        <a href="{{ route('aset.jual', $aset) }}" class="btn btn-success">Jual Aset</a>
                    @endif
                @endcan
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Nilai Perolehan</div>
                        <div class="fs-5 fw-semibold">Rp {{ number_format($aset->nilai_awal, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Total Penyusutan</div>
                        <div class="fs-5 fw-semibold">Rp {{ number_format($aset->total_penyusutan, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Total Penambahan</div>
                        <div class="fs-5 fw-semibold">Rp {{ number_format($aset->total_penambahan, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 border-primary">
                    <div class="card-body">
                        <div class="text-muted small">Saldo Saat Ini</div>
                        <div class="fs-5 fw-bold text-primary">Rp {{ number_format($aset->saldo, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-wrap gap-3 text-muted small">
                    <span>Tanggal beli: <strong>{{ optional($aset->tanggal_beli)->format('d/m/Y') }}</strong></span>
                    <span>Masa penyusutan: <strong>{{ $aset->waktuPenyusutan }} tahun</strong></span>
                    <span>Penyusutan bulanan: <strong>Rp {{ number_format($aset->penyusutan_bulanan, 0, ',', '.') }}</strong></span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Kode</th>
                                <th>Keterangan</th>
                                <th>Jurnal</th>
                                <th class="text-end">Debet</th>
                                <th class="text-end">Kredit</th>
                                <th class="text-end">Saldo Berjalan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($details as $detail)
                                <tr>
                                    <td>{{ optional($detail->tanggal)->format('d/m/Y') }}</td>
                                    <td><span class="badge bg-secondary">{{ $detail->kode }}</span></td>
                                    <td>
                                        {{ $detail->keterangan ?: $detail->kode_label }}
                                        <div class="text-muted small">{{ $detail->kode_label }}</div>
                                    </td>
                                    <td>
                                        @if ($detail->jurnal)
                                            <span class="small">{{ $detail->jurnal->nomor }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        {{ $detail->debet > 0 ? 'Rp ' . number_format($detail->debet, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="text-end">
                                        {{ $detail->kredit > 0 ? 'Rp ' . number_format($detail->kredit, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="text-end fw-semibold">Rp {{ number_format($detail->saldo_berjalan, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Belum ada riwayat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="text-muted small mt-3">
                    Saldo berjalan dihitung dari jumlah debet dikurangi jumlah kredit, berurutan menurut id detail.
                    Detail <code>sst</code> dapat berasal dari penyusutan jadwal maupun penyesuaian pengurangan.
                </div>
            </div>
        </div>
    </div>
@endsection
