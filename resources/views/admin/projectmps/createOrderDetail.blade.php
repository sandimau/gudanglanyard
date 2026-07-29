@extends('layouts.app')

@section('title')
    Tambah Produk Order
@endsection

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active" aria-current="page"> <a
                    href="{{ route('projectmp.detail', $projectMp->id) }}">{{ $projectMp->nota }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Tambah Produk Order</li>
        </ol>
    </nav>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('projectMpOrder.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="project_mp_id" value="{{ $projectMp->id }}">
                <div class="form-group mb-3">
                    <label for="nama" class="mb-2">Produk</label>
                    @include('admin.orderDetails.partials.produk-autocomplete', [
                        'produkId' => old('produk_id'),
                        'produkLabel' => '',
                    ])
                    @if ($errors->has('produk_id'))
                        <div class="invalid-feedback z-10">
                            {{ $errors->first('produk_id') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="tema">Tema</label>
                    <input class="form-control {{ $errors->has('tema') ? 'is-invalid' : '' }}" type="text" name="tema"
                        id="tema" value="{{ old('tema', '') }}">
                    @if ($errors->has('tema'))
                        <div class="invalid-feedback">
                            {{ $errors->first('tema') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="jumlah">Jumlah</label>
                    <input class="form-control {{ $errors->has('jumlah') ? 'is-invalid' : '' }}" type="number"
                        name="jumlah" id="jumlah" value="{{ old('jumlah', '') }}">
                    @if ($errors->has('jumlah'))
                        <div class="invalid-feedback">
                            {{ $errors->first('jumlah') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="harga">Harga</label>
                    <input class="form-control {{ $errors->has('harga') ? 'is-invalid' : '' }}" type="number"
                        name="harga" id="harga" value="{{ old('harga', '') }}">
                    @if ($errors->has('harga'))
                        <div class="invalid-feedback">
                            {{ $errors->first('harga') }}
                        </div>
                    @endif
                </div>
                @foreach ($speks as $item)
                    <div class="form-group mb-3">
                        <label for="spek">{{ $item->nama }}</label>
                        <input class="form-control" type="text" name="{{ $item->nama }}" id="spek">
                    </div>
                @endforeach
                <div class="form-group mb-3">
                    <label for="keterangan">Keterangan</label>
                    <textarea class="form-control {{ $errors->has('keterangan') ? 'is-invalid' : '' }}" name="keterangan" id=""
                        cols="30" rows="10">{{ old('keterangan', '') }}</textarea>
                    @if ($errors->has('keterangan'))
                        <div class="invalid-feedback">
                            {{ $errors->first('keterangan') }}
                        </div>
                    @endif
                </div>
                <div class="form-group mb-3">
                    <label for="deathline">Deadline</label>
                    <input class="form-control {{ $errors->has('deathline') ? 'is-invalid' : '' }}" type="date"
                        name="deathline"
                        value="{{ old('deathline', $projectMp->deadline ? \Carbon\Carbon::parse($projectMp->deadline)->format('Y-m-d') : date('Y-m-d')) }}">
                    @if ($errors->has('deathline'))
                        <div class="invalid-feedback">
                            {{ $errors->first('deathline') }}
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

@push('after-scripts')
    @include('admin.orderDetails.partials.produk-autocomplete-standalone')
@endpush
