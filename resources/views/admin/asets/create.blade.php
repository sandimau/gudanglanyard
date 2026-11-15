@extends('layouts.app')

@section('title')
    Tambah Aset
@endsection

@section('content')
    <div class="container-fluid">
        @include('layouts.includes.messages')

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-0">Tambah Aset Tetap</h5>
                    <small class="text-muted">Tanggal beli otomatis memakai tanggal penyimpanan.</small>
                </div>
                <a href="{{ route('aset.index') }}" class="btn btn-secondary">Kembali</a>
            </div>
            <div class="card-body">
                <form action="{{ route('aset.store') }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Supplier</label>
                            <div id="autocompleteSupplier" class="autocomplete">
                                <input class="autocomplete-input {{ $errors->has('kontak_id') ? 'is-invalid' : '' }}"
                                    placeholder="Cari supplier" aria-label="Cari supplier">
                                <span id="closeSupplier"></span>
                                <ul class="autocomplete-result-list"></ul>
                                <input type="hidden" id="kontak_id" name="kontak_id" value="{{ old('kontak_id') }}">
                            </div>
                            @error('kontak_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Produk</label>
                            <div id="autocompleteProduk" class="autocomplete">
                                <input class="autocomplete-input {{ $errors->has('produk_id') ? 'is-invalid' : '' }}"
                                    placeholder="Cari produk" aria-label="Cari produk">
                                <span id="closeProduk"></span>
                                <ul class="autocomplete-result-list"></ul>
                                <input type="hidden" id="produk_id" name="produk_id" value="{{ old('produk_id') }}">
                            </div>
                            @error('produk_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Harga per Unit</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="harga" id="harga" min="1"
                                    class="form-control @error('harga') is-invalid @enderror"
                                    value="{{ old('harga') }}" required>
                            </div>
                            @error('harga')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Jumlah</label>
                            <input type="number" name="jumlah" id="jumlah" min="1"
                                class="form-control @error('jumlah') is-invalid @enderror"
                                value="{{ old('jumlah', 1) }}" required>
                            @error('jumlah')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Masa Penyusutan (tahun)</label>
                            <input type="number" name="waktuPenyusutan" id="waktuPenyusutan" min="1" max="100"
                                class="form-control @error('waktuPenyusutan') is-invalid @enderror"
                                value="{{ old('waktuPenyusutan', 1) }}" required>
                            @error('waktuPenyusutan')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Pembayaran</label>
                            <select name="pembayaran" id="pembayaran"
                                class="form-select @error('pembayaran') is-invalid @enderror" required>
                                <option value="tunai" {{ old('pembayaran', 'tunai') === 'tunai' ? 'selected' : '' }}>Tunai</option>
                                <option value="hutang" {{ old('pembayaran') === 'hutang' ? 'selected' : '' }}>Hutang</option>
                            </select>
                            @error('pembayaran')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6" id="kas-field">
                            <label class="form-label">Akun Kas</label>
                            <select name="akun_detail_id" id="akun_detail_id"
                                class="form-select @error('akun_detail_id') is-invalid @enderror">
                                <option value="">Pilih akun kas</option>
                                @foreach ($kas as $akun)
                                    <option value="{{ $akun->id }}" {{ old('akun_detail_id') == $akun->id ? 'selected' : '' }}>
                                        {{ $akun->nama }}
                                    </option>
                                @endforeach
                            </select>
                            @error('akun_detail_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Keterangan</label>
                            <textarea name="keterangan" rows="3"
                                class="form-control @error('keterangan') is-invalid @enderror"
                                placeholder="Keterangan tambahan (opsional)">{{ old('keterangan') }}</textarea>
                            @error('keterangan')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                <div class="d-flex justify-content-between">
                                    <span>Nilai awal aset (harga x jumlah)</span>
                                    <strong id="nilai-awal">Rp 0</strong>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <span>Penyusutan bulanan (garis lurus)</span>
                                    <strong id="penyusutan-bulanan">Rp 0</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Simpan Aset</button>
                        <a href="{{ route('aset.index') }}" class="btn btn-light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('after-scripts')
    <script src="{{ asset('js/autocomplete.min.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('js/autocomplete.css') }}">
    <script>
        const rupiah = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        });

        function updateNilai() {
            const harga = parseFloat(document.getElementById('harga').value || 0);
            const jumlah = parseFloat(document.getElementById('jumlah').value || 0);
            const waktu = parseFloat(document.getElementById('waktuPenyusutan').value || 0);

            const nilai = harga * jumlah;
            const penyusutan = waktu > 0 ? Math.round(nilai / (waktu * 12)) : 0;

            document.getElementById('nilai-awal').textContent = rupiah.format(nilai || 0);
            document.getElementById('penyusutan-bulanan').textContent = rupiah.format(penyusutan || 0);
        }

        function aturKas() {
            const pembayaran = document.getElementById('pembayaran').value;
            const field = document.getElementById('kas-field');
            const kas = document.getElementById('akun_detail_id');

            if (pembayaran === 'tunai') {
                field.classList.remove('d-none');
                kas.setAttribute('required', 'required');
            } else {
                field.classList.add('d-none');
                kas.removeAttribute('required');
            }
        }

        ['harga', 'jumlah', 'waktuPenyusutan'].forEach(function (id) {
            document.getElementById(id).addEventListener('input', updateNilai);
        });

        document.getElementById('pembayaran').addEventListener('change', aturKas);

        new Autocomplete('#autocompleteSupplier', {
            search: input => {
                const url = "{{ url('admin/aset-tetap/kontak/api?q=') }}" + `${escape(input)}`;

                return new Promise(resolve => {
                    if (input.length < 1) {
                        return resolve([]);
                    }

                    fetch(url).then(response => response.json()).then(data => resolve(data));
                });
            },
            getResultValue: result => result.perusahaan ? result.nama + ' - ' + result.perusahaan : result.nama,
            onSubmit: result => {
                document.getElementById('kontak_id').value = result.id;
                const btn = document.getElementById('closeSupplier');
                btn.style.display = 'block';
                btn.innerHTML = `<button onclick="clearSupplier()" type="button" class="btnClose btn-warning"><i class='bx bx-x-circle'></i></button>`;
            },
        });

        new Autocomplete('#autocompleteProduk', {
            search: input => {
                const url = "{{ url('admin/aset-tetap/produk/api?q=') }}" + `${escape(input)}`;

                return new Promise(resolve => {
                    if (input.length < 1) {
                        return resolve([]);
                    }

                    fetch(url).then(response => response.json()).then(data => resolve(data));
                });
            },
            getResultValue: result => result.varian
                ? result.kategori + ' - ' + result.nama + ' - ' + result.varian
                : result.kategori + ' - ' + result.nama,
            onSubmit: result => {
                document.getElementById('produk_id').value = result.id;

                const harga = document.getElementById('harga');
                if (result.harga) {
                    harga.value = result.harga;
                }

                const btn = document.getElementById('closeProduk');
                btn.style.display = 'block';
                btn.innerHTML = `<button onclick="clearProduk()" type="button" class="btnClose btn-warning"><i class='bx bx-x-circle'></i></button>`;

                updateNilai();
            },
        });

        function clearSupplier() {
            document.getElementById('kontak_id').value = '';
            document.querySelector('#autocompleteSupplier .autocomplete-input').value = '';
            document.getElementById('closeSupplier').style.display = 'none';
        }

        function clearProduk() {
            document.getElementById('produk_id').value = '';
            document.querySelector('#autocompleteProduk .autocomplete-input').value = '';
            document.getElementById('closeProduk').style.display = 'none';
        }

        aturKas();
        updateNilai();
    </script>
    <style>
        #autocompleteSupplier,
        #autocompleteProduk {
            max-width: 600px;
        }

        #closeSupplier,
        #closeProduk {
            position: relative;
        }

        #closeSupplier button,
        #closeProduk button {
            position: absolute;
            right: -15px;
            top: -40px;
        }

        .btnClose {
            padding: 4px 8px;
            border: 0;
            border-radius: 50px;
            background: #fdc54c;
        }

        .autocomplete-input.is-invalid,
        .autocomplete-input.invalid {
            border: solid 1px red;
        }

        .autocomplete-result-list {
            z-index: 50 !important;
        }
    </style>
@endpush
