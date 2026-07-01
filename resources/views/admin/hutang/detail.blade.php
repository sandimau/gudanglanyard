@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-1">Detail {{ ucfirst($hutang->jenis) }}</h5>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <span class="text-muted">{{ $hutang->kontak->nama }} &bull; {{ $hutang->tanggal->format('d/m/Y') }}</span>
                <span class="badge bg-success">Lunas</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100 bg-light">
                        <div class="text-muted small">Total {{ ucfirst($hutang->jenis) }}</div>
                        <div class="fs-5 fw-semibold mb-0">
                            Rp {{ number_format($hutang->jumlah, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100 bg-light">
                        <div class="text-muted small">Total Bayar</div>
                        <div class="fs-5 fw-semibold text-success mb-0">
                            Rp {{ number_format($hutang->total_bayar, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100 border-success bg-success bg-opacity-10">
                        <div class="text-muted small">Sisa</div>
                        <div class="fs-5 fw-semibold text-success mb-0">
                            Rp {{ number_format($hutang->sisa, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">Riwayat Pembayaran</h6>
                <span class="badge bg-secondary">{{ $hutang->details->count() }} transaksi</span>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="50">No</th>
                            <th>Tanggal</th>
                            <th>Jumlah</th>
                            <th>Kas</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($hutang->details as $detail)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $detail->tanggal->format('d/m/Y') }}</td>
                                <td class="fw-semibold">Rp {{ number_format($detail->jumlah, 0, ',', '.') }}</td>
                                <td>{{ $detail->akun_detail->nama ?? '-' }}</td>
                                <td>{{ $detail->keterangan ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    Belum ada pembayaran
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
