@extends('layouts.app')

@section('title')
    Bayar Semua Tagihan
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Bayar Semua Tagihan - {{ $member->nama_lengkap }}</h5>
                <a href="{{ route('members.freelanceTagihan', $member->id) }}" class="popup btn btn-secondary btn-sm">Kembali</a>
            </div>
        </div>
        <div class="card-body">
            @include('layouts.includes.messages')
            <form method="POST" action="{{ route('members.freelanceTagihan.storeBayarSemua', $member->id) }}">
                @csrf
                <input type="hidden" name="member_id" value="{{ $member->id }}">
                <div class="form-group mb-3">
                    <label class="form-label">Total tagihan belum dibayar</label>
                    <input type="text" class="form-control" readonly
                        value="{{ number_format($totalBelumDibayar ?? 0) }}">
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Lembur belum dibayar</label>
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" class="form-control" readonly
                                value="{{ $jmlLembur ?? 0 }} jam">
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control fw-bold" readonly
                                value="{{ number_format($totalLembur ?? 0) }}">
                        </div>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Total yang akan dibayar</label>
                    <input type="text" class="form-control fw-bold text-primary fs-5" readonly
                        value="{{ number_format($totalSemua ?? 0) }}">
                </div>
                <div class="form-group mb-3">
                    <label for="akun_detail_id">Dari rekening (kas)</label>
                    <select class="form-select @error('akun_detail_id') is-invalid @enderror" name="akun_detail_id" id="akun_detail_id" required>
                        @foreach ($kas as $id => $nama)
                            <option value="{{ $id }}" {{ old('akun_detail_id') == $id ? 'selected' : '' }}>{{ $nama }}</option>
                        @endforeach
                    </select>
                    @error('akun_detail_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary">Bayar Semua Tagihan</button>
            </form>
        </div>
    </div>
@endsection
