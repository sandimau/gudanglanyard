@extends('layouts.app')

@section('title')
    Data Orders
@endsection

@section('content')
    <header class="header mb-4">
        <div class="container-fluid">
            <h5 class="card-title">Orders Offline</h5>
        </div>
    </header>
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <form action="{{ route('order.index') }}" method="get" class="w-100">
                        <div class="d-flex flex-column gap-2 w-100">
                            <div class="d-flex gap-2 align-items-center mb-2">
                                <label for="nota" class="form-label mb-0">Nota</label>
                                <input type="text" name="nota" class="form-control">
                                <label for="nota" class="form-label mb-0">Konsumen</label>
                                <div id="autocomplete" class="autocomplete">
                                    <input class="autocomplete-input {{ $errors->has('kontak_id') ? 'is-invalid' : '' }}"
                                        placeholder="cari kontak" aria-label="cari kontak">
                                    <span id="closeBrg"></span>
                                    <ul class="autocomplete-result-list"></ul>
                                    <input type="hidden" id="kontakId" name="kontak_id">
                                </div>
                                <div id="autocompleteProduk" class="autocomplete">
                                    <input
                                        class="autocomplete-input produk {{ $errors->has('produk_id') ? 'invalid' : '' }}"
                                        placeholder="cari produk" aria-label="cari produk">
                                    <span id="closeBrgProduk"></span>
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
                        </div>
                    </form>
                </div>
            </div>
            <div class="card-body">
                @include('layouts.includes.messages')
                {{ $orders->links() }}
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Nota</th>
                                <th>Konsumen</th>
                                <th>Order</th>
                                <th>Total</th>
                                <th>Kekurangan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $order)
                                <tr data-entry-id="{{ $order->id }}">
                                    <td>{{ date('d-m-Y', strtotime($order->created_at)) }}</td>
                                    <td>{{ $order->nota }}</td>
                                    <td>{{ $order->kontak->nama ?? '' }}</td>
                                    <td><a class="popup"
                                            href="{{ route('order.detail', $order->id) }}">{{ $order->listproduk }}</a></td>
                                    <td>{{ number_format($order->total, 0, ',', '.') }}</td>
                                    <td>{{ number_format($order->kekurangan, 0, ',', '.') }}</td>
                                    <td>
                                        @if ($order->bayar == 0 && $order->total > 0)
                                            <a href="{{ route('order.unpaid') }}"
                                                class="btn rounded-pill btn-danger btn-sm text-white">belum bayar</a>
                                        @endif
                                        @if ($order->bayar == 0 && $order->total == 0)
                                            <a href="{{ route('order.unpaid') }}"
                                                class="btn rounded-pill btn-danger btn-sm text-white">batal</a>
                                        @endif
                                        @if ($order->total > $order->bayar && $order->bayar > 0)
                                            <a href="{{ route('order.unpaid') }}"
                                                class="btn rounded-pill btn-warning btn-sm text-white">belum lunas</a>
                                        @endif
                                        @if ($order->total == $order->bayar && $order->bayar != 0 && $order->total != 0)
                                            <button class="btn rounded-pill btn-success btn-sm text-white">lunas</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('admin.orders.partials.detail-order-modal')
@endsection

@push('after-scripts')
    <script src="{{ asset('js/autocomplete.min.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('js/autocomplete.css') }}">
    <script>
        @include('admin.orders.partials.detail-order-modal-js')

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

        @include('admin.orders.partials.detail-order-modal-styles')
    </style>
@endpush
