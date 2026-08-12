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
            <form method="POST" action="{{ route('penggajian.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="member_id" value="{{ $member->id }}">
                <div class="row mb-4">
                    <div class="col-4">
                        <label class="control-label">Nama :</label>
                        <span style="font-weight: 500">{{ $member->nama_lengkap }}</span>
                    </div>
                    <div class="col-4">
                        <label class="control-label">bagian :</label>
                        <span style="font-weight: 500">{{ $bagian->nama }}</span>
                    </div>
                    <div class="col-4">
                        <label class="control-label">level :</label>
                        <span style="font-weight: 500">{{ $level->nama }}</span>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label class="control-label ">Gaji Pokok</label>
                    <input readonly class="form-control" name="pokok" id="gapok" type="text"
                        value="{{ $level->gaji_pokok }}">
                </div>
                <div class="form-group mb-3">
                    <label class="control-label ">Tunjangan lama kerja</label>
                    <input readonly class="form-control" name="lama_kerja" id="lamaKerja" type="text"
                        value="{{ $lamaKerja }}">
                </div>
                <div class="form-group mb-3">
                    <label class="control-label ">Tunjangan bagian</label>
                    <input readonly class="form-control" name="bagian" id="tBagian" type="text"
                        value="{{ $tBagian }}">
                </div>
                <div class="form-group mb-3">
                    <label class="control-label ">Tunjangan performance</label>
                    <input readonly class="form-control" name="performance" id="performance" type="text"
                        value="{{ $performance }}">
                </div>
                <div class="form-group mb-3">
                    <label class="control-label ">Tunjangan transportasi</label>
                    <input readonly class="form-control" name="transportasi" id="transportasi" type="text"
                        value="{{ $transportasi }}">
                </div>
                <div class="form-group mb-3">
                    <label class="control-label ">Tunjangan komunikasi</label>
                    <input readonly class="form-control" name="komunikasi" id="komunikasi" type="text"
                        value="{{ $level->komunikasi }}">
                </div>
                <div class="form-group mb-3">
                    <label class="control-label ">Tunjangan lain2</label>
                    <input readonly class="form-control" name="lain_lain" id="lain_lain" type="text"
                        value="{{ $gaji->lain_lain }}">
                </div>
                <div class="form-group mb-3">
                    <label class="control-label ">Nilai Tunjangan lain2</label>
                    <input readonly class="form-control" name="jumlah_lain" id="jumlah_lain" type="number"
                        value="{{ $gaji->jumlah_lain ? $gaji->jumlah_lain : 0 }}">
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
                    <label class="control-label ">tunjangan kehadiran</label>
                    @isset($periodeMulai)
                        <small class="text-muted d-block">periode {{ $periodeMulai->format('d/m/Y') }} -
                            {{ $periodeSelesai->format('d/m/Y') }}</small>
                    @endisset
                    @if(isset($jumlahAbsenTidakCuti) && $jumlahAbsenTidakCuti > 0)
                        <small class="text-muted d-block">({{ $jumlahAbsenTidakCuti }}x absen sakit/ijin/terlambat - cuti tidak mengurangi)</small>
                    @endif
                    <input readonly type="number" class="form-control" name="kehadiran" id="kehadiran"
                        value="{{ $tunjanganKehadiran ?? $level->kehadiran }}">
                </div>
                <div class="form-group mb-3">
                    <label class="control-label ">potong kasbon</label>
                    <input onchange="getTotal()" type="number" class="form-control" name="kasbon" id="kasbon"
                        value="{{ $totalKasbon }}">
                </div>
                <div class="form-group mb-3">
                    <label class="control-label ">bonus</label>
                    <input onchange="getTotal()" type="number" class="form-control" name="bonus" id="bonus"
                        value="0">
                </div>
                <div class="form-group mb-3">
                    <label class="control-label ">total</label>
                    <input readonly id="total" type="number" class="form-control" name="total">
                </div>
                <div class="form-group mb-3 mb-3">
                    <label for="akun_detail_id">dari rek</label>
                    <select class="form-select select2 {{ $errors->has('akun_detail') ? 'is-invalid' : '' }}"
                        name="akun_detail_id" id="akun_detail_id">
                        @foreach ($kas as $id => $entry)
                            <option value="{{ $id }}" {{ old('akun_detail_id') == $id ? 'selected' : '' }}>
                                {{ $entry }}</option>
                        @endforeach
                    </select>
                    @if ($errors->has('akun_detail'))
                        <div class="invalid-feedback">
                            {{ $errors->first('akun_detail') }}
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
            let gapok = document.getElementById("gapok").value;
            let lamaKerja = document.getElementById("lamaKerja").value;
            let tBagian = document.getElementById("tBagian").value;
            let performance = document.getElementById("performance").value;
            let transportasi = document.getElementById("transportasi").value;
            let komunikasi = document.getElementById("komunikasi").value;
            let jumlah_lain = document.getElementById("jumlah_lain").value;
            let lembur = document.getElementById("lembur").value;
            let kehadiran = document.getElementById("kehadiran").value;
            let bonus = document.getElementById("bonus").value;
            let kasbon = document.getElementById("kasbon").value;
            let totalSemua = document.getElementById('total');
            let total = parseInt(gapok) + parseInt(lamaKerja) + parseInt(tBagian) + parseInt(performance) + parseInt(
                    transportasi) + parseInt(komunikasi) + parseInt(jumlah_lain) + parseInt(lembur) + parseInt(kehadiran) +
                parseInt(bonus) - parseInt(kasbon);
            totalSemua.value = total;
        }
        getTotal()
    </script>
@endpush
