@extends('layouts.app')

@section('title')
    Create Level
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title">add Level</h5>
                </div>
                <a href="{{ route('level.index') }}" class="btn btn-primary ">back</a>
            </div>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('level.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group mb-3">
                    <label for="nama">Nama</label>
                    <input class="form-control {{ $errors->has('nama') ? 'is-invalid' : '' }}" type="text"
                        name="nama" id="nama" value="{{ old('nama') }}">
                    @if ($errors->has('nama'))
                        <div class="invalid-feedback">
                            {{ $errors->first('nama') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="gaji_pokok">Gaji Pokok</label>
                    <input class="form-control {{ $errors->has('gaji_pokok') ? 'is-invalid' : '' }}" type="number"
                        name="gaji_pokok" id="gaji_pokok" value="{{ old('gaji_pokok') }}">
                    @if ($errors->has('gaji_pokok'))
                        <div class="invalid-feedback">
                            {{ $errors->first('gaji_pokok') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="komunikasi">Komunikasi</label>
                    <input class="form-control {{ $errors->has('komunikasi') ? 'is-invalid' : '' }}" type="number"
                        name="komunikasi" id="komunikasi" value="{{ old('komunikasi') }}">
                    @if ($errors->has('komunikasi'))
                        <div class="invalid-feedback">
                            {{ $errors->first('komunikasi') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="transportasi">Transportasi</label>
                    <input class="form-control {{ $errors->has('transportasi') ? 'is-invalid' : '' }}" type="number"
                        name="transportasi" id="transportasi" value="{{ old('transportasi') }}">
                    @if ($errors->has('transportasi'))
                        <div class="invalid-feedback">
                            {{ $errors->first('transportasi') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="kehadiran">Kehadiran</label>
                    <input class="form-control {{ $errors->has('kehadiran') ? 'is-invalid' : '' }}" type="number"
                        name="kehadiran" id="kehadiran" value="{{ old('kehadiran') }}">
                    @if ($errors->has('kehadiran'))
                        <div class="invalid-feedback">
                            {{ $errors->first('kehadiran') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="lama_kerja">Lama Kerja</label>
                    <input class="form-control {{ $errors->has('lama_kerja') ? 'is-invalid' : '' }}" type="number"
                        name="lama_kerja" id="lama_kerja" value="{{ old('lama_kerja') }}">
                    @if ($errors->has('lama_kerja'))
                        <div class="invalid-feedback">
                            {{ $errors->first('lama_kerja') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="harga_lembur">Harga Lembur</label>
                    <input class="form-control {{ $errors->has('harga_lembur') ? 'is-invalid' : '' }}" type="number"
                        name="harga_lembur" id="harga_lembur" value="{{ old('harga_lembur') }}">
                    @if ($errors->has('harga_lembur'))
                        <div class="invalid-feedback">
                            {{ $errors->first('harga_lembur') }}
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
