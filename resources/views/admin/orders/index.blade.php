@extends('layouts.app')

@section('title')
    Data Orders
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header py-2">
                <form action="{{ route('order.index') }}" method="get" class="w-100 order-filters">
                    <div class="row gx-3 gy-2 align-items-center">
                        <div class="col-12 col-md-4 col-lg-3 order-field">
                            <label for="nota" class="form-label">Nota</label>
                            <input type="text" id="nota" name="nota" class="form-control" value="{{ request('nota') }}">
                        </div>
                        <div class="col-12 col-md-4 col-lg-4 order-field">
                            <label for="kontakSearch" class="form-label">Konsumen</label>
                            <div id="autocomplete" class="autocomplete">
                                <input id="kontakSearch" class="autocomplete-input {{ $errors->has('kontak_id') ? 'is-invalid' : '' }}"
                                    placeholder="Cari kontak" aria-label="Cari kontak">
                                <span id="closeBrg"></span>
                                <ul class="autocomplete-result-list"></ul>
                                <input type="hidden" id="kontakId" name="kontak_id">
                            </div>
                        </div>
                        <div class="col-12 col-md-4 col-lg-5 order-field">
                            <label for="produkSearch" class="form-label">Produk</label>
                            <div id="autocompleteProduk" class="autocomplete">
                                <input id="produkSearch"
                                    class="autocomplete-input produk {{ $errors->has('produk_id') ? 'invalid' : '' }}"
                                    placeholder="Cari produk" aria-label="Cari produk">
                                <span id="closeBrgProduk"></span>
                                <ul class="autocomplete-result-list"></ul>
                                <input type="hidden" id="produkId" name="produk_id">
                            </div>
                        </div>
                        <div class="col-12 col-md-4 order-field">
                            <label for="dari" class="form-label">Dari</label>
                            <input type="date" id="dari" name="dari" class="form-control" value="{{ request('dari') }}">
                        </div>
                        <div class="col-12 col-md-4 order-field">
                            <label for="sampai" class="form-label">Sampai</label>
                            <input type="date" id="sampai" name="sampai" class="form-control" value="{{ request('sampai') }}">
                        </div>
                        <div class="col-12 col-md-4">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </div>
                </form>
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
                                            href="{{ route('order.detail', $order->id, false) }}">{{ $order->listproduk }}</a></td>
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
        .order-filters .order-field {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .order-filters .order-field .form-label {
            margin-bottom: 0;
            white-space: nowrap;
        }

        .order-filters .order-field .form-control,
        .order-filters .order-field .autocomplete {
            flex: 1;
            min-width: 0;
        }

        .order-filters #autocomplete,
        .order-filters #autocompleteProduk {
            width: 100%;
            position: relative;
        }

        .order-filters #closeBrg,
        .order-filters #closeBrgProduk {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 2;
        }

        .order-filters .autocomplete-input {
            width: 100%;
            height: 38px;
            padding: 6px 40px 6px 40px;
            background-position: 10px center;
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
