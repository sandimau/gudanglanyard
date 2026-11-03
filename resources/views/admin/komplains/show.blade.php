@extends('layouts.app')

@section('title', 'Detail Komplain')

@section('content')
    @include('layouts.includes.messages')

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex align-items-center justify-content-between gap-2">
            <h5 class="mb-0">Detail Komplain #{{ $komplain->id }}</h5>
            <a href="{{ route('komplain.index') }}" class="btn btn-outline-secondary btn-sm komplain-back-link">Kembali</a>
        </div>
        <div class="card-body">
            @php
                $detailUrl = $komplain->project_mp_detail_id && $komplain->projectMp
                    ? route('projectmp.detail', $komplain->project_mp_id)
                    : ($komplain->order
                        ? route('order.detail', $komplain->order_id)
                        : ($komplain->projectMp ? route('projectmp.detail', $komplain->project_mp_id) : null));
                $pengganti = $komplain->replacementOrderDetail ?: $komplain->replacementProjectMpDetail;
            @endphp

            <div class="row g-3 mb-4">
                <div class="col-md-4"><div class="text-secondary small">Tanggal Komplain</div>{{ $komplain->created_at?->format('d-m-Y H:i') }}</div>
                <div class="col-md-4"><div class="text-secondary small">Konsumen</div>{{ $komplain->nama_konsumen ?: ($komplain->kontak->nama ?? '-') }}</div>
                <div class="col-md-4"><div class="text-secondary small">Jenis</div>{{ $komplain->tipe_label }}</div>
                <div class="col-md-4">
                    <div class="text-secondary small">Nota / Order</div>
                    @if ($detailUrl)
                        <a href="{{ $detailUrl }}">{{ $komplain->nota ?: '-' }}</a>
                    @else
                        {{ $komplain->nota ?: '-' }}
                    @endif
                </div>
                <div class="col-md-4"><div class="text-secondary small">Produk</div>{{ $komplain->produk?->namaLengkap ?? $komplain->produk?->nama ?? '-' }}</div>
                <div class="col-md-4"><div class="text-secondary small">Jumlah Komplain</div>{{ number_format($komplain->jumlah, 0, ',', '.') }} pcs</div>
                <div class="col-md-4"><div class="text-secondary small">Status</div>{{ $komplain->status_label }}</div>
                <div class="col-md-4"><div class="text-secondary small">Jumlah Produk Diganti</div>{{ $komplain->status === 'approved' ? number_format($komplain->jumlah, 0, ',', '.') . ' pcs' : '-' }}</div>
                <div class="col-md-4"><div class="text-secondary small">Biaya Pengganti / Produk</div>{{ $komplain->status === 'approved' ? 'Rp ' . number_format($komplain->jumlah ? $komplain->kerugian / $komplain->jumlah : 0, 0, ',', '.') : '-' }}</div>
                <div class="col-md-4"><div class="text-secondary small">Total Kerugian</div><strong class="text-danger">Rp {{ number_format($komplain->kerugian, 0, ',', '.') }}</strong></div>
                <div class="col-md-4"><div class="text-secondary small">Dibuat Oleh</div>{{ $komplain->user->name ?? '-' }}</div>
                <div class="col-md-4"><div class="text-secondary small">Disetujui Oleh / Tanggal</div>{{ $komplain->approvedBy->name ?? '-' }} {{ $komplain->approved_at?->format('d-m-Y H:i') }}</div>
                @if ($pengganti)
                    <div class="col-md-4"><div class="text-secondary small">ID Detail Produk Pengganti</div>#{{ $pengganti->id }}</div>
                @endif
                <div class="col-12"><div class="text-secondary small">Bagian yang Salah</div>{{ $komplain->bagian_salah }}</div>
                <div class="col-12"><div class="text-secondary small">Isi Komplain</div>{{ $komplain->keterangan ?: '-' }}</div>
                @if ($komplain->catatan_admin)
                    <div class="col-12"><div class="text-secondary small">Catatan Admin</div>{{ $komplain->catatan_admin }}</div>
                @endif
            </div>

            @can('komplain_approve')
                @if ($komplain->status === 'approved')
                    <form action="{{ route('komplain.kerugian', $komplain) }}" method="post" class="border-top pt-3">
                        @csrf
                        @method('patch')
                        <label for="hppSatuan" class="form-label">Biaya penggantian per produk (HPP)</label>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <input id="hppSatuan" type="number" name="hpp_satuan" min="1" step="1" required
                                class="form-control" style="max-width: 220px;"
                                value="{{ old('hpp_satuan', $komplain->jumlah ? (int) ($komplain->kerugian / $komplain->jumlah) : 0) ?: '' }}"
                                placeholder="Biaya per produk">
                            <button type="submit" class="btn btn-primary">Simpan Kerugian</button>
                        </div>
                        <div class="form-text">Total kerugian = biaya per produk × jumlah pengganti. Harga order tidak berubah.</div>
                    </form>
                @elseif ($komplain->is_pending)
                    <form action="{{ route('komplain.approve', $komplain) }}" method="post" class="border-top pt-3"
                        onsubmit="return confirm('Setujui komplain ini dan tambahkan produk pengganti?');">
                        @csrf
                        @method('patch')
                        <label for="hppSatuan" class="form-label">Biaya penggantian per produk (HPP)</label>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <input id="hppSatuan" type="number" name="hpp_satuan" min="1" step="1" required
                                class="form-control" style="max-width: 220px;"
                                value="{{ old('hpp_satuan', $komplain->hpp_satuan ?: '') }}"
                                placeholder="Biaya per produk">
                            <button type="submit" class="btn btn-success">ACC & Tambah Pengganti</button>
                        </div>
                    </form>
                @endif
            @endcan
        </div>
    </div>
@endsection
