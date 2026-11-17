@extends('layouts.app')
@section('title', 'Resep Produk Custom')
@section('content')
<div class="card mb-3"><div class="card-body">
    <h4>Resep: {{ $produk->namaLengkap }}</h4>
    <p>Estimasi HPP per unit: <strong>Rp {{ number_format($produk->hpp, 0, ',', '.') }}</strong> (belum termasuk biaya sekali per order).</p>
    <p class="text-muted">Jumlah bahan dihitung per unit pesanan. Varian bahan yang memiliki banyak pilihan ditentukan saat pesanan dibuat (offline) atau dari SKU Shopee.</p>
    <table class="table table-striped"><thead><tr><th>Jenis</th><th>Komponen / Keterangan</th><th>Jumlah</th><th>Biaya</th><th>Aksi</th></tr></thead><tbody>
        @forelse($reseps as $baris)
            <tr><td>{{ $baris->jenis === 'bahan' ? 'Bahan' : 'Biaya internal' }}</td>
                <td>{{ $baris->jenis === 'bahan' ? $baris->komponenModel?->nama : $baris->keterangan }}</td>
                <td>{{ $baris->per_order ? 'Sekali per order' : $baris->jumlah }}</td>
                <td>
                    @if($baris->jenis === 'internal')
                        Rp {{ number_format($baris->biaya, 0, ',', '.') }}
                    @elseif($baris->komponenModel && $baris->komponenModel->produk->isNotEmpty())
                        Rp {{ number_format($baris->komponenModel->produk->avg('hpp'), 0, ',', '.') }}
                        @if($baris->komponenModel->produk->count() > 1)<small class="text-muted">(rata-rata varian)</small>@endif
                    @else
                        <span class="text-muted">Tidak ada varian aktif</span>
                    @endif
                </td>
                <td><div class="d-flex gap-1"><a class="btn btn-sm btn-outline-primary" href="{{ route('produkCustom.resep', ['produk' => $produk, 'edit' => $baris->id]) }}">Edit</a><form method="post" action="{{ route('produkCustom.resep.destroy', [$produk, $baris]) }}" onsubmit="return confirm('Hapus baris resep?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Hapus</button></form></div></td></tr>
        @empty <tr><td colspan="5">Belum ada resep.</td></tr> @endforelse
    </tbody></table>
</div></div>
<div class="card mb-3"><div class="card-body">
    <h5>{{ $editResep ? 'Edit baris resep' : 'Tambah baris resep' }}</h5>
    <form method="post" action="{{ $editResep ? route('produkCustom.resep.update', [$produk, $editResep]) : route('produkCustom.resep.store', $produk) }}" class="row g-2" id="formResepCustom">@csrf
        @if($editResep) @method('PUT') @endif
        <div class="col-md-2"><label for="jenisResep" class="form-label">Jenis</label><select class="form-select" name="jenis" id="jenisResep"><option value="bahan" @selected(old('jenis', $editResep?->jenis ?? 'bahan') === 'bahan')>Bahan</option><option value="internal" @selected(old('jenis', $editResep?->jenis) === 'internal')>Biaya internal</option></select></div>
        <div class="col-md-4" data-resep-jenis="bahan">
            <label for="komponenResep" class="form-label">Produk komponen (stok)</label>
            <div id="autocompleteKomponenResep" class="autocomplete position-relative" data-search-url="{{ route('produkCustom.resep.komponen', $produk) }}">
                <input id="komponenResep" class="autocomplete-input form-control @error('komponen_model_id') is-invalid @enderror" type="text" autocomplete="off" maxlength="100" placeholder="Cari nama, kategori, atau varian bahan" value="{{ $komponenPilihan ? $komponenPilihan->kategori?->nama . ' - ' . $komponenPilihan->nama : '' }}" required>
                <ul class="autocomplete-result-list"></ul>
                <input type="hidden" id="komponenModelId" name="komponen_model_id" value="{{ $komponenId }}">
            </div>
            @error('komponen_model_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-2" id="jumlahResepWrap"><label for="jumlahResep" class="form-label">Jumlah / unit</label><input class="form-control" name="jumlah" id="jumlahResep" type="number" min="0.01" step="0.01" value="{{ old('jumlah', $editResep?->jumlah ?? 1) }}" required></div>
        <div class="col-md-2" data-resep-jenis="internal"><label for="biayaResep" class="form-label">Biaya internal</label><input class="form-control" name="biaya" id="biayaResep" type="number" min="0" value="{{ old('biaya', $editResep?->biaya) }}"></div>
        <div class="col-md-4" data-resep-jenis="internal"><label for="keteranganResep" class="form-label">Keterangan</label><input class="form-control" name="keterangan" id="keteranganResep" maxlength="255" value="{{ old('keterangan', $editResep?->keterangan) }}"></div>
        <div class="col-12" data-resep-jenis="internal"><label><input type="checkbox" name="per_order" id="perOrderResep" value="1" @checked(old('per_order', $editResep?->per_order))> Biaya sekali per order</label></div>
        <div class="col-12"><button class="btn btn-primary">{{ $editResep ? 'Simpan perubahan' : 'Tambah' }}</button> @if($editResep)<a class="btn btn-outline-secondary" href="{{ route('produkCustom.resep', $produk) }}">Batal</a>@endif</div>
    </form>
    @if($errors->any())<div class="alert alert-danger mt-2">{{ $errors->first() }}</div>@endif
</div></div>
<div class="card"><div class="card-body"><h5>SKU untuk Shopee</h5>
    @forelse($skuOptions as $option)
        <div class="mb-2"><span>{{ $option['label'] ?: 'Tanpa pilihan varian' }}:</span> <code>{{ $option['sku'] }}</code>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="navigator.clipboard.writeText(@js($option['sku']))">Salin SKU</button></div>
    @empty <p>Belum ada kombinasi SKU.</p> @endforelse
    <small class="text-muted">Untuk paket, tambahkan _N setelah SKU (misalnya _2 untuk 2 unit).</small>
</div></div>
@endsection

@push('after-styles')
<link rel="stylesheet" href="{{ asset('js/autocomplete.css') }}">
<style>#autocompleteKomponenResep .autocomplete-input { width: 100%; }</style>
@endpush

@push('after-scripts')
<script src="{{ asset('js/autocomplete.min.js') }}"></script>
<script src="{{ asset('js/produk-custom-resep.js') }}"></script>
<script>window.initProdukCustomResep(document);</script>
@endpush
