@extends('layouts.app')

@section('title')
    Data Belanja
@endsection

@section('content')
    <header class="header mb-4">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Belanja</h5>
            <div class="d-flex gap-2">
                @can('member_create')
                    <a href="{{ route('belanja.create') }}" class="btn btn-primary"><i class='bx bx-plus-circle'></i> tambah</a>
                @endcan
            </div>
        </div>
    </header>
    <div class="bg-light rounded">
        @include('layouts.includes.messages')
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <form action="{{ route('belanja.index') }}" method="get" class="w-100">
                        <div class="d-flex gap-2 align-items-center mb-2">
                            <label for="nota" class="form-label mb-0">Nota</label>
                            <input type="text" name="nota" class="form-control">
                            <label for="nota" class="form-label mb-0">Supplier</label>
                            <div id="autocomplete" class="autocomplete">
                                <input class="autocomplete-input {{ $errors->has('kontak_id') ? 'is-invalid' : '' }}"
                                    placeholder="cari kontak" aria-label="cari kontak">
                                <span id="closeBrg"></span>
                                <ul class="autocomplete-result-list"></ul>
                                <input type="hidden" id="kontakId" name="kontak_id">
                            </div>
                            <label for="produk" class="form-label mb-0">Produk</label>
                            <div id="autocompleteProduk" class="autocomplete">
                                <input class="autocomplete-input {{ $errors->has('produk_id') ? 'is-invalid' : '' }}"
                                    placeholder="cari produk" aria-label="cari produk">
                                <span id="closeProduk"></span>
                                <ul class="autocomplete-result-list"></ul>
                                <input type="hidden" id="produkId" name="produk_id">
                            </div>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <label for="tanggal" class="form-label mb-0">Dari</label>
                            <input type="date" name="dari" class="form-control">
                            <label for="tanggal" class="form-label mb-0">Sampai</label>
                            <input type="date" name="sampai" class="form-control">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card-body">
                {{ $belanjas->links() }}
                <div class="table-responsive">
                    <table class=" table table-bordered table-striped table-hover mt-3">
                        <thead>
                            <tr>
                                <th>gambar</th>
                                <th>tanggal</th>
                                <th>nota</th>
                                <th>supplier</th>
                                <th>produk</th>
                                <th>total</th>
                                @can('belanja_delete')
                                    <th>action</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($belanjas as $belanja)
                                <tr data-entry-id="{{ $belanja->id }}">
                                    <td>
                                        @if ($belanja->gambar)
                                            <a href="{{ asset('uploads/belanja/' . $belanja->gambar) }}" target="_blank">
                                                <img src="{{ asset('uploads/belanja/' . $belanja->gambar) }}" alt="gambar"
                                                    style="width: 100px; height: auto; cursor: zoom-in;">
                                            </a>
                                        @else
                                            <span>-</span>
                                        @endif
                                    </td>
                                    <td>{{ date('d-m-Y', strtotime($belanja->created_at)) }}</td>
                                    <td>{{ $belanja->nota }}</td>
                                    <td>{{ $belanja->kontak->nama }}</td>
                                    <td><a href="{{ route('belanja.detail', $belanja->id) }}">{{ $belanja->produk }}</a>
                                    </td>
                                    <td>{{ number_format($belanja->total, 0, ',', '.') }}</td>
                                    @can('belanja_delete')
                                        <td>
                                            <div class="d-flex gap-1">
                                                <form action="{{ route('belanja.destroy', $belanja->id) }}" method="post"
                                                    onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm"><i
                                                            class='bx bx-trash'></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    @endcan
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
                const url = "{{ url('admin/supplier/api?q=') }}" + `${escape(input)}`;
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
            getResultValue: result => result.nama,
            onSubmit: result => {
                let kontak = document.getElementById('kontakId');
                kontak.value = result.id;

                let btn = document.getElementById("closeBrg");
                btn.style.display = "block";
                btn.innerHTML =
                    `<button onclick="clearData()" type="button" class="btnClose btn-warning"><i class='bx bx-x-circle' ></i></button>`;

            },
        })

        new Autocomplete('#autocompleteProduk', {
            search: input => {
                const url = "{{ url('admin/produkBeli/api?q=') }}" + `${escape(input)}`;
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

        function clearData() {
            let btn = document.getElementById("closeBrg");
            btn.style.display = "none";
            let auto = document.querySelector(".autocomplete-input");
            auto.value = null;
            let idProduk = document.getElementById('kontakId');
            idProduk.value = null;
        }

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
