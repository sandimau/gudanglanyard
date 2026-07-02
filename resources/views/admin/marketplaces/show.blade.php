@extends('layouts.app')

@section('title')
    Detail Marketplaces
@endsection

@php
    if ($marketplace->marketplace == 'tiktok') {
        $order = route('marketplaces.uploadOrderTiktok', $marketplace->id);
        $keuangan = route('marketplaces.uploadKeuanganTiktokBaru', $marketplace->id);
        $stok = '';
    } else {
        $order = route('marketplaces.uploadOrder', $marketplace->id);
        $keuangan = route('marketplaces.uploadKeuangan', $marketplace->id);
        $stok = route('marketplaces.uploadStok', $marketplace->id);
    }
@endphp

@section('content')
    @include('layouts.includes.messages')
    <div class="card">
        <div class="card-header">
            <b>Upload Order</b>
        </div>

        <div class="card-body">


            <form method="POST" action="{{ $order }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group mb-3">
                    <label for="nama" class="mb-2">Konsumen</label>
                    <div id="autocomplete" class="autocomplete">
                        <input class="autocomplete-input {{ $errors->has('kontak_id') ? 'is-invalid' : '' }}"
                            placeholder="cari kontak" aria-label="cari kontak" value="{{ $marketplace->kontak->nama }}">
                        <span id="closeBrg" style="display: block;">@php
                            echo "<button onclick='clearData()' type='button' class='btnClose btn-warning'><i class='bx bx-x-circle'></i></button>";
                        @endphp</span>

                        <ul class="autocomplete-result-list"></ul>
                        <input type="hidden" id="kontakId" name="kontak_id" value="{{ $marketplace->kontak_id }}">
                    </div>
                    @if ($errors->has('kontak_id'))
                        <div class="invalid-feedback z-10">
                            {{ $errors->first('kontak_id') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label class="required" for="order">file harus berformat .csv</label>
                    <input class="form-control {{ $errors->has('order') ? 'is-invalid' : '' }}" type="file"
                        name="order" id="order" value="{{ old('order', '') }}">
                    @if ($errors->has('order'))
                        <div class="invalid-feedback">
                            {{ $errors->first('order') }}
                        </div>
                    @endif
                    <label class="required text-danger" for="order">terakhir upload
                        {{ $marketplace->tglUploadOrder }}</label>
                </div>
                <div class="form-group">
                    <button class="btn btn-primary mt-1" type="submit">
                        upload
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="card mt-5">
        <div class="card-header">
            <b>Upload Keuangan</b>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ $keuangan }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group mb-3">
                    <label>kas marketplace</label>
                    <select class="form-select" name="kas_id" name="kas_id">
                        <option value="{{ null }}">pilih kas marketplace</option>
                        @foreach ($kasMarketplace as $item)
                            <option {{ $item->id == $marketplace->kas_id ? 'selected' : '' }} value="{{ $item->id }}">
                                {{ $item->nama }}</option>
                        @endforeach
                    </select>
                    @if ($errors->has('kas_id'))
                        <div class="invalid-feedback">
                            {{ $errors->first('kas_id') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label>kas penarikan</label>
                    <select class="form-select" name="penarikan_id" name="penarikan_id">
                        <option value="{{ null }}">pilih kas penarikan</option>
                        @foreach ($kasPenarikan as $item)
                            <option {{ $item->id == $marketplace->penarikan_id ? 'selected' : '' }}
                                value="{{ $item->id }}">{{ $item->nama }}</option>
                        @endforeach
                    </select>
                    @if ($errors->has('penarikan_id'))
                        <div class="invalid-feedback">
                            {{ $errors->first('penarikan_id') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label class="required" for="keuangan">file harus berformat .csv</label>
                    <input class="form-control {{ $errors->has('keuangan') ? 'is-invalid' : '' }}" type="file"
                        name="keuangan" id="keuangan" value="{{ old('keuangan', '') }}">
                    @if ($errors->has('keuangan'))
                        <div class="invalid-feedback">
                            {{ $errors->first('keuangan') }}
                        </div>
                    @endif
                    <label class="required text-danger" for="order">terakhir upload
                        {{ $marketplace->tglUploadKeuangan }}</label>
                </div>
                <div class="form-group">
                    <button class="btn btn-primary mt-1" type="submit">
                        upload
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="card mt-5">
        <div class="card-header">
            <b>Upload Stok</b>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ $stok }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group mb-3">
                    <label class="required" for="stok">file harus berformat .csv</label>
                    <input class="form-control {{ $errors->has('stok') ? 'is-invalid' : '' }}" type="file"
                        name="stok" id="stok" value="{{ old('stok', '') }}">
                    @if ($errors->has('stok'))
                        <div class="invalid-feedback">
                            {{ $errors->first('stok') }}
                        </div>
                    @endif
                    <label class="required text-danger" for="stok">terakhir upload
                        {{ $marketplace->tglUploadStok }}</label>
                </div>
                <div class="form-group">
                    <button class="btn btn-primary mt-1" type="submit">
                        upload
                    </button>
                </div>
            </form>
        </div>
    </div>
    @if ($marketplace->marketplace === 'shopee')
        <div class="card mt-5">
            <div class="card-header d-flex justify-content-between align-items-center">
                <b>Sync Stok Otomatis (API)</b>
                <a href="{{ route('marketplaces.syncStokStatus') }}" class="btn btn-sm btn-outline-secondary">Lihat Status</a>
            </div>
            <div class="card-body">
                <p class="mb-2">
                    Auto sync:
                    @if ($marketplace->auto_sync_stok)
                        <span class="badge bg-success">Aktif</span>
                    @else
                        <span class="badge bg-secondary">Nonaktif</span>
                    @endif
                </p>
                <p class="mb-0 text-muted">
                    Terakhir sync API: {{ $marketplace->tglSyncStok ?? 'belum pernah' }}.
                    Sync berjalan otomatis via cron eksternal (cron-job.org).
                </p>
            </div>
        </div>
    @endif
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
