@extends('layouts.app')

@section('title')
    Semua Produk
@endsection

@section('content')
    @php
        $sortLink = function ($column, $label) use ($sort, $direction) {
            $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
            $url = route('produks.index', array_filter([
                'search' => request('search'),
                'sort' => $column,
                'direction' => $nextDirection,
            ]));
            $icon = '';
            if ($sort === $column) {
                $icon = $direction === 'asc' ? ' ↑' : ' ↓';
            }
            return '<a href="' .
                e($url) .
                '" class="text-decoration-none text-dark">' .
                e($label) .
                $icon .
                '</a>';
        };
    @endphp
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Semua Produk</h5>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('produks.index') }}" method="GET" class="mb-3">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">
                <div class="input-group" style="max-width: 420px;">
                    <input type="text" name="search" class="form-control"
                        placeholder="Cari nama atau SKU..." value="{{ request('search') }}">
                    <button type="submit" class="btn btn-primary">Cari</button>
                    @if (request('search'))
                        <a href="{{ route('produks.index', ['sort' => $sort, 'direction' => $direction]) }}"
                            class="btn btn-secondary">Reset</a>
                    @endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>{!! $sortLink('sku', 'SKU') !!}</th>
                            <th>{!! $sortLink('gambar', 'Gambar') !!}</th>
                            <th>{!! $sortLink('kategori', 'Kategori') !!}</th>
                            <th>{!! $sortLink('nama', 'Nama') !!}</th>
                            <th>{!! $sortLink('varian', 'varian') !!}</th>
                            <th>{!! $sortLink('harga_beli', 'harga beli') !!}</th>
                            <th>{!! $sortLink('harga_jual', 'harga jual') !!}</th>
                            <th>{!! $sortLink('hpp', 'hpp') !!}</th>
                            <th>{!! $sortLink('stok', 'stok') !!}</th>
                            <th>action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $prevModel = null;
                        @endphp
                        @foreach ($produks as $produk)
                            @php
                                $showModel = $prevModel !== $produk->model_id;
                                $prevModel = $produk->model_id;
                            @endphp
                            <tr>
                                <td>{{ $produk->produk_id }}</td>
                                <td>
                                    @if ($produk->gambar && $showModel)
                                        <a class="test-popup-link"
                                            href="{{ asset('uploads/produk/' . $produk->gambar) }}">
                                            <img style="height: 60px"
                                                src="{{ url('uploads/produk/' . $produk->gambar) }}" alt="">
                                        </a>
                                    @endif
                                </td>
                                <td>
                                    @if ($showModel)
                                        {{ $produk->kategori_utama }} &gt; {{ $produk->kategori }}
                                    @endif
                                </td>
                                <td>
                                    @if ($showModel)
                                        {{ $produk->model }}
                                    @endif
                                </td>
                                <td>{{ $produk->varian }}</td>
                                <td>Rp {{ number_format($produk->harga_beli, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($produk->harga, 0, ',', '.') }}</td>
                                <td>
                                    <a href="{{ route('produk.belanja', ['produk' => $produk->produk_id]) }}">Rp
                                        {{ number_format($produk->hpp, 0, ',', '.') }}</a>
                                </td>
                                <td>
                                    <a
                                        href="{{ route('produk.stok', ['produk' => $produk->produk_id]) }}">{{ $produk->lastStok ?? $produk->lastStokRecord() }}</a>
                                </td>
                                <td>
                                    <a href="{{ route('produks.edit', $produk->produk_id) }}"
                                        class="btn btn-primary btn-sm">
                                        Edit
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $produks->links() }}
            </div>
        </div>
    </div>
@endsection
