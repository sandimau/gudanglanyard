@extends('layouts.app')

@section('title')
    Status Sync Stok Shopee
@endsection

@section('content')
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Sync Stok Shopee Otomatis</h5>
            <a href="{{ route('marketplaces.index') }}" class="btn btn-sm btn-secondary">Kembali</a>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">
                Stok ERP otomatis di-push ke semua toko Shopee saat ada perubahan (order, opname, cancel, dll.).
                Tidak perlu klik tombol — cukup atur cron eksternal sekali.
            </p>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <b>Status Toko Shopee</b>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Toko</th>
                            <th>Auto Sync</th>
                            <th>Terakhir Sync API</th>
                            <th>Terakhir Upload CSV</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($marketplaces as $mp)
                            <tr>
                                <td>{{ $mp->nama }}</td>
                                <td>
                                    @if ($mp->auto_sync_stok)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    @endif
                                </td>
                                <td>{{ $mp->tglSyncStok ?? '-' }}</td>
                                <td>{{ $mp->tglUploadStok ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-muted">Belum ada toko Shopee tersinkron.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <b>Produk Menunggu Sync ({{ $pending->count() }})</b>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Produk ID</th>
                            <th>Dirty Sejak</th>
                            <th>Terakhir Sync</th>
                            <th>Stok Terakhir</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pending as $row)
                            <tr>
                                <td>{{ $row->produk_id }}</td>
                                <td>{{ $row->dirty_at }}</td>
                                <td>{{ $row->last_synced_at ?? '-' }}</td>
                                <td>{{ $row->last_synced_stock ?? '-' }}</td>
                                <td class="text-danger small">{{ $row->last_error ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted">Tidak ada produk yang menunggu sync.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <b>Log Error Sync Terbaru</b>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Toko</th>
                            <th>Pesan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentErrors as $log)
                            <tr>
                                <td>{{ $log->tanggal }}</td>
                                <td>{{ $log->marketplace }}</td>
                                <td class="small">{{ \Illuminate\Support\Str::limit($log->isi, 120) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-muted">Belum ada error sync.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
