@extends('layouts.app')

@section('title', $desain->exists ? 'Edit Desain' : 'Tambah Desain')

@section('content')
    @php
        $produkId = old('produk_id', $desain->produk_id);
        $tipeTerpilih = old('tipe', $desain->tipe);
        $produkTerpilih = $produkId ? \App\Models\Produk::with('produkModel.kategori')->find($produkId) : null;
        $labelProduk = $produkTerpilih
            ? collect([
                $produkTerpilih->produkModel?->kategori?->nama,
                $produkTerpilih->produkModel?->nama,
                $produkTerpilih->nama,
            ])->filter()->implode(' - ')
            : '';
    @endphp
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="mb-0">{{ $desain->exists ? 'Edit Desain' : 'Tambah Desain' }}</h4>
        <a href="{{ route('desain.index') }}" class="btn btn-outline-secondary btn-sm">Kembali ke Daftar</a>
    </div>
    @include('layouts.includes.messages')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ $desain->exists ? route('desain.update', $desain) : route('desain.store') }}">
                @csrf
                @if ($desain->exists) @method('PATCH') @endif
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="nama_konsumen">Nama Konsumen</label>
                        <input type="text" id="nama_konsumen" name="nama_konsumen" required maxlength="255"
                            class="form-control @error('nama_konsumen') is-invalid @enderror"
                            value="{{ old('nama_konsumen', $desain->nama_konsumen) }}">
                        @error('nama_konsumen') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="desain-produk-input">Produk</label>
                        @include('admin.desain.partials.produk-autocomplete', [
                            'containerId' => 'desain-produk',
                            'inputId' => 'desain-produk-input',
                            'hiddenId' => 'desain-produk-id',
                            'valueId' => $produkId,
                            'valueLabel' => $labelProduk,
                            'invalid' => $errors->has('produk_id'),
                        ])
                        @error('produk_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label d-block">Tipe Desain</label>
                        <div class="form-check form-check-inline">
                            <input type="radio" id="tipe-offline" name="tipe" value="offline" required
                                class="form-check-input @error('tipe') is-invalid @enderror"
                                {{ $tipeTerpilih === 'offline' ? 'checked' : '' }}>
                            <label class="form-check-label" for="tipe-offline">Offline</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input type="radio" id="tipe-online" name="tipe" value="online" required
                                class="form-check-input @error('tipe') is-invalid @enderror"
                                {{ $tipeTerpilih === 'online' ? 'checked' : '' }}>
                            <label class="form-check-label" for="tipe-online">Online</label>
                        </div>
                        @error('tipe') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="tema">Tema</label>
                        <input type="text" id="tema" name="tema" maxlength="255"
                            class="form-control @error('tema') is-invalid @enderror"
                            value="{{ old('tema', $desain->tema) }}">
                        @error('tema') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="keterangan">Keterangan</label>
                        <textarea id="keterangan" name="keterangan" rows="4"
                            class="form-control @error('keterangan') is-invalid @enderror">{{ old('keterangan', $desain->keterangan) }}</textarea>
                        @error('keterangan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    @if ($desain->exists)
                        <a href="{{ route('desain.proses') }}" class="btn btn-outline-secondary">Lihat Proses Desain</a>
                    @endif
                </div>
            </form>
            @if ($desain->exists)
                <hr>
                <form method="POST" action="{{ route('desain.destroy', $desain) }}" onsubmit="return confirm('Hapus desain ini?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">Hapus Desain</button>
                </form>
            @endif
        </div>
    </div>
    @include('admin.desain.partials.produk-autocomplete-script')
@endsection
