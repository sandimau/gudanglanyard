@extends('layouts.app')

@section('title', 'Catat Produk Gagal')

@section('content')
    <div class="container-fluid">
        <div class="card shadow-sm">
            <div class="card-header"><h5 class="mb-0">Catat Produk Gagal</h5></div>
            <div class="card-body">
                @include('layouts.includes.errors')
                <form action="{{ route('gagalProduksi.store') }}" method="post">
                    @csrf
                    <div class="mb-3">
                        <label for="cari_produk" class="form-label">Produk yang gagal</label>
                        <div id="autocompleteProdukGagal" class="autocomplete position-relative">
                            <input type="search" id="cari_produk" name="nama_produk" class="autocomplete-input form-control"
                                value="{{ old('nama_produk') }}" placeholder="Ketik nama produk, lalu pilih dari daftar" autocomplete="off" required>
                            <ul class="autocomplete-result-list"></ul>
                        </div>
                        <input type="hidden" id="produk_id" name="produk_id" value="{{ old('produk_id') }}">
                        <small class="text-muted">Bisa memilih produk apa pun dari master produk, tanpa mencari nota order.</small>
                    </div>
                    <div class="mb-3">
                        <label for="tanggal" class="form-label">Tanggal kejadian</label>
                        <input type="date" id="tanggal" name="tanggal" class="form-control"
                            value="{{ old('tanggal', now()->toDateString()) }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="tahap" class="form-label">Tahap proses (opsional)</label>
                        <input type="text" id="tahap" name="tahap" class="form-control" maxlength="255"
                            value="{{ old('tahap') }}" placeholder="Contoh: cetak / jahit">
                    </div>
                    <div class="mb-3">
                        <label for="jumlah" class="form-label">Jumlah gagal</label>
                        <input type="number" id="jumlah" name="jumlah" class="form-control" min="1" max="1000000000"
                            step="1" value="{{ old('jumlah') }}" required>
                    </div>
                    <p class="text-muted small">Kerugian dihitung otomatis: jumlah gagal × HPP produk saat pencatatan. Jika HPP produk belum tersedia, kerugian akan tercatat Rp 0.</p>
                    <div class="mb-3">
                        <label for="keterangan" class="form-label">Penyebab / keterangan (opsional)</label>
                        <textarea id="keterangan" name="keterangan" class="form-control" rows="3" maxlength="2000">{{ old('keterangan') }}</textarea>
                    </div>
                    <p class="text-muted small">Pencatatan ini tidak mengubah jumlah pesanan, status, atau stok.</p>
                    <button type="submit" class="btn btn-primary">Simpan catatan</button>
                    <a href="{{ route('gagalProduksi.index') }}" class="btn btn-secondary">Batal</a>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('after-scripts')
    <link rel="stylesheet" href="{{ asset('js/autocomplete.css') }}">
    <script src="{{ asset('js/autocomplete.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('cari_produk');
            const produkId = document.getElementById('produk_id');
            let dipilih = input.value;

            input.addEventListener('input', function () {
                if (input.value !== dipilih) produkId.value = '';
            });

            new Autocomplete('#autocompleteProdukGagal', {
                search: function (term) {
                    if (!term.trim()) return Promise.resolve([]);
                    return fetch(@json(route('gagalProduksi.cariProduk')) + '?q=' + encodeURIComponent(term))
                        .then(response => response.json()).catch(() => []);
                },
                getResultValue: result => result.label,
                onSubmit: function (result) {
                    produkId.value = result.id;
                    dipilih = result.label;
                },
            });
        });
    </script>
@endpush
