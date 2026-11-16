@extends('layouts.app')

@section('title')
    Edit Produk Kategori Utama
@endsection

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Edit Kategori Utama</div>

                <div class="card-body">
                    <form action="{{ route('produk-kategori-utama.update', $produkKategoriUtama) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="nama" class="form-label">Nama</label>
                            <input type="text" class="form-control @error('nama') is-invalid @enderror" id="nama" name="nama" value="{{ old('nama', $produkKategoriUtama->nama) }}" required>
                            @error('nama')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <input type="hidden" name="jual" value="0">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="jual" name="jual" value="1" {{ old('jual', $produkKategoriUtama->jual) ? 'checked' : '' }}>
                                <label class="form-check-label" for="jual">Jual</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <input type="hidden" name="beli" value="0">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="beli" name="beli" value="1" {{ old('beli', $produkKategoriUtama->beli) ? 'checked' : '' }}>
                                <label class="form-check-label" for="beli">Beli</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <input type="hidden" name="stok" value="0">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="stok" name="stok" value="1" {{ old('stok', $produkKategoriUtama->stok) ? 'checked' : '' }}>
                                <label class="form-check-label" for="stok">Stok</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <input type="hidden" name="produksi" value="0">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="produksi" name="produksi" value="1" {{ old('produksi', $produkKategoriUtama->produksi) ? 'checked' : '' }}>
                                <label class="form-check-label" for="produksi">Produksi</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <input type="hidden" name="is_custom" value="0">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input @error('is_custom') is-invalid @enderror" id="is_custom" name="is_custom" value="1" @checked(old('is_custom', $produkKategoriUtama->is_custom))>
                                <label class="form-check-label" for="is_custom">Produk custom (atur resep)</label>
                            </div>
                            <div class="form-text">Produk custom selalu dijual tanpa stok produk jadi; pilihan Produksi tidak berubah kecuali Anda mengubahnya.</div>
                            @error('is_custom') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('produk-kategori-utama.index') }}" class="btn btn-secondary">Kembali</a>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
