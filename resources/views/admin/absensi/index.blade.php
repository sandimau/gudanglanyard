@extends('layouts.app')

@section('title')
    Data Absensi
@endsection

@section('content')
    <div class="bg-light rounded">
        @include('layouts.includes.messages')
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h5 class="card-title">Absensi</h5>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 ">
                    <div class="col-md-2">
                        <label class="form-label">Member</label>
                        <select name="member_id" class="form-select">
                            <option value="">Semua</option>
                            @foreach($members as $m)
                                <option value="{{ $m->id }}" {{ request('member_id') == $m->id ? 'selected' : '' }}>{{ $m->nama_lengkap }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Bulan</label>
                        <select name="bulan" class="form-select">
                            <option value="">Semua</option>
                            @for($i=1;$i<=12;$i++)
                                @php
                                    $bulanList = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
                                @endphp
                                <option value="{{ $i }}" {{ request('bulan') == $i ? 'selected' : '' }}>{{ $bulanList[$i] }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tahun</label>
                        <input type="number" name="tahun" class="form-control" value="{{ request('tahun', date('Y')) }}" min="2020" max="2030">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-secondary">Filter</button>
                    </div>
                </form>
                {{ $absensis->links() }}
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Member</th>
                                <th>Jenis</th>
                                <th>Absensi</th>
                                <th>Keterangan</th>
                                <th>Jam Masuk</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($absensis as $item)
                                <tr>
                                    <td>{{ $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') : '-' }}</td>
                                    <td>{{ $item->member->nama_lengkap ?? '-' }}</td>
                                    <td>{{ $item->member->jenis }}</td>
                                    <td><span class="badge bg-{{ $item->jenis == 'cuti' ? 'info' : ($item->jenis == 'terlambat' ? 'warning' : 'secondary') }}">{{ ucfirst($item->jenis) }}</span></td>
                                    <td>{{ $item->keterangan ?? '-' }}</td>
                                    <td>{{ $item->jam_masuk ?? '-' }}</td>
                                    <td>
                                        <form action="{{ route('absensi.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus absensi ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm"><i class='bx bx-trash'></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
