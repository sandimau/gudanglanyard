@extends('layouts.app')

@section('title', 'Riwayat Belanja Produk')

@section('content')
    @php
        $totalBelanja = $belanjas->sum(fn ($item) => $item->jumlah * $item->harga);
    @endphp

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-3 py-3">
            <div>
                <div class="text-muted small mb-1">Riwayat Belanja</div>
                <h4 class="mb-0">{{ $produk->produkModel->nama }}{{ $produk->nama ? ' - ' . $produk->nama : '' }}</h4>
            </div>
            <a href="{{ route('produkModel.index', ['kategori_id' => $produk->produkModel->kategori_id]) }}"
                class="btn btn-outline-secondary">Kembali ke Daftar</a>
        </div>

        <div class="card-body p-3 p-md-4">
            <div class="row g-3 mb-4">
                <div class="col-md-6 col-xl-3">
                    <div class="border rounded h-100 p-3">
                        <div class="text-muted small mb-1">SKU</div>
                        <div class="fw-semibold">{{ $produk->id }}</div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="border rounded h-100 p-3">
                        <div class="text-muted small mb-1">Kategori</div>
                        <div class="fw-semibold">{{ $produk->produkModel->kategori->nama }}</div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="border rounded h-100 p-3">
                        <div class="text-muted small mb-1">Jumlah Transaksi</div>
                        <div class="fw-semibold">{{ $belanjas->count() }}</div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="border rounded h-100 p-3">
                        <div class="text-muted small mb-1">Total Belanja</div>
                        <div class="fw-semibold text-primary">Rp {{ number_format($totalBelanja, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            <h5 class="mb-3">Daftar Transaksi</h5>
            <div class="table-responsive border rounded">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">No.</th>
                            <th scope="col">Tanggal</th>
                            <th scope="col" class="text-end">Jumlah</th>
                            <th scope="col" class="text-end">Harga Satuan</th>
                            <th scope="col" class="text-end">Total</th>
                            <th scope="col">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($belanjas as $index => $belanja)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="text-nowrap">{{ $belanja->created_at->format('d-m-Y') }}</td>
                                <td class="text-end">{{ number_format($belanja->jumlah, 0, ',', '.') }}</td>
                                <td class="text-end text-nowrap">Rp {{ number_format($belanja->harga, 0, ',', '.') }}</td>
                                <td class="text-end text-nowrap fw-semibold">Rp {{ number_format($belanja->jumlah * $belanja->harga, 0, ',', '.') }}</td>
                                <td class="text-break">{{ $belanja->keterangan ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Belum ada riwayat belanja.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="4" class="text-end">Total Belanja</th>
                            <th class="text-end text-nowrap">Rp {{ number_format($totalBelanja, 0, ',', '.') }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
