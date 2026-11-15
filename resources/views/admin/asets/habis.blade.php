@extends('layouts.app')

@section('title')
    Aset Habis
@endsection

@section('content')
    <div class="container-fluid">
        @include('layouts.includes.messages')

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <h4 class="mb-1">Aset Habis</h4>
                <p class="text-muted mb-0">Aset dengan saldo nol atau kurang, termasuk yang sudah terjual.</p>
            </div>
            <a href="{{ route('aset.index') }}" class="btn btn-secondary">Kembali ke Aset Aktif</a>
        </div>

        <div class="card">
            <div class="card-body">
                {{ $asets->links() }}

                <div class="table-responsive mt-3">
                    <table class="table table-bordered table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Tanggal Beli</th>
                                <th>Produk</th>
                                <th>Supplier</th>
                                <th class="text-end">Nilai Perolehan</th>
                                <th class="text-end">Saldo</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($asets as $aset)
                                @php
                                    $terakhir = $aset->details->last();
                                    $terjual = $terakhir && $terakhir->kode === 'jual';
                                @endphp
                                <tr>
                                    <td>{{ optional($aset->tanggal_beli)->format('d/m/Y') }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $aset->nama_produk }}</div>
                                        @if ($aset->keterangan)
                                            <div class="text-muted small">{{ $aset->keterangan }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $aset->kontak->nama ?? '-' }}</td>
                                    <td class="text-end">Rp {{ number_format($aset->nilai_awal, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($aset->saldo, 0, ',', '.') }}</td>
                                    <td>
                                        @if ($terjual)
                                            <span class="badge bg-success">Terjual</span>
                                            <div class="text-muted small">{{ optional($terakhir->tanggal)->format('d/m/Y') }}</div>
                                        @else
                                            <span class="badge bg-secondary">Habis Disusutkan</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('aset.riwayat', $aset) }}" class="btn btn-sm btn-outline-primary">
                                            Riwayat
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Belum ada aset habis.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
