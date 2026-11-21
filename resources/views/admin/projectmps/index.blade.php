@extends('layouts.app')

@section('title')
    Data Marketplace
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header py-2">
                <form action="{{ route('projectmp.index') }}" method="get" class="w-100 projectmp-filters">
                    <div class="row gx-3 gy-2 align-items-center">
                        <div class="col-12 col-md-4 col-lg-3 projectmp-field">
                            <label for="marketplace_id" class="form-label">Marketplace</label>
                            <select name="marketplace_id" id="marketplace_id" class="form-select">
                                <option value="">Semua</option>
                                @foreach ($marketplaces as $mp)
                                    <option value="{{ $mp->id }}" {{ request('marketplace_id') == $mp->id ? 'selected' : '' }}>
                                        {{ $mp->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4 col-lg-3 projectmp-field">
                            <label for="nota" class="form-label">Nota</label>
                            <input type="text" id="nota" name="nota" class="form-control" value="{{ request('nota') }}">
                        </div>
                        <div class="col-12 col-md-4 col-lg-6 projectmp-field">
                            <label for="produkSearch" class="form-label">Produk</label>
                            <div id="autocompleteProduk" class="autocomplete">
                                <input id="produkSearch" class="autocomplete-input produk {{ $errors->has('produk_id') ? 'invalid' : '' }}"
                                    placeholder="Cari produk" aria-label="Cari produk">
                                <span id="closeBrgProduk"></span>
                                <ul class="autocomplete-result-list"></ul>
                                <input type="hidden" id="produkId" name="produk_id" value="{{ request('produk_id') }}">
                            </div>
                        </div>
                        <div class="col-12 col-lg-4 projectmp-field">
                            <label for="pembayaranToggle" class="form-label">Pembayaran</label>
                            <div class="d-flex align-items-center gap-2 projectmp-payment">
                                <span class="text-muted">Belum</span>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="pembayaranToggle" {{ request('pembayaran') == '1' ? 'checked' : '' }}>
                                    <input type="hidden" name="pembayaran" value="{{ request('pembayaran') == '1' ? '1' : '0' }}" id="pembayaranValue">
                                </div>
                                <span class="text-muted">Sudah</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3 projectmp-field">
                            <label for="dari" class="form-label">Dari</label>
                            <input type="date" id="dari" name="dari" class="form-control" value="{{ request('dari') }}">
                        </div>
                        <div class="col-12 col-md-6 col-lg-3 projectmp-field">
                            <label for="sampai" class="form-label">Sampai</label>
                            <input type="date" id="sampai" name="sampai" class="form-control" value="{{ request('sampai') }}">
                        </div>
                        <div class="col-12 col-lg-2">
                            <button type="submit" class="btn btn-primary w-100">Cari</button>
                        </div>
                    </div>
                </form>
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
                                <th>Marketplace</th>
                                <th>Order</th>
                                <th>
                                    <a href="{{ route('projectmp.index', array_merge(request()->query(), ['sort' => request('sort') == 'total_asc' ? 'total_desc' : 'total_asc'])) }}"
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
                                    <a href="{{ route('projectmp.index', array_merge(request()->query(), ['sort' => request('sort') == 'bersih_asc' ? 'bersih_desc' : 'bersih_asc'])) }}"
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
                                    <a href="{{ route('projectmp.index', array_merge(request()->query(), ['sort' => request('sort') == 'persentase_asc' ? 'persentase_desc' : 'persentase_asc'])) }}"
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
                                    <td>{{ $projectmp->marketplace->nama ?? '-' }}</td>
                                    <td><a class="popup" href="{{ route('projectmp.detail', $projectmp->id, false) }}">{{ $projectmp->listproduk }}</a></td>
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

    @include('admin.projectmps.partials.detail-projectmp-modal')
@endsection

@push('after-scripts')
    <script src="{{ asset('js/autocomplete.min.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('js/autocomplete.css') }}">
    <script>
        @include('admin.projectmps.partials.detail-projectmp-modal-js')

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
        .projectmp-filters .projectmp-field {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .projectmp-filters .projectmp-field .form-label {
            margin-bottom: 0;
            white-space: nowrap;
        }

        .projectmp-filters .projectmp-field .form-control,
        .projectmp-filters .projectmp-field .form-select,
        .projectmp-filters .projectmp-field .autocomplete {
            flex: 1;
            min-width: 0;
        }

        .projectmp-filters .projectmp-payment {
            min-height: 38px;
        }

        .projectmp-filters #autocompleteProduk {
            width: 100%;
            position: relative;
        }

        .projectmp-filters #closeBrgProduk {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 2;
        }

        .projectmp-filters .autocomplete-input {
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

        @include('admin.projectmps.partials.detail-projectmp-modal-styles')
    </style>
@endpush
