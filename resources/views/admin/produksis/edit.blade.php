@extends('layouts.app')

@section('title')
    Edit Produksi
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h5>Edit Produksi</h5>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('produksis.update', [$produksi->id]) }}" enctype="multipart/form-data">
                @method('patch')
                @csrf
                <div class="form-group">
                    <label for="nama">Nama</label>
                    <input class="form-control {{ $errors->has('nama') ? 'is-invalid' : '' }}" type="text" name="nama" id="nama" value="{{ old('nama',$produksi->nama) }}">
                    @if($errors->has('nama'))
                        <div class="invalid-feedback">
                            {{ $errors->first('nama') }}
                        </div>
                    @endif
                </div>
                <div class="form-group">
                    <label for="warna">Warna</label>
                    <input class="form-control {{ $errors->has('warna') ? 'is-invalid' : '' }}" type="color" name="warna" id="warna" value="{{ old('warna',$produksi->warna) }}">
                    @if($errors->has('warna'))
                        <div class="invalid-feedback">
                            {{ $errors->first('warna') }}
                        </div>
                    @endif
                </div>
                <div class="form-group">
                    <label>grup</label>
                    <select class="form-select {{ $errors->has('warna') ? 'is-invalid' : '' }}" aria-label="Default select example" name="grup" >
                        <option>pilih grup</option>
                        <option value="awal" {{ $produksi->grup == 'awal' ? 'selected' : '' }}>awal</option>
                        <option value="Desain" {{ $produksi->grup == 'Desain' ? 'selected' : '' }}>Desain</option>
                        <option value="Setting" {{ $produksi->grup == 'Setting' ? 'selected' : '' }}>Setting</option>
                        <option value="Produksi ID Card" {{ $produksi->grup == 'Produksi ID Card' ? 'selected' : '' }}>Produksi ID Card</option>
                        <option value="Produksi Lanyard" {{ $produksi->grup == 'Produksi Lanyard' ? 'selected' : '' }}>Produksi Lanyard</option>
                        <option value="Selesai" {{ $produksi->grup == 'Selesai' ? 'selected' : '' }}>Selesai</option>
                        <option value="batal" {{ $produksi->grup == 'batal' ? 'selected' : '' }}>batal</option>
                    </select>
                    @if($errors->has('grup'))
                        <div class="invalid-feedback">
                            {{ $errors->first('grup') }}
                        </div>
                    @endif
                </div>
                <div class="form-group">
                    <label for="urutan">Urutan</label>
                    <input class="form-control {{ $errors->has('urutan') ? 'is-invalid' : '' }}" type="number" name="urutan" id="urutan" value="{{ old('urutan',$produksi->urutan) }}">
                    @if($errors->has('urutan'))
                        <div class="invalid-feedback">
                            {{ $errors->first('urutan') }}
                        </div>
                    @endif
                </div>
                <div class="form-group">
                    <button class="btn btn-primary mt-4" type="submit">
                        Udpate
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
