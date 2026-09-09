@extends('layouts.app')

@section('title')
    Produk Model
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><a href="{{ route('produk-kategori-utama.index') }}"
                    class="text-decoration-none text-primary">{{ $kategori->kategoriUtama->nama }}</a> > <a
                    href="{{ route('produk-kategori.index', $kategori->kategoriUtama->id) }}"
                    class="text-decoration-none text-primary">{{ $kategori->nama }}</a> > Produk</h5>
            <a href="{{ route('produkModel.create', ['kategori_id' => $kategori->id]) }}" class="btn btn-primary">Tambah
                Produk</a>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Gambar</th>
                            <th>Nama</th>
                            <th>varian</th>
                            <th>Satuan</th>
                            <th>harga beli</th>
                            <th>harga jual</th>
                            <th>hpp</th>
                            @foreach ($cabangs as $cabang)
                                <th class="text-center text-nowrap">
                                    Stok {{ $cabang->kode ?: $cabang->nama }}
                                </th>
                            @endforeach
                            <th class="text-center">Total</th>
                            @role('super')
                                <th>action</th>
                            @endrole
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $prevModel = null;
                        @endphp
                        @foreach ($produks as $produk)
                            @php
                                $showModel = $prevModel !== $produk->model;
                                $prevModel = $produk->model;
                                $stokCabang = $stokByProdukCabang[$produk->produk_id] ?? [];
                                $totalStok = array_sum($stokCabang);
                            @endphp
                            <tr>
                                <td>{{ $produk->produk_id }}</td>
                                <td>
                                    @if ($produk->gambar)
                                        @if ($showModel)
                                            <a class="test-popup-link"
                                                href="{{ asset('uploads/produk/' . $produk->gambar) }}">
                                                <img style="height: 60px"
                                                    src="{{ url('uploads/produk/' . $produk->gambar) }}" alt=""
                                                    srcset="">
                                            </a>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @if ($showModel)
                                        <a
                                            href="{{ route('produkModel.show', ['produkModel' => $produk->model_id, 'kategori_id' => $kategori->id]) }}">{{ $produk->model }}</a>
                                    @endif
                                </td>
                                <td>{{ $produk->varian }}</td>
                                <td>{{ $produk->satuan }}</td>
                                <td>Rp {{ number_format($produk->harga_beli, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($produk->harga, 0, ',', '.') }}</td>
                                <td><a href="{{ route('produk.belanja', ['produk' => $produk->produk_id]) }}">Rp
                                        {{ number_format($produk->hpp, 0, ',', '.') }}</a></td>
                                @foreach ($cabangs as $cabang)
                                    @php
                                        $saldo = (int) ($stokCabang[$cabang->id] ?? 0);
                                    @endphp
                                    <td class="text-center {{ $saldo < 0 ? 'text-danger' : '' }}">
                                        <a href="{{ route('produk.stok', ['produk' => $produk->produk_id]) }}"
                                            title="Stok {{ $cabang->nama }}">
                                            {{ $saldo }}
                                        </a>
                                    </td>
                                @endforeach
                                <td class="text-center fw-semibold {{ $totalStok < 0 ? 'text-danger' : '' }}">
                                    {{ $totalStok }}
                                </td>
                                @role('super')
                                    <td>
                                        <form
                                            action="{{ route('produk.destroy', ['produk' => $produk->produk_id, 'kategori' => $kategori->id]) }}"
                                            method="POST" style="display: inline;"
                                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </td>
                                @endrole

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
