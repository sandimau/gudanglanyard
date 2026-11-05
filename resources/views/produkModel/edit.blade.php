@extends('layouts.app')

@section('title')
    Edit Produk Model
@endsection

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Edit Produk</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('produkModel.update', ['produkModel' => $produkModel->id, 'kategori_id' => $kategori->id]) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Nama Produk</label>
                    <input type="text" class="form-control @error('nama') is-invalid @enderror" name="nama" value="{{ old('nama', $produkModel->nama) }}">
                    @error('nama')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Harga</label>
                    <input type="number" class="form-control @error('harga') is-invalid @enderror" name="harga" value="{{ old('harga', $produkModel->harga) }}">
                    @error('harga')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Satuan</label>
                    <select class="form-control @error('satuan') is-invalid @enderror" name="satuan">
                        @foreach ($satuan as $item)
                            <option value="{{ $item }}" {{ old('satuan', $produkModel->satuan) == $item ? 'selected' : '' }}>{{ $item }}</option>
                        @endforeach
                    </select>
                    @error('satuan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>
                    <textarea class="form-control" name="deskripsi">{{ old('deskripsi', $produkModel->deskripsi) }}</textarea>
                </div>

                <input type="hidden" name="kategori_id" value="{{ $kategori->id }}">

                <div class="mb-3">
                    <label class="form-label">Gambar</label>
                    @if($produkModel->gambar)
                        <div class="mb-2">
                            <img style="height: 60px" src="{{ url('uploads/produk/' . $produkModel->gambar) }}"alt="" srcset="">
                        </div>
                    @endif
                    <input type="file" class="form-control @error('gambar') is-invalid @enderror" name="gambar">
                    @error('gambar')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-end">
                    <a href="{{ route('produkModel.index', ['kategori_id' => $kategori->id]) }}" class="btn btn-secondary me-2">Batal</a>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
