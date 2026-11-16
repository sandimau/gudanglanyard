<form action="{{ route('produkModel.store', ['kategori_id' => $kategori->id]) }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="mb-3">
        <label class="form-label">Nama Produk</label>
        <input type="text" class="form-control @error('nama') is-invalid @enderror" name="nama" value="{{ old('nama') }}" required>
        @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
        <label class="form-label">Harga</label>
        <input type="number" class="form-control @error('harga') is-invalid @enderror" name="harga" value="{{ old('harga') }}" required>
        @error('harga') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-3">
        <label class="form-label">Deskripsi</label>
        <textarea class="form-control" name="deskripsi">{{ old('deskripsi') }}</textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">Satuan</label>
        <select class="form-control" name="satuan">
            @foreach ($satuan as $item)
                <option value="{{ $item }}" {{ old('satuan') === $item ? 'selected' : '' }}>{{ $item }}</option>
            @endforeach
        </select>
    </div>
    <input type="hidden" name="kategori_id" value="{{ $kategori->id }}">
    <div class="mb-3">
        <label class="form-label">Gambar</label>
        <input type="file" class="form-control @error('gambar') is-invalid @enderror" name="gambar" accept="image/jpeg,image/png">
        @error('gambar') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="d-flex justify-content-end">
        @if (!empty($inModal))
            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Batal</button>
        @else
            <a href="{{ route('produkModel.index', ['kategori_id' => $kategori->id]) }}" class="btn btn-secondary me-2">Batal</a>
        @endif
        <button type="submit" class="btn btn-primary">Simpan</button>
    </div>
</form>
