@extends('layouts.app')

@section('title')
    Edit Akun
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            Edit Akun
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('akunDetails.update', [$akunDetail->id]) }}"
                enctype="multipart/form-data">
                @method('patch')
                @csrf
                <div class="form-group mb-3">
                    <label class="required" for="nama">Nama Akun</label>
                    <input class="form-control {{ $errors->has('nama') ? 'is-invalid' : '' }}" type="text" name="nama"
                        id="nama" value="{{ old('nama', $akunDetail->nama) }}" required>
                    @if ($errors->has('nama'))
                        <div class="invalid-feedback">
                            {{ $errors->first('nama') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label class="required" for="akun_kategori_id">Kategori Akun</label>
                    <select class="form-select select2 {{ $errors->has('akun_kategori') ? 'is-invalid' : '' }}"
                        name="akun_kategori_id" id="akun_kategori_id">
                        @foreach ($akun_kategoris as $id => $entry)
                            <option value="{{ $id }}"
                                {{ (old('akun_kategori_id') ? old('akun_kategori_id') : $akunDetail->akun_kategori->id ?? '') == $id ? 'selected' : '' }}>
                                {{ $entry }}</option>
                        @endforeach
                    </select>
                    @if ($errors->has('akun_kategori'))
                        <div class="invalid-feedback">
                            {{ $errors->first('akun_kategori') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <button class="btn btn-danger" type="submit">
                        {{ trans('save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
