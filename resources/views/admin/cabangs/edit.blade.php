@extends('layouts.app')

@section('title')
    Edit Cabang
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Edit Cabang</h5>
                <a href="{{ route('cabang.index') }}" class="btn btn-primary">Kembali</a>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('cabang.update', $cabang->id) }}">
                @method('patch')
                @csrf
                <div class="form-group mb-3">
                    <label for="nama">Nama</label>
                    <input class="form-control {{ $errors->has('nama') ? 'is-invalid' : '' }}" type="text"
                        name="nama" id="nama" value="{{ old('nama', $cabang->nama) }}" required>
                    @if ($errors->has('nama'))
                        <div class="invalid-feedback">{{ $errors->first('nama') }}</div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="kode">Kode</label>
                    <input class="form-control {{ $errors->has('kode') ? 'is-invalid' : '' }}" type="text"
                        name="kode" id="kode" value="{{ old('kode', $cabang->kode) }}">
                    @if ($errors->has('kode'))
                        <div class="invalid-feedback">{{ $errors->first('kode') }}</div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="alamat">Alamat</label>
                    <textarea class="form-control {{ $errors->has('alamat') ? 'is-invalid' : '' }}" name="alamat"
                        id="alamat" rows="3">{{ old('alamat', $cabang->alamat) }}</textarea>
                    @if ($errors->has('alamat'))
                        <div class="invalid-feedback">{{ $errors->first('alamat') }}</div>
                    @endif
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="status" id="status" value="1"
                        {{ old('status', $cabang->status) ? 'checked' : '' }}>
                    <label class="form-check-label" for="status">Aktif</label>
                </div>
                <button class="btn btn-primary" type="submit">Simpan</button>
            </form>
        </div>
    </div>
@endsection
