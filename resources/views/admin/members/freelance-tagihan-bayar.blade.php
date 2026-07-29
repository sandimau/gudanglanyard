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
                    <input type="text" class="form-control fw-bold" readonly id="nominal_upah"
                        value="{{ number_format($freelanceTagihan->nominal_upah) }}">
                    <input type="hidden" id="nominal_upah_raw" value="{{ (int) $freelanceTagihan->nominal_upah }}">
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Potong kasbon</label>
                    <input onchange="hitungTotalBayar()" type="number" class="form-control" name="kasbon"
                        id="kasbon" value="{{ old('kasbon', $totalKasbon ?? 0) }}" min="0"
                        max="{{ (int) $freelanceTagihan->nominal_upah }}">
                    @if(($totalKasbon ?? 0) > 0)
                        <small class="text-muted">Saldo kasbon saat ini: {{ number_format($totalKasbon) }}</small>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Total dibayar</label>
                    <input type="text" class="form-control fw-bold" readonly id="total_dibayar" value="">
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

@push('after-scripts')
    <script>
        function hitungTotalBayar() {
            let nominal = parseInt(document.getElementById('nominal_upah_raw').value) || 0;
            let kasbon = parseInt(document.getElementById('kasbon').value) || 0;
            let total = nominal - kasbon;
            document.getElementById('total_dibayar').value = total.toLocaleString('id-ID');
        }
        hitungTotalBayar();
    </script>
@endpush
