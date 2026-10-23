@extends('layouts.app')

@section('title', 'Jadwal Konten')

@section('content')
    @php
        $namaHari = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
        $tanggal = $kalenderAwal->copy();
        $modalTanggalDefault = $bulan->isSameMonth(now()) ? now()->toDateString() : $bulan->copy()->startOfMonth()->toDateString();
    @endphp
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div>
            <h4 class="mb-1">Jadwal Konten</h4>
            <p class="text-muted mb-0">Klik tanggal untuk menambahkan konten. Klik topik untuk mengubah detail.</p>
        </div>
        @if ($bolehTambahKonten)
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahKontenModal"
                data-tanggal="{{ $modalTanggalDefault }}"><i class="bx bx-plus"></i> Tambah Konten</button>
        @endif
    </div>
    @include('layouts.includes.messages')
    @unless ($bolehTambahKonten)
        <div class="alert alert-info">
            @if ($canSetupKonten)
                Buat <a href="{{ route('konten.setup') }}">tab proses konten</a> terlebih dahulu sebelum menambahkan jadwal.
            @else
                Setup konten belum tersedia. Hubungi super atau manager sebelum menambahkan jadwal.
            @endif
        </div>
    @endunless
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between gap-2">
            <a href="{{ route('konten.jadwal', ['bulan' => $bulan->copy()->subMonth()->format('Y-m')]) }}"
                class="btn btn-outline-secondary btn-sm" aria-label="Bulan sebelumnya"><i class="bx bx-chevron-left"></i> Sebelumnya</a>
            <h5 class="mb-0 text-center">{{ $bulan->locale('id')->translatedFormat('F Y') }}</h5>
            <a href="{{ route('konten.jadwal', ['bulan' => $bulan->copy()->addMonth()->format('Y-m')]) }}"
                class="btn btn-outline-secondary btn-sm" aria-label="Bulan berikutnya">Berikutnya <i class="bx bx-chevron-right"></i></a>
        </div>
        <div class="card-body p-2 p-md-3">
            <div class="konten-kalender" role="grid" aria-label="Kalender {{ $bulan->locale('id')->translatedFormat('F Y') }}">
                @foreach ($namaHari as $hari)
                    <div class="konten-kalender__heading" role="columnheader">{{ $hari }}</div>
                @endforeach
                @while ($tanggal->lte($kalenderAkhir))
                    @php
                        $tanggalString = $tanggal->toDateString();
                        $diBulanIni = $tanggal->isSameMonth($bulan);
                    @endphp
                    <div class="konten-kalender__hari {{ $diBulanIni ? '' : 'konten-kalender__hari--luar' }} {{ $tanggal->isToday() ? 'konten-kalender__hari--hari-ini' : '' }}"
                        role="gridcell" aria-label="{{ $tanggal->locale('id')->translatedFormat('l, d F Y') }}">
                        <div class="d-flex justify-content-between align-items-start gap-1">
                            <span class="fw-semibold">{{ $tanggal->day }}</span>
                            @if ($diBulanIni && $bolehTambahKonten)
                                <button type="button" class="konten-kalender__tambah" data-bs-toggle="modal"
                                    data-bs-target="#tambahKontenModal" data-tanggal="{{ $tanggalString }}"
                                    title="Tambah konten {{ $tanggalString }}" aria-label="Tambah konten {{ $tanggalString }}">+</button>
                            @endif
                        </div>
                        @if ($diBulanIni)
                            @foreach ($kontens->get($tanggalString, collect()) as $konten)
                                <button type="button" class="konten-kalender__topik" data-bs-toggle="modal"
                                    data-bs-target="#editKontenModal" data-konten-id="{{ $konten->id }}"
                                    data-action="{{ route('konten.update', $konten) }}"
                                    data-delete-action="{{ route('konten.destroy', $konten) }}"
                                    data-chat-url="{{ route('konten.chat.index', $konten) }}"
                                    data-chat-store-url="{{ route('konten.chat.store', $konten) }}"
                                    data-tanggal="{{ $konten->tanggal->toDateString() }}"
                                    data-topik="{{ $konten->topik }}" data-platform="{{ $konten->platform }}"
                                    data-waktu-posting="{{ substr($konten->waktu_posting, 0, 5) }}"
                                    data-tujuan="{{ $konten->tujuan }}"
                                    title="Edit {{ $konten->topik }}">{{ $konten->topik }}</button>
                            @endforeach
                        @endif
                    </div>
                    @php $tanggal->addDay(); @endphp
                @endwhile
            </div>
        </div>
    </div>
    @if ($bolehTambahKonten)
        @include('admin.konten.partials.tambah-modal', ['modalAsal' => 'jadwal'])
    @endif
    @include('admin.konten.partials.edit-modal')
@endsection

@push('after-styles')
    <style>
        .konten-kalender { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 3px; }
        .konten-kalender__heading { text-align: center; font-weight: 600; padding: .5rem 0; }
        .konten-kalender__hari { min-width: 0; min-height: 112px; padding: .45rem; border: 1px solid var(--app-border, #dee2e6); border-radius: 6px; overflow: hidden; }
        .konten-kalender__hari--luar { opacity: .4; background: var(--app-card-bg, #f8f9fa); }
        .konten-kalender__hari--hari-ini { border-color: #0d6efd; box-shadow: inset 0 0 0 1px #0d6efd; }
        .konten-kalender__tambah { font-size: 1.2rem; line-height: 1; padding: 0 .2rem; color: #0d6efd; background: transparent; border: 0; cursor: pointer; }
        .konten-kalender__topik { display: block; width: 100%; margin-top: .35rem; padding: .2rem .35rem; border: 0; border-radius: 4px; background: #dbeafe; color: #1e40af; font-size: .8rem; line-height: 1.3; overflow-wrap: anywhere; text-align: left; cursor: pointer; }
        .konten-kalender__topik:hover { background: #bfdbfe; color: #1e3a8a; }
        @media (max-width: 700px) {
            .konten-kalender__hari { min-height: 76px; padding: .2rem; font-size: .8rem; }
            .konten-kalender__topik { font-size: .68rem; padding: .1rem .15rem; }
        }
    </style>
@endpush
