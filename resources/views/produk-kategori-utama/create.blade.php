@extends('layouts.app')

@section('title')
    Add Produk Kategori Utama
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Tambah Kategori Utama</div>

            <div class="card-body">
                <form action="{{ route('produk-kategori-utama.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="nama" class="form-label">Nama</label>
                        <input type="text" class="form-control @error('nama') is-invalid @enderror" id="nama" name="nama" value="{{ old('nama') }}" required>
                        @error('nama')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="jual" name="jual" value="1" {{ old('jual') ? 'checked' : '' }}>
                            <label class="form-check-label" for="jual">Jual</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="beli" name="beli" value="1" {{ old('beli') ? 'checked' : '' }}>
                            <label class="form-check-label" for="beli">Beli</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="stok" name="stok" value="1" {{ old('stok') ? 'checked' : '' }}>
                            <label class="form-check-label" for="stok">Stok</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="produksi" name="produksi" value="1" {{ old('produksi') ? 'checked' : '' }}>
                            <label class="form-check-label" for="produksi">produksi</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <input type="hidden" name="is_custom" value="0">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input @error('is_custom') is-invalid @enderror" id="is_custom" name="is_custom" value="1" @checked(old('is_custom'))>
                            <label class="form-check-label" for="is_custom">Produk custom (atur resep)</label>
                        </div>
                        <div class="form-text">Produk custom selalu dijual tanpa stok produk jadi; pilihan Produksi tetap mengikuti isian di atas.</div>
                        @error('is_custom') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('produk-kategori-utama.index') }}" class="btn btn-secondary">Kembali</a>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
