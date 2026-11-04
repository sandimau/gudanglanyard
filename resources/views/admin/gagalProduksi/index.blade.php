@extends('layouts.app')

@section('title', 'Produk Gagal')

@section('content')
    <div class="container-fluid">
        @include('layouts.includes.messages')
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Catatan Produk Gagal</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('gagalProduksi.index') }}" method="get" class="row g-2 align-items-end mb-3">
                    <div class="col-auto">
                        <label for="tanggal" class="form-label">Tanggal kejadian</label>
                        <input type="date" id="tanggal" name="tanggal" class="form-control" value="{{ $tanggal }}">
                    </div>
                    <div class="col-auto"><button class="btn btn-primary" type="submit">Tampilkan</button></div>
                </form>
                <p class="text-secondary">Total gagal pada {{ \Illuminate\Support\Carbon::parse($tanggal)->format('d/m/Y') }}:
                    <strong>{{ number_format($totalGagal) }}</strong> dari {{ $catatan->total() }} catatan.</p>
                <div class="alert alert-warning">Total kerugian produk gagal pada tanggal ini:
                    <strong>Rp {{ number_format($totalKerugian, 0, ',', '.') }}</strong>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead><tr><th>Order</th><th>Produk</th><th>Tahap</th><th>Jumlah gagal</th><th>Kerugian</th><th>Keterangan</th><th>Dicatat oleh</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($catatan as $item)
                                <tr>
                                    <td>
                                        @if ($item->projectMp)
                                            <a href="{{ route('projectmp.detail', $item->projectMp->id) }}">{{ $item->projectMp->nota }}</a>
                                        @elseif ($item->orderDetail?->order)
                                            <a href="{{ route('order.detail', $item->orderDetail->order_id) }}">{{ $item->orderDetail->order->nota }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $item->nama_produk ?: ($item->orderDetail?->produk?->namaLengkap ?? '-') }}</td>
                                    <td>{{ $item->tahap ?: '-' }}</td>
                                    <td>{{ $item->jumlah }}</td>
                                    <td>Rp {{ number_format($item->kerugian, 0, ',', '.') }}</td>
                                    <td>{{ $item->keterangan ?: '-' }}</td>
                                    <td>{{ $item->user->name ?? '-' }}</td>
                                    <td>
                                        @if (auth()->user()->can('gagal_produksi_manage') && can_edit_cabang_record($item->cabang_id ?? $item->orderDetail?->order?->cabang_id))
                                            <form method="post" action="{{ route('gagalProduksi.destroy', $item->id) }}" onsubmit="return confirm('Hapus catatan produk gagal ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted">Belum ada produk gagal pada tanggal ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $catatan->links() }}
            </div>
        </div>
    </div>
@endsection
