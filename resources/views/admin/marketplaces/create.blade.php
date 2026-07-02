@extends('layouts.app')

@section('title')
    Create Marketplaces
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            create marketplace
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('marketplaces.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group mb-3">
                    <label class="required" for="nama">nama</label>
                    <input class="form-control {{ $errors->has('nama') ? 'is-invalid' : '' }}" type="text" name="nama"
                        id="nama" value="{{ old('nama', '') }}">
                    @if ($errors->has('nama'))
                        <div class="invalid-feedback">
                            {{ $errors->first('nama') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label>marketplace</label>
                    <select class="form-select" name="marketplace" name="marketplace">
                        <option value="{{ null }}">pilih marketplace</option>
                        <option value="tokopedia">tokopedia</option>
                        <option value="shopee">shopee</option>
                        <option value="tiktok">tiktok</option>
                    </select>
                    @if ($errors->has('marketplace'))
                        <div class="invalid-feedback">
                            {{ $errors->first('marketplace') }}
                        </div>
                    @endif
                </div>
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
                    <label>kas marketplace</label>
                    <select class="form-select" name="kas_id" name="kas_id">
                        <option value="{{ null }}">pilih kas marketplace</option>
                        @foreach ($kasMarketplace as $item)
                            <option value="{{ $item->id }}">{{ $item->nama }}</option>
                        @endforeach
                    </select>
                    @if ($errors->has('kas_id'))
                        <div class="invalid-feedback">
                            {{ $errors->first('kas_id') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="warna">Warna</label>
                    <input class="form-control {{ $errors->has('warna') ? 'is-invalid' : '' }}" type="color"
                        name="warna" id="warna" value="{{ old('warna', '#6c757d') }}">
                    <small class="text-muted">Warna untuk membedakan toko di tampilan lain (mis. dashboard project MP).</small>
                    @if ($errors->has('warna'))
                        <div class="invalid-feedback">
                            {{ $errors->first('warna') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label>kas penarikan</label>
                    <select class="form-select" name="penarikan_id" name="penarikan_id">
                        <option value="{{ null }}">pilih kas penarikan</option>
                        @foreach ($kasPenarikan as $item)
                            <option value="{{ $item->id }}">{{ $item->nama }}</option>
                        @endforeach
                    </select>
                    @if ($errors->has('penarikan_id'))
                        <div class="invalid-feedback">
                            {{ $errors->first('penarikan_id') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label>baru Keuangan</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="baruKeuangan" id="baruKeuangan" value="1">
                        <label class="form-check-label" for="baruKeuangan">
                            baru keuangan
                        </label>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label>baru order</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="baruOrder" id="baruOrder" value="1">
                        <label class="form-check-label" for="baruOrder">
                            baru Order
                        </label>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label>Auto Sync Stok Shopee</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="auto_sync_stok" id="auto_sync_stok" value="1" checked>
                        <label class="form-check-label" for="auto_sync_stok">
                            Push stok otomatis ke Shopee (via cron-job.org)
                        </label>
                    </div>
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
