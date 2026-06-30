@extends('layouts.app')

@section('title')
    Create Penggajians
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            tambah penggajian
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('penggajian.storeFreelance') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="member_id" value="{{ $member->id }}">
                <div class="form-group mb-3">
                    <label class="control-label ">Nama</label>
                    <input readonly class="form-control" name="nama" id="nama" type="text"
                        value="{{ $member->nama_lengkap }}">
                </div>
                <div class="form-group mb-3">
                    <label class="control-label ">total lembur</label>
                    <div class="">
                        <span style="font-weight: 500">{{ $jmlLembur }} jam</span>
                        <input type="hidden" name="jam_lembur" value="{{ $jmlLembur }}">
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label class="control-label ">tunjangan lembur</label>
                    <div class="">
                        <input readonly class="form-control" name="lembur" id="lembur" type="text"
                            value="{{ $totalLembur }}">
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label class="control-label ">upah</label>
                    <div class="">
                        <input readonly class="form-control" name="upah" id="upah" type="text"
                            value="{{ $member->upah }}">
                    </div>
                </div>
                @if($jumlahHari > 0)
                    <div class="alert alert-info mb-3">
                        <strong>{{ $jumlahHari }} tagihan belum dibayar</strong>
                        (total upah: {{ number_format($totalTagihan) }})
                        <ul class="mb-0 mt-2 small">
                            @foreach ($tagihansBelumDibayar as $tagihan)
                                <li>{{ $tagihan->tanggal ? \Carbon\Carbon::parse($tagihan->tanggal)->format('d/m/Y') : '-' }}
                                    — {{ number_format($tagihan->nominal_upah) }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <input type="hidden" id="total_tagihan" value="{{ $totalTagihan }}">
                @else
                    <input type="hidden" id="total_tagihan" value="0">
                @endif
                <div class="form-group mb-3">
                    <label class="control-label ">Jumlah hari</label>
                    <div class="">
                        <input onchange="getTotal()" class="form-control" name="jumlah_hari" id="jumlah_hari"
                            type="number" value="{{ $jumlahHari }}"
                            {{ $jumlahHari > 0 ? 'readonly' : '' }}>
                        @if($jumlahHari > 0)
                            <small class="text-muted">Diisi otomatis dari jumlah tagihan yang belum dibayar.</small>
                        @endif
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label class="control-label ">Nilai Lain2</label>
                    <input onchange="getTotal()" type="number" class="form-control" name="jumlah_lain" id="jumlah_lain"
                        value="0">
                </div>
                <div class="form-group mb-3">
                    <label class="control-label ">keterangan</label>
                    <input type="text" class="form-control" name="lain_lain" id="lain_lain">
                </div>
                <div class="form-group mb-3">
                    <label class="control-label ">total</label>
                    <input readonly id="total" type="number" class="form-control" name="total">
                </div>
                <div class="form-group mb-3 mb-3">
                    <label for="akun_detail_id">dari rek</label>
                    <select class="form-select select2 {{ $errors->has('akun_detail_id') ? 'is-invalid' : '' }}"
                        name="akun_detail_id" id="akun_detail_id">
                        @foreach ($kas as $id => $entry)
                            <option value="{{ $id }}" {{ old('akun_detail_id') == $id ? 'selected' : '' }}>
                                {{ $entry }}</option>
                        @endforeach
                    </select>
                    @if ($errors->has('akun_detail_id'))
                        <div class="invalid-feedback">
                            {{ $errors->first('akun_detail_id') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <button class="btn btn-danger" type="submit">
                        save
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
@push('after-scripts')
    <style>
        .control-label {
            padding-bottom: 10px
        }

        input:read-only {
            background-color: rgb(218, 218, 218);
        }
        #total{
            background-color: rgb(203, 211, 247);
            font-weight: 600
        }
    </style>
    <script>
        function getTotal() {
            let upah = parseInt(document.getElementById("upah").value) || 0;
            let lembur = parseInt(document.getElementById("lembur").value) || 0;
            let jumlah_hari = parseInt(document.getElementById("jumlah_hari").value) || 0;
            let jumlah_lain = parseInt(document.getElementById("jumlah_lain").value) || 0;
            let totalTagihan = parseInt(document.getElementById("total_tagihan").value) || 0;
            let totalSemua = document.getElementById('total');
            let upahTotal = totalTagihan > 0 ? totalTagihan : (upah * jumlah_hari);
            let total = upahTotal + lembur + jumlah_lain;
            totalSemua.value = total;
        }
        getTotal()
    </script>
@endpush
