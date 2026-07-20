@extends('layouts.app')

@section('title')
Create Member
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Add Freelance</h5>
            <a href="{{ route('members.freelance') }}" class="btn btn-primary" data-modal-skip>back</a>
        </div>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route("freelance.store") }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="nama_lengkap">Nama Lengkap</label>
                <input class="form-control {{ $errors->has('nama_lengkap') ? 'is-invalid' : '' }}" type="text" name="nama_lengkap" id="nama_lengkap" value="{{ old('nama_lengkap', '') }}">
                @if($errors->has('nama_lengkap'))
                    <div class="invalid-feedback">
                        {{ $errors->first('nama_lengkap') }}
                    </div>
                @endif
            </div>
            <div class="form-group">
                <label for="tgl_lahir">Tanggal Lahir</label>
                <input class="form-control date {{ $errors->has('tgl_lahir') ? 'is-invalid' : '' }}" type="date" name="tgl_lahir" id="tgl_lahir" value="{{ old('tgl_lahir') }}">
                @if($errors->has('tgl_lahir'))
                    <div class="invalid-feedback">
                        {{ $errors->first('tgl_lahir') }}
                    </div>
                @endif
            </div>
            <div class="form-group">
                <label for="tempat_lahir">Tempat Lahir</label>
                <input class="form-control {{ $errors->has('tempat_lahir') ? 'is-invalid' : '' }}" type="text" name="tempat_lahir" id="tempat_lahir" value="{{ old('tempat_lahir', '') }}">
                @if($errors->has('tempat_lahir'))
                    <div class="invalid-feedback">
                        {{ $errors->first('tempat_lahir') }}
                    </div>
                @endif
            </div>
            <div class="form-group">
                <label for="alamat">Alamat</label>
                <textarea class="form-control {{ $errors->has('alamat') ? 'is-invalid' : '' }}" name="alamat" id="" cols="30" rows="10">{{ old('alamat', '') }}</textarea>
                @if($errors->has('alamat'))
                    <div class="invalid-feedback">
                        {{ $errors->first('alamat') }}
                    </div>
                @endif
            </div>
            <div class="form-group">
                <label for="no_telp">No Telp</label>
                <input class="form-control {{ $errors->has('no_telp') ? 'is-invalid' : '' }}" type="text" name="no_telp" id="no_telp" value="{{ old('no_telp', '') }}">
                @if($errors->has('no_telp'))
                    <div class="invalid-feedback">
                        {{ $errors->first('no_telp') }}
                    </div>
                @endif
            </div>
            <div class="form-group">
                <label for="no_rek">No Rek</label>
                <input class="form-control {{ $errors->has('no_rek') ? 'is-invalid' : '' }}" type="text" name="no_rek" id="no_rek" value="{{ old('no_rek', '') }}">
                @if($errors->has('no_rek'))
                    <div class="invalid-feedback">
                        {{ $errors->first('no_rek') }}
                    </div>
                @endif
            </div>
            <div class="form-group">
                <label for="no_rek">upah</label>
                <input class="form-control {{ $errors->has('upah') ? 'is-invalid' : '' }}" type="number" name="upah" id="upah" value="{{ old('upah', '') }}">
                @if($errors->has('upah'))
                    <div class="invalid-feedback">
                        {{ $errors->first('upah') }}
                    </div>
                @endif
            </div>
            <div class="form-group">
                <label for="lembur">lembur</label>
                <input class="form-control {{ $errors->has('lembur') ? 'is-invalid' : '' }}" type="number" name="lembur" id="lembur" value="{{ old('lembur', '') }}">
                @if($errors->has('lembur'))
                    <div class="invalid-feedback">
                        {{ $errors->first('lembur') }}
                    </div>
                @endif
            </div>
            <div class="form-group">
                <label for="tipe_kerja">Tipe Kerja</label>
                <select class="form-select {{ $errors->has('tipe_kerja') ? 'is-invalid' : '' }}" name="tipe_kerja" id="tipe_kerja">
                    <option value="wfo" {{ old('tipe_kerja', 'wfo') == 'wfo' ? 'selected' : '' }}>WFO (Kerja di kantor)</option>
                    <option value="wfh" {{ old('tipe_kerja') == 'wfh' ? 'selected' : '' }}>WFH (Kerja dari rumah)</option>
                </select>
                @if($errors->has('tipe_kerja'))
                    <div class="invalid-feedback">
                        {{ $errors->first('tipe_kerja') }}
                    </div>
                @endif
            </div>
            <div class="form-group">
                <button class="btn btn-primary mt-4" type="submit">
                    save
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
