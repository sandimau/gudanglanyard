@extends('layouts.app')

@section('title')
    Tagihan Upah Freelance
@endsection

@section('content')
    <div class="bg-light rounded">
        @include('layouts.includes.messages')
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Tagihan Upah - {{ $member->nama_lengkap }}</h5>
                    </div>
                    <a href="{{ route('members.freelance') }}" class="btn btn-secondary" data-modal-skip><i class='bx bx-arrow-back'></i> Kembali</a>
                </div>
            </div>
            <div class="card-body">
                @if($totalBelumDibayar > 0)
                    <div class="alert alert-info mb-3 d-flex justify-content-between align-items-center">
                        <span><strong>Total upah belum dibayar:</strong> {{ number_format($totalBelumDibayar) }}</span>
                        <a href="{{ route('penggajian.createFreelance', $member->id) }}" class="popup btn btn-sm btn-success">Bayar Upah</a>
                    </div>
                @endif
                {{ $tagihans->links() }}
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Nominal Upah</th>
                                <th>Keterangan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tagihans as $item)
                                <tr>
                                    <td>{{ $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') : '-' }}</td>
                                    <td>{{ number_format($item->nominal_upah) }}</td>
                                    <td>{{ $item->keterangan ?? '-' }}</td>
                                    <td>
                                        @if($item->dibayar === 'sudah')
                                            <span class="badge bg-success">Sudah dibayar</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Belum dibayar</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Belum ada data tagihan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
