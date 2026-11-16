@extends('layouts.app')

@section('title')
    Add Produk Model
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Tambah Produk</h5>
    </div>
    <div class="card-body">
        @include('produkModel.partials.create-form')
    </div>
</div>
@endsection
