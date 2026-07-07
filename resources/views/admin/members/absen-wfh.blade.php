@extends('layouts.app')

@section('title')
    Absen WFH - {{ $member->nama_lengkap }}
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title">Absen WFH</h5>
                    <h6 class="card-subtitle mb-2 text-muted">{{ $member->nama_lengkap }}</h6>
                </div>
                <a href="{{ route('members.index') }}" class="btn btn-secondary" data-modal-skip>Kembali</a>
            </div>
        </div>
        <div class="card-body">
            <div class="mt-2">
                @include('layouts.includes.messages')
            </div>

            @if($sudahAbsenHariIni)
                <div class="alert alert-info">Absen untuk hari ini ({{ \Carbon\Carbon::today()->format('d/m/Y') }}) sudah tercatat.</div>
            @else
                <form method="POST" action="{{ route('members.absenWfhStore', $member->id) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="tanggal" class="form-label">Tanggal</label>
                            <input type="date" class="form-control {{ $errors->has('tanggal') ? 'is-invalid' : '' }}" name="tanggal" id="tanggal" value="{{ old('tanggal', date('Y-m-d')) }}" required>
                            @if($errors->has('tanggal'))
                                <div class="invalid-feedback">{{ $errors->first('tanggal') }}</div>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <label for="jam_mulai" class="form-label">Jam Mulai Kerja</label>
                            <input type="time" class="form-control {{ $errors->has('jam_mulai') ? 'is-invalid' : '' }}" name="jam_mulai" id="jam_mulai" value="{{ old('jam_mulai', date('H:i')) }}" required>
                            @if($errors->has('jam_mulai'))
                                <div class="invalid-feedback">{{ $errors->first('jam_mulai') }}</div>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <label for="keterangan" class="form-label">Keterangan / Laporan Pekerjaan</label>
                            <input type="text" class="form-control {{ $errors->has('keterangan') ? 'is-invalid' : '' }}" name="keterangan" id="keterangan" value="{{ old('keterangan') }}" placeholder="Contoh: input data order online">
                            @if($errors->has('keterangan'))
                                <div class="invalid-feedback">{{ $errors->first('keterangan') }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-primary" type="submit"><i class='bx bx-calendar-check'></i> Simpan Absen</button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Riwayat Absen WFH</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jam Mulai</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($absensis as $item)
                            <tr>
                                <td>{{ $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') : '-' }}</td>
                                <td>{{ $item->jam_masuk ?? '-' }}</td>
                                <td>{{ $item->keterangan ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center">Belum ada riwayat absen WFH.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $absensis->links() }}
            </div>
        </div>
    </div>
@endsection

