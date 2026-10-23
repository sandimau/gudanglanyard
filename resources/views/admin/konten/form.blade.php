@extends('layouts.app')

@section('title', $konten->exists ? 'Edit Konten' : 'Tambah Konten')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="mb-0">{{ $konten->exists ? 'Edit Konten' : 'Tambah Konten' }}</h4>
        <a href="{{ route('konten.jadwal', ['bulan' => $konten->tanggal?->format('Y-m') ?? now()->format('Y-m')]) }}"
            class="btn btn-outline-secondary btn-sm">Kembali ke Jadwal</a>
    </div>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ $konten->exists ? route('konten.update', $konten) : route('konten.store') }}">
                @csrf
                @if ($konten->exists) @method('PATCH') @endif
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="tanggal">Tanggal</label>
                        <input type="date" id="tanggal" name="tanggal" required class="form-control @error('tanggal') is-invalid @enderror"
                            value="{{ old('tanggal', $konten->tanggal?->format('Y-m-d')) }}">
                        @error('tanggal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="topik">Topik</label>
                        <input type="text" id="topik" name="topik" required maxlength="255"
                            class="form-control @error('topik') is-invalid @enderror" value="{{ old('topik', $konten->topik) }}">
                        @error('topik') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="platform">Platform</label>
                        <input type="text" id="platform" name="platform" required maxlength="255" placeholder="Contoh: Instagram, TikTok"
                            class="form-control @error('platform') is-invalid @enderror" value="{{ old('platform', $konten->platform) }}">
                        @error('platform') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="waktu_posting">Waktu Posting</label>
                        <input type="time" id="waktu_posting" name="waktu_posting" required
                            class="form-control @error('waktu_posting') is-invalid @enderror"
                            value="{{ old('waktu_posting', $konten->waktu_posting ? substr($konten->waktu_posting, 0, 5) : '') }}">
                        @error('waktu_posting') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="tujuan">Tujuan</label>
                        <textarea id="tujuan" name="tujuan" rows="4" required class="form-control @error('tujuan') is-invalid @enderror">{{ old('tujuan', $konten->tujuan) }}</textarea>
                        @error('tujuan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    @if ($konten->exists)
                        <a href="{{ route('konten.proses') }}" class="btn btn-outline-secondary">Lihat Proses Konten</a>
                    @endif
                </div>
            </form>
            @if ($konten->exists)
                <hr>
                <form method="POST" action="{{ route('konten.destroy', $konten) }}" onsubmit="return confirm('Hapus jadwal konten ini?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">Hapus Konten</button>
                </form>
            @endif
        </div>
    </div>
@endsection
