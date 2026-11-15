@extends('layouts.app')

@section('title')
    Aset Tetap
@endsection

@section('content')
    <div class="container-fluid">
        @include('layouts.includes.messages')

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <h4 class="mb-1">Aset Tetap</h4>
                <p class="text-muted mb-0">Aset perusahaan yang masih memiliki nilai buku.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('aset.habis') }}" class="btn btn-outline-secondary">
                    <i class='bx bx-archive'></i> Aset Habis
                </a>
                @can('aset_manage')
                    <a href="{{ route('aset.create') }}" class="btn btn-primary">
                        <i class='bx bx-plus-circle'></i> Tambah Aset
                    </a>
                @endcan
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-4">
                <div class="card border-primary h-100">
                    <div class="card-body">
                        <div class="text-muted small mb-1">Total Saldo Aset</div>
                        <div class="fs-3 fw-bold text-primary">Rp {{ number_format($totalSaldo, 0, ',', '.') }}</div>
                        <div class="text-muted small">{{ $totalAset }} aset aktif</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <form action="{{ route('aset.index') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <input type="text" name="q" value="{{ $q }}" class="form-control"
                            placeholder="Cari produk atau supplier...">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Cari</button>
                        @if ($q !== '')
                            <a href="{{ route('aset.index') }}" class="btn btn-light">Reset</a>
                        @endif
                    </div>
                </form>
            </div>
            <div class="card-body">
                {{ $asets->links() }}

                <div class="table-responsive mt-3">
                    <table class="table table-bordered table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Tanggal Beli</th>
                                <th>Produk</th>
                                <th>Supplier</th>
                                <th class="text-end">Harga</th>
                                <th class="text-center">Jumlah</th>
                                <th>Penyusutan</th>
                                <th class="text-end">Saldo</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($asets as $aset)
                                <tr>
                                    <td>{{ optional($aset->tanggal_beli)->format('d/m/Y') }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $aset->nama_produk }}</div>
                                        @if ($aset->keterangan)
                                            <div class="text-muted small">{{ $aset->keterangan }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $aset->kontak->nama ?? '-' }}</td>
                                    <td class="text-end">Rp {{ number_format($aset->harga, 0, ',', '.') }}</td>
                                    <td class="text-center">{{ number_format($aset->jumlah, 0, ',', '.') }}</td>
                                    <td>
                                        <div>{{ $aset->waktuPenyusutan }} tahun</div>
                                        <div class="text-muted small">Rp {{ number_format($aset->penyusutan_bulanan, 0, ',', '.') }}/bulan</div>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('aset.riwayat', $aset) }}" class="fw-bold text-decoration-none">
                                            Rp {{ number_format($aset->saldo, 0, ',', '.') }}
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            @can('aset_manage')
                                                <a href="{{ route('aset.edit', $aset) }}" class="btn btn-sm btn-warning"
                                                    title="Penyesuaian">
                                                    <i class='bx bx-edit'></i>
                                                </a>
                                                <a href="{{ route('aset.jual', $aset) }}" class="btn btn-sm btn-success"
                                                    title="Jual aset">
                                                    <i class='bx bx-dollar-circle'></i>
                                                </a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        Belum ada aset aktif.
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
