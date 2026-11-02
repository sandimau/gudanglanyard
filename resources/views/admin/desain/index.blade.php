@extends('layouts.app')

@section('title', 'Daftar Desain')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div>
            <h4 class="mb-1">Daftar Desain</h4>
            <p class="text-muted mb-0">Daftar seluruh desain konsumen.</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('desain.proses') }}" class="btn btn-outline-secondary">
                <i class="bx bx-columns"></i> Proses Desain
            </a>
            @if ($bolehTambahDesain)
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahDesainModal">
                    <i class="bx bx-plus"></i> Tambah Desain
                </button>
            @endif
        </div>
    </div>
    @include('layouts.includes.messages')

    @unless ($bolehTambahDesain)
        <div class="alert alert-info">
            @if ($canSetupDesain)
                Buat <a href="{{ route('desain.setup') }}">kategori dan sub kategori desain</a> terlebih dahulu sebelum menambahkan desain.
            @else
                Setup desain belum tersedia. Hubungi pengguna yang bisa mengatur kategori desain.
            @endif
        </div>
    @endunless

    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h5 class="mb-0">Daftar Desain <span class="badge bg-primary rounded-pill">{{ $desains->total() }}</span></h5>
            <form method="GET" action="{{ route('desain.index') }}" class="d-flex flex-wrap align-items-center gap-2 desain-filter-form">
                <input type="search" name="q" class="form-control desain-search" value="{{ $pencarian }}"
                    placeholder="Cari konsumen, produk, tema, tipe, keterangan..." aria-label="Cari desain">
                <select name="tipe" class="form-select desain-filter-tipe" aria-label="Filter tipe desain">
                    <option value="">Semua Tipe</option>
                    <option value="offline" @selected($tipe === 'offline')>Offline</option>
                    <option value="online" @selected($tipe === 'online')>Online</option>
                </select>
                <button type="submit" class="btn btn-primary text-nowrap">Cari</button>
                @if ($pencarian !== '' || $tipe !== '')
                    <a href="{{ route('desain.index') }}" class="btn btn-outline-secondary text-nowrap">Reset</a>
                @endif
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Konsumen</th>
                            <th scope="col">Produk</th>
                            <th scope="col">Tema</th>
                            <th scope="col">Tipe</th>
                            <th scope="col">Proses</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($desains as $desain)
                            <tr>
                                <td class="fw-semibold"><a href="{{ route('desain.show', $desain) }}" data-bs-toggle="modal"
                                    data-bs-target="#detailDesainModal" data-detail-url="{{ route('desain.show', $desain) }}">{{ $desain->nama_konsumen }}</a></td>
                                <td>{{ $desain->produk ? $desain->produk_label : '-' }}</td>
                                <td>{{ $desain->tema ?: '-' }}</td>
                                <td><span class="badge {{ $desain->tipe === 'online' ? 'bg-success' : 'bg-secondary' }}">{{ $desain->tipe_label }}</span></td>
                                <td class="text-nowrap">
                                    @if ($desain->tab)
                                        <span class="badge" style="background-color: {{ $desain->tab->warna }}">
                                            {{ $desain->tab->grup }} / {{ $desain->tab->nama }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">Belum Ada Sub Kategori</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">
                                {{ $pencarian !== '' || $tipe !== '' ? 'Tidak ada desain yang cocok dengan filter.' : 'Belum ada desain.' }}
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($desains->hasPages())
                <div class="mt-3">{{ $desains->links() }}</div>
            @endif
        </div>
    </div>

    @if ($bolehTambahDesain)
        @include('admin.desain.partials.tambah-modal', [
            'modalAsal' => 'daftar',
            'modalQ' => $pencarian,
            'modalTipe' => $tipe,
            'modalPage' => $desains->currentPage(),
        ])
    @endif
    @include('admin.desain.partials.detail-modal')
    @include('admin.desain.partials.edit-modal', [
        'modalAsal' => 'daftar',
        'modalQ' => $pencarian,
        'modalTipe' => $tipe,
        'modalPage' => $desains->currentPage(),
    ])
    @include('admin.desain.partials.produk-autocomplete-script')
@endsection

@push('after-styles')
    <style>
        .desain-filter-form { flex: 1 1 auto; justify-content: flex-end; }
        .desain-filter-form .desain-search { flex: 1 1 220px; width: auto; max-width: 320px; min-width: 180px; }
        .desain-filter-form .desain-filter-tipe { flex: 0 0 140px; width: 140px; }
    </style>
@endpush
