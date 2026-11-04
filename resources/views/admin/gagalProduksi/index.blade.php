@extends('layouts.app')

@section('title', 'Produk Gagal')

@section('content')
    <div class="container-fluid">
        @include('layouts.includes.messages')
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Catatan Produk Gagal</h5>
                @if (auth()->user()->can('gagal_produksi_access') && auth()->user()->can('gagal_produksi_manage'))
                    <a href="{{ route('gagalProduksi.create') }}" class="btn btn-primary btn-sm">+ Catat produk gagal</a>
                @endif
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
                        <thead><tr><th>Produk</th><th>Tahap</th><th>Jumlah gagal</th><th>Kerugian</th><th>Keterangan</th><th>Dicatat oleh</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($catatan as $item)
                                <tr>
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
                                <tr><td colspan="7" class="text-center text-muted">Belum ada produk gagal pada tanggal ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $catatan->links() }}
            </div>
        </div>
    </div>
@endsection
