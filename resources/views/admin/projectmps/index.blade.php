@extends('layouts.app')

@section('title')
    Data Marketplace
@endsection

@section('content')
    <header class="header mb-4">
        <div class="container-fluid">
            <h5 class="card-title">Arsip Order Marketplace</h5>
        </div>
    </header>
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <form action="{{ route('projectmp.index') }}" method="get" class="w-100">
                        <div class="d-flex flex-column gap-2 w-100">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <label for="nota" class="form-label mb-0">Nota</label>
                                <input type="text" name="nota" class="form-control" value="{{ request('nota') }}">
                                <label for="pembayaran" class="form-label mb-0">Pembayaran</label>
                                <span class="text-muted">Belum</span>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="pembayaranToggle" {{ request('pembayaran') == '1' ? 'checked' : '' }}>
                                    <input type="hidden" name="pembayaran" value="{{ request('pembayaran') == '1' ? '1' : '0' }}" id="pembayaranValue">
                                    <label class="form-check-label" for="pembayaranToggle"></label>
                                </div>
                                <span class="text-muted">Sudah</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div id="autocompleteProduk" class="autocomplete">
                                    <input class="autocomplete-input produk {{ $errors->has('produk_id') ? 'invalid' : '' }}"
                                        placeholder="cari produk" aria-label="cari produk">
                                    <span id="closeBrgProduk"></span>
                                    <ul class="autocomplete-result-list"></ul>
                                    <input type="hidden" id="produkId" name="produk_id" value="{{ request('produk_id') }}">
                                </div>
                                <label for="tanggal" class="form-label mb-0">Dari</label>
                                <input type="date" name="dari" class="form-control" value="{{ request('dari') }}">
                                <label for="tanggal" class="form-label mb-0">Sampai</label>
                                <input type="date" name="sampai" class="form-control" value="{{ request('sampai') }}">
                                <button type="submit" class="btn btn-primary">Cari</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card-body">
                @include('layouts.includes.messages')
                {{ $projectmps->links() }}
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Nota</th>
                                <th>Order</th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => request('sort') == 'total_asc' ? 'total_desc' : 'total_asc']) }}"
                                       class="text-decoration-none text-dark">
                                        Total
                                        @if(request('sort') == 'total_asc')
                                            <i class="bx bx-sort-up"></i>
                                        @elseif(request('sort') == 'total_desc')
                                            <i class="bx bx-sort-down"></i>
                                        @else
                                            <i class="bx bx-sort"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => request('sort') == 'bersih_asc' ? 'bersih_desc' : 'bersih_asc']) }}"
                                       class="text-decoration-none text-dark">
                                        Bersih
                                        @if(request('sort') == 'bersih_asc')
                                            <i class="bx bx-sort-up"></i>
                                        @elseif(request('sort') == 'bersih_desc')
                                            <i class="bx bx-sort-down"></i>
                                        @else
                                            <i class="bx bx-sort"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => request('sort') == 'persentase_asc' ? 'persentase_desc' : 'persentase_asc']) }}"
                                       class="text-decoration-none text-dark">
                                        Persentase
                                        @if(request('sort') == 'persentase_asc')
                                            <i class="bx bx-sort-up"></i>
                                        @elseif(request('sort') == 'persentase_desc')
                                            <i class="bx bx-sort-down"></i>
                                        @else
                                            <i class="bx bx-sort"></i>
                                        @endif
                                    </a>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($projectmps as $projectmp)
                                <tr data-entry-id="{{ $projectmp->id }}">
                                    <td>{{ date('d-m-Y', strtotime($projectmp->created_at)) }}</td>
                                    <td>{{ $projectmp->nota }}</td>
                                    <td><a href="{{ route('projectmp.detail', $projectmp->id) }}">{{ $projectmp->listproduk }}</a></td>
                                    <td>{{ number_format($projectmp->total, 0, ',', '.') }}</td>
                                    <td>{{ number_format($projectmp->bersih, 0, ',', '.') }}</td>
                                    <td>{{ $projectmp->persen ?? 0 }}%</td>
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

        // Handle pembayaran toggle switch
        document.getElementById('pembayaranToggle').addEventListener('change', function() {
            const hiddenInput = document.getElementById('pembayaranValue');
            if (this.checked) {
                hiddenInput.value = '1';
            } else {
                hiddenInput.value = '0';
            }
        });

        // Load selected values after page load
        document.addEventListener('DOMContentLoaded', function() {

            // Load produk name if produk_id exists
            @if(request('produk_id'))
                fetch("{{ url('admin/produk/api?id=') }}" + "{{ request('produk_id') }}")
                    .then(response => response.json())
                    .then(data => {
                        if (data) {
                            const produkName = data.varian ? data.kategori + ' - ' + data.nama + ' - ' + data.varian : data.kategori + ' - ' + data.nama;
                            document.querySelector('#autocompleteProduk .autocomplete-input').value = produkName;
                            let btn = document.getElementById("closeBrgProduk");
                            btn.style.display = "block";
                            btn.innerHTML = `<button onclick="clearProduk()" type="button" class="btnClose btn-warning"><i class='bx bx-x-circle' ></i></button>`;
                        }
                    })
                    .catch(error => console.log('Error loading produk:', error));
            @endif

            // Set pembayaran toggle state
            const pembayaranToggle = document.getElementById('pembayaranToggle');
            const pembayaranValue = document.getElementById('pembayaranValue');

            @if(request('pembayaran') == '1')
                pembayaranToggle.checked = true;
                pembayaranValue.value = '1';
            @elseif(request('pembayaran') == '0')
                pembayaranToggle.checked = false;
                pembayaranValue.value = '0';
            @endif
        });
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

        /* Sorting styles */
        th a {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        th a:hover {
            color: #007bff !important;
        }

        th a i {
            font-size: 12px;
            opacity: 0.7;
        }

        th a:hover i {
            opacity: 1;
        }
    </style>
@endpush
