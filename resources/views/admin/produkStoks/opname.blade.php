@extends('layouts.app')

@section('title')
    Data produk stoks
@endsection

@section('content')
    <header class="header mb-4">
        <div class="container-fluid">
            <h5 class="card-title">Stok Opname</h5>
        </div>
    </header>
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <form action="{{ route('opnames.index') }}" method="get" class="d-flex gap-2 align-items-center">
                        <label for="nota" class="form-label mb-0">Produk</label>
                        <div id="autocomplete" class="autocomplete">
                            <input class="autocomplete-input {{ $errors->has('produk_id') ? 'is-invalid' : '' }}"
                                placeholder="cari produk" aria-label="cari produk">
                            <span id="closeBrg"></span>
                            <ul class="autocomplete-result-list"></ul>
                            <input type="hidden" id="produkId" name="produk_id">
                        </div>
                        <label for="tanggal" class="form-label mb-0">Dari</label>
                        <input type="date" name="dari" class="form-control" value="{{ $dari ?? date('Y-m-d') }}">
                        <label for="tanggal" class="form-label mb-0">Sampai</label>
                        <input type="date" name="sampai" class="form-control" value="{{ $sampai ?? date('Y-m-d') }}">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                @include('layouts.includes.messages')
                {{ $produkStoks->links() }}
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Produk</th>
                                <th>tambah</th>
                                <th>kurang</th>
                                <th>saldo</th>
                                <th>Keterangan</th>
                                <th>user</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($produkStoks as $stok)
                                <tr>
                                    <td>{{ $stok->created_at->format('d-m-Y') }}</td>
                                    <td>{{ $stok->produk->namaLengkap }}</td>
                                    <td>{{ $stok->tambah }}</td>
                                    <td>{{ $stok->kurang }}</td>
                                    <td>{{ $stok->saldo }}</td>
                                    <td>{{ $stok->keterangan }}</td>
                                    <td>{{ $stok->user ? $stok->user->name : null }}</td>
                                </tr>
                            @endforeach
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
        new Autocomplete('#autocomplete', {
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
            getResultValue: result => result.varian ? result.kategori + ' - ' + result.nama + ' - ' + result.varian : result.kategori + ' - ' + result.nama,
            onSubmit: result => {
                let produk = document.getElementById('produkId');
                produk.value = result.id;

                let btn = document.getElementById("closeBrg");
                btn.style.display = "block";
                btn.innerHTML =
                    `<button onclick="clearData()" type="button" class="btnClose btn-warning"><i class='bx bx-x-circle' ></i></button>`;

            },
        })

        function clearData() {
            let btn = document.getElementById("closeBrg");
            btn.style.display = "none";
            let auto = document.querySelector(".autocomplete-input");
            auto.value = null;
            let idProduk = document.getElementById('produkId');
            idProduk.value = null;
        }
    </script>
    <style>
        #autocomplete,
        #autocompleteProduk {
            max-width: 600px;
        }

        #closeBrg,
        #closeBrgProduk {
            position: relative;
        }

        #closeBrg button,
        #closeBrgProduk button {
            position: absolute;
            right: -15px;
            top: -40px;
        }

        .autocomplete-input {
            width: 300px !important;
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
