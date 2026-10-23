@extends('layouts.app')

@section('title', 'Semua Konten')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div>
            <h4 class="mb-1">Semua Konten</h4>
            <p class="text-muted mb-0">Daftar seluruh konten dari semua bulan.</p>
        </div>
        <a href="{{ route('konten.proses') }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back"></i> Kembali ke Proses
        </a>
    </div>
    @include('layouts.includes.messages')

    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h5 class="mb-0">Daftar Konten <span class="badge bg-primary rounded-pill">{{ $kontens->total() }}</span></h5>
            <form method="GET" action="{{ route('konten.semua') }}" class="d-flex gap-2">
                <input type="hidden" name="bulan" value="{{ $bulanKembali }}">
                <input type="search" name="q" class="form-control" value="{{ $pencarian }}"
                    placeholder="Cari topik, platform, tujuan..." aria-label="Cari konten">
                <button type="submit" class="btn btn-primary">Cari</button>
                @if ($pencarian !== '')
                    <a href="{{ route('konten.semua', ['bulan' => $bulanKembali]) }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Tanggal</th>
                            <th scope="col">Topik</th>
                            <th scope="col">Platform</th>
                            <th scope="col">Waktu Posting</th>
                            <th scope="col">Tujuan</th>
                            <th scope="col">Proses</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($kontens as $konten)
                            <tr>
                                <td class="text-nowrap">{{ $konten->tanggal->locale('id')->translatedFormat('d M Y') }}</td>
                                <td class="fw-semibold">{{ $konten->topik }}</td>
                                <td>{{ $konten->platform }}</td>
                                <td class="text-nowrap">{{ substr($konten->waktu_posting, 0, 5) }}</td>
                                <td style="min-width: 220px; white-space: pre-wrap">{{ $konten->tujuan }}</td>
                                <td class="text-nowrap">
                                    @if ($konten->tab)
                                        <span class="badge" style="background-color: {{ $konten->tab->warna }}">
                                            {{ $konten->tab->grup }} / {{ $konten->tab->nama }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">Belum Ada Tab</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#editKontenModal" data-konten-id="{{ $konten->id }}"
                                        data-action="{{ route('konten.update', $konten) }}"
                                        data-delete-action="{{ route('konten.destroy', $konten) }}"
                                        data-chat-url="{{ route('konten.chat.index', $konten) }}"
                                        data-chat-store-url="{{ route('konten.chat.store', $konten) }}"
                                        data-tanggal="{{ $konten->tanggal->toDateString() }}"
                                        data-topik="{{ $konten->topik }}" data-platform="{{ $konten->platform }}"
                                        data-waktu-posting="{{ substr($konten->waktu_posting, 0, 5) }}"
                                        data-tujuan="{{ $konten->tujuan }}">Edit</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">
                                {{ $pencarian !== '' ? 'Tidak ada konten yang cocok dengan pencarian.' : 'Belum ada konten.' }}
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($kontens->hasPages())
                <div class="mt-3">{{ $kontens->links() }}</div>
            @endif
        </div>
    </div>
    @include('admin.konten.partials.edit-modal', [
        'modalAsal' => 'semua',
        'modalBulan' => $bulanKembali,
        'modalPencarian' => $pencarian,
        'modalHalaman' => $kontens->currentPage(),
    ])
@endsection
