@extends('layouts.app')

@section('title')
    Create Kontaks
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h5>Add Kontak</h5>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('kontaks.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group mb-3">
                    <label for="nama">Nama</label>
                    <input class="form-control {{ $errors->has('nama') ? 'is-invalid' : '' }}" type="text" name="nama"
                        id="nama" value="{{ old('nama', '') }}">
                    @if ($errors->has('nama'))
                        <div class="invalid-feedback">
                            {{ $errors->first('nama') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label class="required" for="cabang_id">Cabang</label>
                    <select class="form-select {{ $errors->has('cabang_id') ? 'is-invalid' : '' }}" name="cabang_id" id="cabang_id" required>
                        @foreach(($cabangs ?? []) as $cabang)
                            <option value="{{ $cabang->id }}" {{ (int) old('cabang_id', $cabangAktif->id ?? null) === (int) $cabang->id ? 'selected' : '' }}>
                                {{ $cabang->nama }}{{ $cabang->kode ? ' ('.$cabang->kode.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @if ($errors->has('cabang_id'))
                        <div class="invalid-feedback">
                            {{ $errors->first('cabang_id') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="perusahaan">Perusahaan</label>
                    <input class="form-control {{ $errors->has('perusahaan') ? 'is-invalid' : '' }}" type="text" name="perusahaan"
                        id="perusahaan" value="{{ old('perusahaan', '') }}">
                    @if ($errors->has('perusahaan'))
                        <div class="invalid-feedback">
                            {{ $errors->first('perusahaan') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="noTelp">No Telp</label>
                    <input class="form-control {{ $errors->has('noTelp') ? 'is-invalid' : '' }}" type="text" name="noTelp"
                        id="noTelp" value="{{ old('noTelp', '') }}">
                    @if ($errors->has('noTelp'))
                        <div class="invalid-feedback">
                            {{ $errors->first('noTelp') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="email">Email</label>
                    <input class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" type="email" name="email"
                        id="email" value="{{ old('email', '') }}">
                    @if ($errors->has('email'))
                        <div class="invalid-feedback">
                            {{ $errors->first('email') }}
                        </div>
                    @endif
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input name="konsumen" class="form-check-input {{ $errors->has('konsumen') ? 'is-invalid' : '' }}" type="checkbox" value="1" id="flexCheckKonsumen">
                        <label class="form-check-label" for="flexCheckKonsumen">
                            Konsumen
                        </label>
                        @if ($errors->has('konsumen'))
                        <div class="invalid-feedback">
                            {{ $errors->first('konsumen') }}
                        </div>
                    @endif
                    </div>
                    <div class="form-check">
                        <input name="supplier" class="form-check-input" type="checkbox" value="1" id="flexCheckSupplier">
                        <label class="form-check-label" for="flexCheckSupplier">
                            Supplier
                        </label>
                    </div>
                    <div class="form-check">
                        <input name="marketplace" class="form-check-input" type="checkbox" value="1" id="flexCheckMarketplace">
                        <label class="form-check-label" for="flexCheckMarketplace">
                            Marketplace
                        </label>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label for="alamat">Alamat</label>
                    <textarea class="form-control {{ $errors->has('alamat') ? 'is-invalid' : '' }}" name="alamat" id=""
                        cols="30" rows="10">{{ old('alamat', '') }}</textarea>
                    @if ($errors->has('alamat'))
                        <div class="invalid-feedback">
                            {{ $errors->first('alamat') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="ar_id">cs</label>
                    <select class="form-select {{ $errors->has('ar_id') ? 'is-invalid' : '' }}" aria-label="Default select example" name="ar_id" id="ar_id">
                        @foreach($ars as $entry)
                            <option value="{{ $entry->id }}" {{ old('ar_id') == $entry->id ? 'selected' : '' }}>{{ $entry->member->nama_lengkap }}</option>
                        @endforeach
                    </select>
                    @if($errors->has('ar_id'))
                        <div class="invalid-feedback">
                            {{ $errors->first('ar_id') }}
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
