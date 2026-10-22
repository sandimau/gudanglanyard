@extends('layouts.app')

@section('title', 'Pindah Gudang')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <h5 class="card-title mb-0">Riwayat Pindah Gudang</h5>
            @if ($bolehInput)
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPindahGudang">
                    Pindah Gudang
                </button>
            @endif
        </div>
        <div class="card-body table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Produk</th>
                        <th>Asal</th>
                        <th>Tujuan</th>
                        <th>Jumlah</th>
                        <th>Keterangan</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($riwayat as $mutasi)
                        <tr>
                            <td>{{ $mutasi->created_at->format('d-m-Y H:i') }}</td>
                            <td>{{ $mutasi->produk?->namaLengkap ?? 'Produk dihapus' }}</td>
                            <td>{{ $mutasi->cabang?->nama ?? '-' }}</td>
                            <td>{{ $tujuanPerMutasi->get($mutasi->id)?->cabang?->nama ?? '-' }}</td>
                            <td>{{ $mutasi->kurang }}</td>
                            <td>{{ $mutasi->keterangan }}</td>
                            <td>{{ $mutasi->user?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">Belum ada perpindahan gudang.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($bolehInput)
        @include('admin.produkStoks.partials.pindahGudangForm')
    @endif
@endsection

@if ($bolehInput)
@push('after-styles')
    <link rel="stylesheet" href="{{ asset('js/autocomplete.css') }}">
    <style>
        #modalPindahGudang .modal-body {
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
        }
        .pindah-produk { position: relative; }
        .pindah-produk .autocomplete-input { width: 100%; }
        .pindah-produk .autocomplete-result-list { z-index: 20; }
    </style>
@endpush

@push('after-scripts')
    <script src="{{ asset('js/autocomplete.min.js') }}"></script>
    <script>
        @if ($errors->any())
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPindahGudang')).show();
        @endif

        document.querySelectorAll('.pindah-produk').forEach(function (root) {
            const labelInput = root.querySelector('.autocomplete-input');
            const idInput = root.querySelector('.pindah-produk-id');

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
                    return result.kategori + ' - ' + result.nama + (result.varian ? ' - ' + result.varian : '') + ' (#' + result.id + ')';
                },
                onSubmit: function (result) {
                    idInput.value = result.id;
                }
            });
        });
    </script>
@endpush
@endif
