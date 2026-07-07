@extends('layouts.app')

@section('title')
Create Member
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Add Member</h5>
            <a href="{{ route('members.index') }}" class="btn btn-primary" data-modal-skip>back</a>
        </div>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route("members.store") }}" enctype="multipart/form-data">
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
                <label for="tgl_masuk">Tanggal Masuk</label>
                <input class="form-control date {{ $errors->has('tgl_masuk') ? 'is-invalid' : '' }}" type="date" name="tgl_masuk" id="tgl_masuk" value="{{ old('tgl_masuk') }}">
                @if($errors->has('tgl_masuk'))
                    <div class="invalid-feedback">
                        {{ $errors->first('tgl_masuk') }}
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
                <label for="tgl_gajian">Tanggal Gajian</label>
                <select class="form-select {{ $errors->has('tgl_gajian') ? 'is-invalid' : '' }}" name="tgl_gajian">
                    <option value="" disabled selected>pilih tanggal</option>
                    @foreach ($tglGaji as $key => $value)
                        <option value={{ $key }}>{{ $value }}</option>
                    @endforeach
                </select>
                @if($errors->has('tgl_gajian'))
                    <div class="invalid-feedback">
                        {{ $errors->first('tgl_gajian') }}
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
                <label>status</label>
                <select class="form-select {{ $errors->has('status') ? 'is-invalid' : '' }}" name="status">
                    <option value="" disabled selected>pilih status</option>
                    <option value="1">aktif</option>
                    <option value="0">tidak aktif</option>
                </select>
                @if($errors->has('status'))
                    <div class="invalid-feedback">
                        {{ $errors->first('status') }}
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
