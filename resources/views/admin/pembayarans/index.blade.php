@extends('layouts.app')

@section('title')
    Pembayaran
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="card-title mb-0">Pembayaran</h5>
                    <div class="text-end">
                        <div class="text-muted small">Total pembayaran</div>
                        <div class="fs-5 fw-bold text-success">Rp {{ number_format($totalPembayaran, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @include('layouts.includes.messages')

                <form method="GET" action="{{ route('pembayaran.index') }}" class="row g-2 align-items-end mb-4">
                    <div class="col-sm-6 col-lg-3">
                        <label for="cari" class="form-label">Cari</label>
                        <input type="text" name="cari" id="cari" class="form-control"
                            value="{{ request('cari') }}" placeholder="Nota, konsumen, atau keterangan">
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label for="akun_detail_id" class="form-label">Kas</label>
                        <select name="akun_detail_id" id="akun_detail_id" class="form-select">
                            <option value="">Semua kas</option>
                            @foreach ($akunDetails as $id => $nama)
                                <option value="{{ $id }}" @selected((string) request('akun_detail_id') === (string) $id)>
                                    {{ $nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <label for="dari" class="form-label">Dari</label>
                        <input type="date" name="dari" id="dari" class="form-control" value="{{ request('dari') }}">
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <label for="sampai" class="form-label">Sampai</label>
                        <input type="date" name="sampai" id="sampai" class="form-control" value="{{ request('sampai') }}">
                    </div>
                    <div class="col-lg-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('pembayaran.index') }}" class="btn btn-light">Reset</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Nota</th>
                                <th>Konsumen</th>
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
                                    <td>
                                        @if ($pembayaran->order)
                                            <a href="{{ route('order.pembayaran', $pembayaran->order_id) }}">
                                                {{ $pembayaran->order->nota ?? '-' }}
                                            </a>
                                        @else
                                            {{ $pembayaran->order?->nota ?? '-' }}
                                        @endif
                                    </td>
                                    <td>{{ $pembayaran->order?->kontak?->nama ?? '-' }}</td>
                                    <td>{{ $pembayaran->akunDetail?->nama ?? '-' }}</td>
                                    <td class="text-end text-nowrap">Rp {{ number_format($pembayaran->jumlah, 0, ',', '.') }}</td>
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
                                    <td colspan="7" class="text-center text-muted py-4">Tidak ada data pembayaran.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $pembayarans->links() }}
            </div>
        </div>
    </div>
@endsection
