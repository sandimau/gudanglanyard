@extends('layouts.app')

@section('title')
    Create Produk Stoks
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-0">
                        Stok Opname >
                        {{ $produk->produkModel->kategori->nama ?? '' }} -
                        {{ $produk->produkModel->nama }}
                    </h5>
                    @if ($varians->count() > 1)
                        <small class="text-muted">Update stok untuk semua varian sekaligus</small>
                    @endif
                </div>
                @can('kontak_create')
                    <a href="{{ route('produkStok.index', $produk->id) }}" class="btn btn-secondary">back</a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            @if ($errors->has('varians'))
                <div class="alert alert-danger">
                    {{ $errors->first('varians') }}
                </div>
            @endif
            @if ($errors->has('jumlah'))
                <div class="alert alert-danger">
                    {{ $errors->first('jumlah') }}
                </div>
            @endif

            <form method="POST" action="{{ route('produkStok.store') }}">
                @csrf
                <input type="hidden" name="produk_id" value="{{ $produk->id }}">

                <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th style="width: 80px">SKU</th>
                                <th>Varian</th>
                                <th class="text-center" style="width: 120px">Stok saat ini</th>
                                <th style="width: 140px">Tambah</th>
                                <th style="width: 140px">Kurang</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($varians as $varian)
                                @php
                                    $oldTambah = old('varians.' . $varian->id . '.tambah', 0);
                                    $oldKurang = old('varians.' . $varian->id . '.kurang', 0);
                                @endphp
                                <tr class="{{ $varian->id === $produk->id ? 'table-warning' : '' }}">
                                    <td>{{ $varian->id }}</td>
                                    <td>{{ $varian->nama ?: '-' }}</td>
                                    <td class="text-center fw-semibold {{ $varian->stok_saat_ini < 0 ? 'text-danger' : '' }}">
                                        {{ $varian->stok_saat_ini }}
                                    </td>
                                    <td>
                                        <input class="form-control"
                                            type="number"
                                            min="0"
                                            name="varians[{{ $varian->id }}][tambah]"
                                            value="{{ $oldTambah }}">
                                    </td>
                                    <td>
                                        <input class="form-control"
                                            type="number"
                                            min="0"
                                            name="varians[{{ $varian->id }}][kurang]"
                                            value="{{ $oldKurang }}">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="form-group mb-3">
                    <label for="keterangan">Keterangan</label>
                    <textarea class="form-control {{ $errors->has('keterangan') ? 'is-invalid' : '' }}"
                        name="keterangan" id="keterangan" cols="30" rows="4">{{ old('keterangan', '') }}</textarea>
                    @if ($errors->has('keterangan'))
                        <div class="invalid-feedback">
                            {{ $errors->first('keterangan') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="tanggal">Tanggal</label>
                    <input class="form-control {{ $errors->has('tanggal') ? 'is-invalid' : '' }}" type="date"
                        name="tanggal" id="tanggal" value="{{ old('tanggal', date('Y-m-d')) }}">
                    @if ($errors->has('tanggal'))
                        <div class="invalid-feedback">
                            {{ $errors->first('tanggal') }}
                        </div>
                    @endif
                </div>
                <div class="form-group">
                    <button class="btn btn-primary mt-2" type="submit">
                        Simpan Opname
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
