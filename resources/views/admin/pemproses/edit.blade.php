@extends('layouts.app')

@section('title')
    Edit Pemproses
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h5>Edit Pemproses</h5>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('pemproses.update', [$pemproses->id]) }}" enctype="multipart/form-data">
                @method('patch')
                @csrf
                <div class="form-group">
                    <label for="nama">Nama</label>
                    <input class="form-control {{ $errors->has('nama') ? 'is-invalid' : '' }}" type="text"
                        name="nama" id="nama" maxlength="50" value="{{ old('nama', $pemproses->nama) }}">
                    @if ($errors->has('nama'))
                        <div class="invalid-feedback">
                            {{ $errors->first('nama') }}
                        </div>
                    @endif
                </div>
                <div class="form-group">
                    <label for="kategori">Kategori</label>
                    <select class="form-select {{ $errors->has('kategori') ? 'is-invalid' : '' }}"
                        name="kategori" id="kategori">
                        @foreach ($kategoriOptions as $value => $label)
                            <option value="{{ $value }}"
                                {{ old('kategori', $pemproses->kategori) == $value ? 'selected' : '' }}>
                                {{ $label }}</option>
                        @endforeach
                    </select>
                    @if ($errors->has('kategori'))
                        <div class="invalid-feedback">
                            {{ $errors->first('kategori') }}
                        </div>
                    @endif
                </div>
                <div class="form-group">
                    <label for="warna">Warna</label>
                    <input class="form-control {{ $errors->has('warna') ? 'is-invalid' : '' }}" type="color"
                        name="warna" id="warna"
                        value="{{ old('warna', $pemproses->warna ? '#' . ltrim($pemproses->warna, '#') : '#000000') }}">
                    @if ($errors->has('warna'))
                        <div class="invalid-feedback">
                            {{ $errors->first('warna') }}
                        </div>
                    @endif
                </div>
                <div class="form-group">
                    <button class="btn btn-primary mt-4" type="submit">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
