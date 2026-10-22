@extends('layouts.app')

@section('title')
    Create Orders
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title">add order</h5>
                </div>
                <a href="{{ route('order.dashboard') }}" class="btn btn-success ">back</a>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('order.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group mb-3">
                    <label for="nama" class="mb-2">Konsumen</label>
                    <div id="autocomplete" class="autocomplete">
                        <input class="autocomplete-input {{ $errors->has('kontak_id') ? 'is-invalid' : '' }}"
                            placeholder="cari kontak" aria-label="cari kontak">
                        <span id="closeBrg"></span>
                        <ul class="autocomplete-result-list"></ul>
                        <input type="hidden" id="kontakId" name="kontak_id">
                    </div>
                    @if ($errors->has('kontak_id'))
                        <div class="invalid-feedback z-10">
                            {{ $errors->first('kontak_id') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="nama" class="mb-2">Produk</label>
                    <div id="autocompleteProduk" class="autocomplete">
                        <input class="autocomplete-input produk {{ $errors->has('produk_id') ? 'is-invalid' : '' }}"
                            placeholder="cari produk" aria-label="cari produk">
                        <span id="closeBrgProduk"></span>
                        <ul class="autocomplete-result-list"></ul>
                        <input type="hidden" id="produkId" name="produk_id">
                    </div>
                    @if ($errors->has('produk_id'))
                        <div class="invalid-feedback z-10">
                            {{ $errors->first('produk_id') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="nota">nota</label>
                    <input class="form-control" type="text" name="nota" id="nota" value="{{ old('nota', '') }}">
                </div>
                <div class="form-group mb-3">
                    <label for="tema">Tema</label>
                    <input class="form-control {{ $errors->has('tema') ? 'is-invalid' : '' }}" type="text" name="tema"
                        id="tema" value="{{ old('tema', '') }}">
                    @if ($errors->has('tema'))
                        <div class="invalid-feedback">
                            {{ $errors->first('tema') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="jumlah">Jumlah</label>
                    <input class="form-control {{ $errors->has('jumlah') ? 'is-invalid' : '' }}" type="number"
                        name="jumlah" id="jumlah" value="{{ old('jumlah', '') }}">
                    @if ($errors->has('jumlah'))
                        <div class="invalid-feedback">
                            {{ $errors->first('jumlah') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="harga">Harga</label>
                    <input class="form-control {{ $errors->has('harga') ? 'is-invalid' : '' }}" type="number"
                        name="harga" id="harga" value="{{ old('harga', '') }}">
                    @if ($errors->has('harga'))
                        <div class="invalid-feedback">
                            {{ $errors->first('harga') }}
                        </div>
                    @endif
                </div>
                @foreach ($speks as $item)
                    <div class="form-group mb-3">
                        <label for="spek">{{ $item->nama }}</label>
                        <input class="form-control" type="text" name="{{ $item->nama }}" id="spek">
                    </div>
                @endforeach
                <div class="form-group mb-3">
                    <label for="keterangan">Keterangan</label>
                    <textarea class="form-control {{ $errors->has('keterangan') ? 'is-invalid' : '' }}" name="keterangan" id=""
                        cols="30" rows="10">{{ old('keterangan', '') }}</textarea>
                    @if ($errors->has('keterangan'))
                        <div class="invalid-feedback">
                            {{ $errors->first('keterangan') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="deathline">Deathline</label>
                    <input class="form-control {{ $errors->has('deathline') ? 'is-invalid' : '' }}" type="date"
                        name="deathline" id="deathline" value="{{ old('deathline', date('Y-m-d')) }}">
                    @if ($errors->has('deathline'))
                        <div class="invalid-feedback">
                            {{ $errors->first('deathline') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <div class="row">
                        <label for="harga">Pengiriman</label>
                        <div class="row">
                            <div class="col-lg-3 col-sm-12">
                                <div class="form-check ms-3">
                                    <input class="form-check-input" type="radio" name="pengiriman"
                                        id="flexRadioDefault" value="diambil">
                                    <label class="form-check-label" for="flexRadioDefault">
                                        diambil
                                    </label>
                                </div>
                            </div>
                            <div class="col-lg-3 col-sm-12">
                                <div class="form-check ms-3">
                                    <input class="form-check-input" type="radio" name="pengiriman"
                                        id="flexRadioDefault2" value="dikirim">
                                    <label class="form-check-label" for="flexRadioDefault2">
                                        dikirim
                                    </label>
                                </div>
                            </div>
                            <div class="col-lg-3 col-sm-12">
                                <div class="form-check ms-3">
                                    <input class="form-check-input" type="radio" name="pengiriman"
                                        id="flexRadioDefault3" value="jasa pengiriman">
                                    <label class="form-check-label" for="flexRadioDefault3">
                                        jasa pengiriman
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <div class="row">
                        <label for="harga">Invoice</label>
                        <div class="row">
                            <div class="col-sm-12 col-lg-3">
                                <div class="form-check me-1">
                                    <input class="form-check-input" type="radio" name="invoice" id="invoice"
                                        value="disertakan barang">
                                    <label class="form-check-label" for="invoice">
                                        disertakan barang
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-12 col-lg-3">
                                <div class="form-check me-1">
                                    <input class="form-check-input" type="radio" name="invoice" id="invoice2"
                                        value="dikirim terpisah">
                                    <label class="form-check-label" for="invoice2">
                                        dikirim terpisah
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-12 col-lg-2">
                                <div class="form-check me-1">
                                    <input class="form-check-input" type="radio" name="invoice" id="invoice3"
                                        value="diemail">
                                    <label class="form-check-label" for="invoice3">
                                        diemail
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-12 col-lg-2">
                                <div class="form-check me-1">
                                    <input class="form-check-input" type="radio" name="invoice" id="invoice4"
                                        value="tidak pakai">
                                    <label class="form-check-label" for="invoice4">
                                        tidak pakai
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <div class="row">
                        <label for="harga">Pembayaran</label>
                        <div class="d-flex">
                            <div class="form-check me-2">
                                <input class="form-check-input" type="radio" name="jenis_pembayaran" id="pembayaran"
                                    value="cod">
                                <label class="form-check-label" for="pembayaran">
                                    cod
                                </label>
                            </div>
                            <div class="form-check me-2">
                                <input class="form-check-input" type="radio" name="jenis_pembayaran" id="pembayaran2"
                                    value="transfer">
                                <label class="form-check-label" for="pembayaran2">
                                    transfer
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label for="jasa">jasa pengiriman</label>
                    <input class="form-control {{ $errors->has('jasa') ? 'is-invalid' : '' }}" type="text"
                        name="jasa" id="jasa" value="{{ old('jasa', '') }}">
                    @if ($errors->has('jasa'))
                        <div class="invalid-feedback">
                            {{ $errors->first('jasa') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="ongkir">ongkir</label>
                    <input class="form-control {{ $errors->has('ongkir') ? 'is-invalid' : '' }}" type="number"
                        name="ongkir" id="ongkir" value="{{ old('ongkir', '') }}">
                    @if ($errors->has('ongkir'))
                        <div class="invalid-feedback">
                            {{ $errors->first('ongkir') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="ket_kirim">ket ongkir</label>
                    <input class="form-control {{ $errors->has('ket_kirim') ? 'is-invalid' : '' }}" type="text"
                        name="ket_kirim" id="ket_kirim" value="{{ old('ket_kirim', '') }}">
                    @if ($errors->has('ket_kirim'))
                        <div class="invalid-feedback">
                            {{ $errors->first('ket_kirim') }}
                        </div>
                    @endif
                </div>
                <div class="form-group">
                    <button class="btn btn-primary mt-4" type="submit">
                        save
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('after-scripts')
<script src="{{ asset('js/autocomplete.min.js') }}"></script>
<link rel="stylesheet" href="{{ asset('js/autocomplete.css') }}">
    <script>
        new Autocomplete('#autocomplete', {
            search: input => {
                const url = "{{ url('admin/konsumen/api?q=') }}" + `${escape(input)}`;
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
            getResultValue: result => result.perusahaan ? result.nama + ' - ' + result.perusahaan : result.nama,
            onSubmit: result => {
                let kontak = document.getElementById('kontakId');
                if (kontak.value !== String(result.id)) {
                    clearProduk();
                }
                kontak.value = result.id;

                let btn = document.getElementById("closeBrg");
                btn.style.display = "block";
                btn.innerHTML =
                    `<button onclick="clearData()" type="button" class="btnClose btn-warning"><i class='bx bx-x-circle' ></i></button>`;

            },
        })

        new Autocomplete('#autocompleteProduk', {
            search: input => {
                const kontakId = document.getElementById('kontakId').value;
                const url = "{{ url('admin/produk/api') }}?q=" + encodeURIComponent(input)
                    + "&stok_tersedia=1"
                    + (kontakId ? "&kontak_id=" + encodeURIComponent(kontakId) : "");
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
                let idProduk = document.getElementById('produkId');
                idProduk.value = result.id;

                //set harga
                let harga = document.getElementById("harga");
                harga.value = result.harga;

                let btn = document.getElementById("closeBrgProduk");
                btn.style.display = "block";
                btn.innerHTML =
                    `<button onclick="clearProduk()" type="button" class="btnClose btn-warning"><i class='bx bx-x-circle' ></i></button>`;
            },
        })

        function clearData() {
            clearProduk();
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
            document.getElementById('harga').value = null;
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
            width: 600px !important;
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
