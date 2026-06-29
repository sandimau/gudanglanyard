@extends('layouts.app')

@section('title')
    Bayar Tagihan
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Bayar Tagihan - {{ $freelanceTagihan->member->nama_lengkap }}</h5>
                <a href="{{ route('members.freelanceTagihan', $freelanceTagihan->member_id) }}" class="popup btn btn-secondary btn-sm">Kembali</a>
            </div>
        </div>
        <div class="card-body">
            @include('layouts.includes.messages')
            <form method="POST" action="{{ route('members.freelanceTagihan.storeBayar') }}">
                @csrf
                <input type="hidden" name="freelance_tagihan_id" value="{{ $freelanceTagihan->id }}">
                <div class="form-group mb-3">
                    <label class="form-label">Tanggal tagihan</label>
                    <input type="text" class="form-control" readonly
                        value="{{ $freelanceTagihan->tanggal ? \Carbon\Carbon::parse($freelanceTagihan->tanggal)->format('d/m/Y') : '-' }}">
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Nominal upah</label>
                    <input type="text" class="form-control fw-bold" readonly
                        value="{{ number_format($freelanceTagihan->nominal_upah) }}">
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
                <button type="submit" class="btn btn-primary">Bayar Tagihan</button>
            </form>
        </div>
    </div>
@endsection
