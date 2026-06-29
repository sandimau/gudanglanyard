@extends('layouts.app')

@section('title')
    Proses Produksi
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Proses Produksi</h5>
                    </div>
                    <a href="{{ route('produksi.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus"></i> Produk Produksi
                    </a>
                </div>
            </div>
            <div class="card-body">
                @include('layouts.includes.messages')

                <!-- Filter Status -->
                <div class="mb-3">
                    <form method="GET" action="{{ route('produksi.index') }}" id="filterForm">
                        <div class="row align-items-end">
                            <div class="col-md-3">
                                <label for="status" class="form-label mb-2">Filter Status</label>
                                <select name="status" id="status" class="form-select"
                                    onchange="document.getElementById('filterForm').submit();">
                                    <option value="">Semua Status</option>
                                    <option value="proses" {{ request('status') == 'proses' ? 'selected' : '' }}>Proses
                                    </option>
                                    <option value="selesai" {{ request('status') == 'selesai' ? 'selected' : '' }}>Selesai
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <a href="{{ route('produksi.index') }}" class="btn btn-secondary">Reset Filter</a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Table -->
                {{ $produksis->links() }}
                <div class="table-responsive">
                    <table class="table table-striped" id="myTable">
                        <thead>
                            <tr>
                                <th scope="col">Tanggal</th>
                                <th scope="col">Produk</th>
                                <th scope="col">Total Biaya</th>
                                <th scope="col">Keterangan</th>
                                <th scope="col">User</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($produksis as $produksi)
                                <tr>
                                    <td>{{ date('d-m-Y H:i', strtotime($produksi->created_at)) }}</td>
                                    <td><a href="{{ route('produksi.show', $produksi->id) }}">{{ $produksi->hasilProduk ?? '-' }}</a></td>
                                    <td>{{ number_format($produksi->biaya ?? 0, 0, ',', '.') }}</td>
                                    <td>{{ $produksi->ket ?? '-' }}</td>
                                    <td>{{ $produksi->user ?? '-' }}</td>
                                    <td>
                                        @if ($produksi->status == 'finish')
                                            <span class="badge bg-success">Selesai</span>
                                        @else
                                            <span class="badge bg-warning">Proses</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada data produksi</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
