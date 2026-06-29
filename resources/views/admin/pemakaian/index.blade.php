@extends('layouts.app')

@section('title')
    Data Pemakaian
@endsection

@section('content')
    <header class="header mb-4">
        <div class="container-fluid">
            <h5 class="card-title">Data Pemakaian</h5>
            @can('pakaiStok_create')
                <a href="{{ route('pemakaian.create') }}" class="btn btn-primary">Tambah Pemakaian</a>
            @endcan
        </div>
    </header>
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <form action="{{ route('pemakaian.index') }}" method="get" class="w-100">
                        <div class="d-flex gap-2 align-items-center mb-2">
                            <div id="autocompleteProduk" class="autocomplete">
                                <input class="autocomplete-input produk {{ $errors->has('produk_id') ? 'invalid' : '' }}"
                                    placeholder="cari produk" aria-label="cari produk"
                                    value="{{ old('produk_nama', request()->filled('produk_id') && isset($pemakaians->first()->produk->nama_lengkap) ? $pemakaians->first()->produk->nama_lengkap : '') }}">
                                <span id="closeBrgProduk"
                                    style="{{ request('produk_id') ? 'display:block;' : 'display:none;' }}"></span>
                                <ul class="autocomplete-result-list"></ul>
                                <input type="hidden" id="produkId" name="produk_id" value="{{ request('produk_id') }}">
                            </div>
                            <label for="tanggal_dari" class="form-label mb-0">Dari</label>
                            <input type="date" id="tanggal_dari" name="dari" class="form-control"
                                value="{{ request('dari') }}">
                            <label for="tanggal_sampai" class="form-label mb-0">Sampai</label>
                            <input type="date" id="tanggal_sampai" name="sampai" class="form-control"
                                value="{{ request('sampai') }}">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="{{ route('pemakaian.index') }}" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card-body">
                @include('layouts.includes.messages')
                {{ $pemakaians->links() }}
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Produk</th>
                                <th>Jumlah</th>
                                <th>Keterangan</th>
                                <th>User</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pemakaians as $pemakaian)
                                <tr data-entry-id="{{ $pemakaian->id }}">
                                    <td>{{ date('d-m-Y', strtotime($pemakaian->created_at)) }}</td>
                                    <td>{{ $pemakaian->produk->nama_lengkap ?? '-' }}</td>
                                    <td>{{ number_format($pemakaian->jumlah, 0, ',', '.') }}</td>
                                    <td>{{ $pemakaian->keterangan ?? '-' }}</td>
                                    <td>{{ $pemakaian->user->name ?? '-' }}</td>
                                    <td>
                                        @can('pakaiStok_Edit')
                                            @if ($pemakaian->produkStok->status != 'manual')
                                                <a href="{{ route('pemakaian.edit', $pemakaian->id) }}"
                                                    class="btn btn-sm btn-danger text-white">Balikin</a>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">Tidak ada data pemakaian</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('after-scripts')
    <script src="{{ asset('js/autocomplete.min.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('js/autocomplete.css') }}">
    <script>
        new Autocomplete('#autocompleteProduk', {
            search: input => {
                const url = "{{ url('admin/produk/api?q=') }}" + `${escape(input)}`;
                return new Promise(resolve => {
                    if (input.length < 1) {
                        return resolve([])
                    }

                    fetch(url)
                        .then(response => response.json())
                        .then(data => {
                            resolve(data);
                        })
                })
            },
            getResultValue: result => result.varian ? result.kategori + ' - ' + result.nama + ' - ' + result
                .varian : result.kategori + ' - ' + result.nama,
            onSubmit: result => {
                let idProduk = document.getElementById('produkId');
                idProduk.value = result.id;

                let btn = document.getElementById("closeBrgProduk");
                btn.style.display = "block";
                btn.innerHTML =
                    `<button onclick="clearProduk()" type="button" class="btnClose btn-warning"><i class='bx bx-x-circle' ></i></button>`;
            },
        })

        function clearProduk() {
            let btn = document.getElementById("closeBrgProduk");
            btn.style.display = "none";
            let auto = document.querySelector(".produk");
            auto.value = null;
            let idProduk = document.getElementById('produkId');
            idProduk.value = null;
        }
    </script>
    <style>

        #closeBrgProduk {
            position: relative;
        }

        #closeBrgProduk button {
            position: absolute;
            right: -15px;
            top: -40px;
        }

        .autocomplete-input {
            width: 400px !important;
            margin-right: 10px;
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
    </style>
@endpush
