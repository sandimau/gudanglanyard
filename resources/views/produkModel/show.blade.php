@extends('layouts.app')

@section('title', 'Detail Produk')

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-3 py-3">
            <div>
                <div class="text-muted small mb-1">{{ $produkModel->kategori->nama }}</div>
                <h4 class="mb-0">{{ $produkModel->nama }}</h4>
            </div>
            <a href="{{ route('produkModel.index', ['kategori_id' => $produkModel->kategori_id]) }}"
                class="btn btn-outline-secondary">Kembali ke Daftar</a>
        </div>

        <div class="card-body p-3 p-md-4">
            <ul class="nav nav-tabs mb-4" id="produkModelTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="detail-tab" data-bs-toggle="tab" data-bs-target="#detail"
                        type="button" role="tab" aria-controls="detail" aria-selected="true">Detail Produk</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="varian-tab" data-bs-toggle="tab" data-bs-target="#varian"
                        type="button" role="tab" aria-controls="varian" aria-selected="false">
                        Varian <span class="badge bg-secondary ms-1">{{ $produkModel->produk->count() }}</span>
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="produkModelTabContent">
                <div class="tab-pane fade show active" id="detail" role="tabpanel" aria-labelledby="detail-tab">
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <div class="border rounded p-3 text-center bg-light">
                                @if ($produkModel->gambar)
                                    <a href="{{ asset('uploads/produk/' . $produkModel->gambar) }}" target="_blank"
                                        rel="noopener noreferrer" title="Lihat gambar produk">
                                        <img src="{{ asset('uploads/produk/' . $produkModel->gambar) }}"
                                            class="img-fluid rounded" style="max-height: 260px; object-fit: contain"
                                            alt="Gambar {{ $produkModel->nama }}">
                                    </a>
                                @else
                                    <div class="d-flex align-items-center justify-content-center text-muted" style="min-height: 200px">
                                        Tidak ada gambar produk
                                    </div>
                                @endif
                            </div>
                            <a href="{{ route('produkModel.edit', ['produkModel' => $produkModel->id, 'kategori_id' => $produkModel->kategori_id]) }}"
                                class="btn btn-primary w-100 mt-3">Edit Produk</a>
                        </div>

                        <div class="col-lg-8">
                            <div class="border rounded h-100 p-3 p-md-4">
                                <h5 class="mb-4">Informasi Produk</h5>
                                <dl class="row mb-0 gy-3">
                                    <dt class="col-sm-4 text-muted fw-normal">Nama Produk</dt>
                                    <dd class="col-sm-8 mb-0 fw-semibold">{{ $produkModel->nama }}</dd>

                                    <dt class="col-sm-4 text-muted fw-normal">Kategori</dt>
                                    <dd class="col-sm-8 mb-0">{{ $produkModel->kategori->nama }}</dd>

                                    <dt class="col-sm-4 text-muted fw-normal">Harga</dt>
                                    <dd class="col-sm-8 mb-0 fw-semibold">Rp {{ number_format($produkModel->harga, 0, ',', '.') }}</dd>

                                    <dt class="col-sm-4 text-muted fw-normal">Satuan</dt>
                                    <dd class="col-sm-8 mb-0">{{ $produkModel->satuan }}</dd>

                                    <dt class="col-sm-4 text-muted fw-normal">Status</dt>
                                    <dd class="col-sm-8 mb-0 d-flex flex-wrap gap-2">
                                        <span class="badge {{ $produkModel->jual ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $produkModel->jual ? 'Dijual' : 'Tidak Dijual' }}
                                        </span>
                                        <span class="badge {{ $produkModel->beli ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $produkModel->beli ? 'Dapat Dibeli' : 'Tidak Dapat Dibeli' }}
                                        </span>
                                        <span class="badge {{ $produkModel->stok ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $produkModel->stok ? 'Stok Tersedia' : 'Stok Kosong' }}
                                        </span>
                                    </dd>

                                    <dt class="col-sm-4 text-muted fw-normal">Deskripsi</dt>
                                    <dd class="col-sm-8 mb-0 text-break">{{ $produkModel->deskripsi ?: 'Tidak ada deskripsi' }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="varian" role="tabpanel" aria-labelledby="varian-tab">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                        <div>
                            <h5 class="mb-1">Daftar Varian</h5>
                            <p class="text-muted small mb-0">Varian yang terdaftar untuk produk ini.</p>
                        </div>
                        <a href="{{ route('produks.create', ['produkModel' => $produkModel->id, 'kategori_id' => $produkModel->kategori_id]) }}"
                            class="btn btn-primary">Tambah Varian</a>
                    </div>
                    <div class="table-responsive border rounded">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">SKU</th>
                                    <th scope="col">Varian</th>
                                    <th scope="col">Status</th>
                                    <th scope="col" class="text-end">HPP</th>
                                    <th scope="col" class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($produkModel->produk as $produk)
                                    <tr>
                                        <td>{{ $produk->id }}</td>
                                        <td>{{ $produk->nama ?: '-' }}</td>
                                        <td>
                                            <span class="badge {{ $produk->status == 1 ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $produk->status == 1 ? 'Aktif' : 'Tidak Aktif' }}
                                            </span>
                                        </td>
                                        <td class="text-end text-nowrap">Rp {{ number_format($produk->hpp, 0, ',', '.') }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('produks.edit', $produk->id) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada varian.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
