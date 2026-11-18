@extends('layouts.app')

@section('title', 'Pecah Stok')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <div>
                <h5 class="card-title mb-0">Riwayat Pecah Stok</h5>
                <small class="text-muted">Memecah stok produk roll/gulungan menjadi stok satuan.</small>
            </div>
            @if ($bolehInput)
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPecahStok">
                    Pecah Stok
                </button>
            @endif
        </div>
        <div class="card-body table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Produk Asal</th>
                        <th>Produk Tujuan</th>
                        <th>Gudang</th>
                        <th>Jumlah Asal</th>
                        <th>Hasil</th>
                        <th>Rasio</th>
                        <th>Keterangan</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($riwayat as $mutasi)
                        @php
                            $hasil = $hasilPerMutasi->get($mutasi->id);
                            $satuanAsal = $mutasi->produk?->produkModel?->satuan ?? 'unit';
                            $satuanTujuan = $hasil?->produk?->produkModel?->satuan ?? 'unit';
                            $rasio = $mutasi->kurang > 0 && $hasil
                                ? rtrim(rtrim(number_format($hasil->tambah / $mutasi->kurang, 2, ',', '.'), '0'), ',')
                                : '-';
                        @endphp
                        <tr>
                            <td>{{ $mutasi->created_at->format('d-m-Y H:i') }}</td>
                            <td>{{ $mutasi->produk?->namaLengkap ?? 'Produk dihapus' }}</td>
                            <td>{{ $hasil?->produk?->namaLengkap ?? '-' }}</td>
                            <td>{{ $mutasi->cabang?->nama ?? '-' }}</td>
                            <td>{{ number_format($mutasi->kurang, 0, ',', '.') }} {{ $satuanAsal }}</td>
                            <td>{{ $hasil ? number_format($hasil->tambah, 0, ',', '.') . ' ' . $satuanTujuan : '-' }}</td>
                            <td>{{ $rasio }}</td>
                            <td>{{ $mutasi->keterangan }}</td>
                            <td>{{ $mutasi->user?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted">Belum ada pecah stok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($bolehInput)
        @include('admin.produkStoks.partials.pecahStokForm')
    @endif
@endsection

@if ($bolehInput)
@push('after-styles')
    <link rel="stylesheet" href="{{ asset('js/autocomplete.css') }}">
    <style>
        #modalPecahStok .modal-body {
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
        }
        .pecah-produk { position: relative; }
        .pecah-produk .autocomplete-input { width: 100%; }
        .pecah-produk .autocomplete-result-list { z-index: 20; }
    </style>
@endpush

@push('after-scripts')
    <script src="{{ asset('js/autocomplete.min.js') }}"></script>
    <script>
        @if ($errors->any())
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPecahStok')).show();
        @endif

        function pecahFormatAngka(nilai) {
            return (Number(nilai) || 0).toLocaleString('id-ID');
        }

        document.querySelectorAll('.pecah-produk').forEach(function (root) {
            const labelInput = root.querySelector('.autocomplete-input');
            const idInput = root.querySelector('.pecah-produk-id');

            labelInput.addEventListener('input', function () {
                idInput.value = '';
            });

            new Autocomplete(root, {
                search: function (input) {
                    if (input.trim().length < 1) return Promise.resolve([]);
                    return fetch("{{ route('order.produkStok') }}?q=" + encodeURIComponent(input))
                        .then(function (response) { return response.json(); })
                        .catch(function () { return []; });
                },
                getResultValue: function (result) {
                    return result.kategori + ' - ' + result.nama
                        + (result.varian ? ' - ' + result.varian : '')
                        + (result.satuan ? ' (' + result.satuan + ')' : '')
                        + ' (#' + result.id + ')';
                },
                onSubmit: function (result) {
                    idInput.value = result.id;
                }
            });
        });

        const jumlahAsalInput = document.getElementById('jumlah_asal');
        const rasioInput = document.getElementById('rasio');
        const hasilPecahOutput = document.getElementById('hasil_pecah');

        function perbaruiHasilPecah() {
            if (!hasilPecahOutput) return;
            const jumlah = parseInt(jumlahAsalInput?.value, 10) || 0;
            const rasio = parseInt(rasioInput?.value, 10) || 0;
            hasilPecahOutput.textContent = pecahFormatAngka(jumlah * rasio);
        }

        jumlahAsalInput?.addEventListener('input', perbaruiHasilPecah);
        rasioInput?.addEventListener('input', perbaruiHasilPecah);
        perbaruiHasilPecah();
    </script>
@endpush
@endif
