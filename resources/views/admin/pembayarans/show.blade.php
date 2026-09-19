@extends('layouts.app')

@section('title')
    Riwayat Pembayaran {{ $order->nota }}
@endsection

@section('content')
    <div class="bg-light rounded">
        @include('layouts.includes.messages')

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white payment-history-header">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="card-title mb-1">Riwayat Pembayaran</h5>
                        <div class="text-muted">
                            {{ $order->nota }} · {{ $order->kontak->nama ?? '-' }}
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        @can('order_detail_access')
                            <a href="{{ route('order.detail', $order->id) }}" class="btn btn-light border">
                                Kembali ke order
                            </a>
                        @else
                            <a href="{{ route('pembayaran.index') }}" class="btn btn-light border">
                                Kembali
                            </a>
                        @endcan
                        @if (($order->kekurangan ?? 0) > 0 && (auth()->user()->can('akun_detail_access') || auth()->user()->can('keuangan')))
                            <a href="{{ route('order.bayar', $order->id) }}" class="btn btn-primary">
                                <i class="bx bx-plus-circle"></i> Tambah pembayaran
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6 col-xl-3">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">Total tagihan</div>
                            <div class="fs-5 fw-bold">Rp {{ number_format($order->total, 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">Diskon</div>
                            <div class="fs-5 fw-bold">Rp {{ number_format($order->diskon, 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="border border-success rounded p-3 h-100 bg-success bg-opacity-10">
                            <div class="text-success small">Sudah dibayar</div>
                            <div class="fs-5 fw-bold text-success">Rp {{ number_format($order->bayar, 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="border {{ $order->kekurangan > 0 ? 'border-warning bg-warning bg-opacity-10' : 'border-success bg-success bg-opacity-10' }} rounded p-3 h-100">
                            <div class="{{ $order->kekurangan > 0 ? 'text-warning-emphasis' : 'text-success' }} small">
                                Kekurangan
                            </div>
                            <div class="fs-5 fw-bold">Rp {{ number_format($order->kekurangan, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0">History Bayar</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Kas</th>
                                <th class="text-end">Jumlah</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pembayarans as $pembayaran)
                                <tr>
                                    <td class="text-nowrap">{{ $pembayaran->created_at?->format('d-m-Y H:i') ?? '-' }}</td>
                                    <td>{{ $pembayaran->akunDetail?->nama ?? '-' }}</td>
                                    <td class="text-end text-nowrap fw-semibold">
                                        Rp {{ number_format($pembayaran->jumlah, 0, ',', '.') }}
                                    </td>
                                    <td>
                                        @if ($pembayaran->status === 'approve')
                                            <span class="badge bg-success">Disetujui</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($pembayaran->status ?? '-') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $pembayaran->ket ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Belum ada riwayat pembayaran untuk order ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($pembayarans->hasPages())
                    <div class="mt-3">{{ $pembayarans->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
