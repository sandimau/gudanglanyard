@extends('layouts.app')

@section('title')
    Data Belum Lunas
@endsection

@section('content')
    <header class="header mb-4">
        <div class="container-fluid">
            <h5 class="card-title">Belum Lunas</h5>
        </div>
    </header>
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <form action="{{ route('order.unpaid') }}" method="get" class="w-100">
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
                @include('layouts.includes.messages')
                <div class="table-responsive">
                {{ $orders->links() }}
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>tanggal</th>
                                <th>nota</th>
                                <th>kontak</th>
                                <th>total tagihan</th>
                                <th>dp</th>
                                <th>kekurangan</th>
                                <th>ongkir</th>
                                <th>order</th>
                                <th>action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $item)
                                <tr>
                                    <td>{{ date('d-m-Y', strtotime($item->created_at)) }}</td>
                                    <td>{{ $item->nota }}</td>
                                    <td><a
                                            href="{{ route('kontaks.show', $item->kontak_id) }}">{{ $item->kontak->nama }}</a>
                                    </td>
                                    <td>{{ number_format($item->total, 0, ',', '.') }}</td>
                                    <td>{{ number_format($item->bayar, 0, ',', '.') }}</td>
                                    <td>{{ number_format($item->kekurangan, 0, ',', '.') }}</td>
                                    <td>{{ number_format($item->ongkir, 0, ',', '.') }}</td>
                                    <td>{{ $item->listproduk }}</td>
                                    <td>
                                        <a href="{{ route('order.bayar', $item->id) }}"
                                            class="btn btn-info btn-sm me-1 text-white"><i class='bx bx-dollar-circle'></i>
                                            bayar</a>
                                    </td>
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

        function clearData() {
            let btn = document.getElementById("closeBrg");
            btn.style.display = "none";
            let auto = document.querySelector(".autocomplete-input");
            auto.value = null;
            let idProduk = document.getElementById('kontakId');
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
