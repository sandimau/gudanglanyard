@extends('layouts.app')

@section('title')
    Produk Model
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><a href="{{ route('produk-kategori-utama.index') }}" data-produk-popup="Kategori Utama"
                    class="text-decoration-none text-primary">{{ $kategori->kategoriUtama->nama }}</a> > <a
                    href="{{ route('produk-kategori.index', $kategori->kategoriUtama->id) }}" data-produk-popup="Kategori"
                    class="text-decoration-none text-primary">{{ $kategori->nama }}</a> > Produk</h5>
            <a href="{{ route('produkModel.create', ['kategori_id' => $kategori->id]) }}" data-produk-popup="Tambah Produk" class="btn btn-primary">Tambah
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
                                            <a data-produk-popup="Gambar Produk"
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
                                        <a data-produk-popup="Detail Produk"
                                            href="{{ route('produkModel.show', ['produkModel' => $produk->model_id, 'kategori_id' => $kategori->id]) }}">{{ $produk->model }}</a>
                                    @endif
                                </td>
                                <td>{{ $produk->varian }}</td>
                                <td>{{ $produk->satuan }}</td>
                                <td>Rp {{ number_format($produk->harga_beli, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($produk->harga, 0, ',', '.') }}</td>
                                <td><a href="{{ route('produk.belanja', ['produk' => $produk->produk_id]) }}" data-produk-popup="Riwayat Belanja">Rp
                                        {{ number_format($produk->hpp, 0, ',', '.') }}</a></td>
                                @foreach ($cabangs as $cabang)
                                    @php
                                        $saldo = (int) ($stokCabang[$cabang->id] ?? 0);
                                    @endphp
                                    <td class="text-center {{ $saldo < 0 ? 'text-danger' : '' }}">
                                        <a href="{{ route('produk.stok', ['produk' => $produk->produk_id]) }}" data-produk-popup="Riwayat Stok"
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

    <div class="modal fade" id="produkLinkModal" tabindex="-1" aria-labelledby="produkLinkModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-lg-down modal-dialog-scrollable modal-dialog-centered modal-xxl">
            <div class="modal-content">
                <div class="modal-header align-items-center">
                    <h5 class="modal-title flex-grow-1 mb-0" id="produkLinkModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-0 position-relative">
                    <div id="produkLinkLoading" class="position-absolute top-50 start-50 translate-middle" role="status">
                        <div class="spinner-border text-primary"><span class="visually-hidden">Memuat...</span></div>
                    </div>
                    <iframe id="produkLinkFrame" title="Detail Produk" class="w-100 border-0 h-100" style="min-height: 75vh; visibility: hidden"></iframe>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('after-styles')
    <style>
        @media (min-width: 992px) {
            #produkLinkModal .modal-dialog.modal-xxl {
                max-width: 98%;
                width: 98%;
                margin-left: auto;
                margin-right: auto;
            }

            #produkLinkModal .modal-dialog.modal-dialog-scrollable {
                height: calc(100% - 2rem);
            }
        }

        #produkLinkModal .modal-body {
            min-height: 75vh;
        }
    </style>
@endpush

@push('after-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalElement = document.getElementById('produkLinkModal');
            const frame = document.getElementById('produkLinkFrame');
            const loading = document.getElementById('produkLinkLoading');
            const modal = new bootstrap.Modal(modalElement);
            const indexUrl = new URL(@json(route('produkModel.index', ['kategori_id' => $kategori->id])));
            let opened = false;

            document.querySelectorAll('[data-produk-popup]').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
                    event.preventDefault();
                    document.getElementById('produkLinkModalTitle').textContent = link.dataset.produkPopup;
                    frame.title = link.dataset.produkPopup;
                    opened = true;
                    frame.style.visibility = 'hidden';
                    loading.classList.remove('d-none');
                    frame.src = link.href;
                    modal.show();
                });
            });

            frame.addEventListener('load', function () {
                if (!opened) return;

                try {
                    const page = frame.contentWindow;
                    const url = new URL(page.location.href);
                    // Form simpan dan tombol Kembali menuju daftar: tutup popup dan muat ulang datanya.
                    if (url.pathname === indexUrl.pathname && url.searchParams.get('kategori_id') === indexUrl.searchParams.get('kategori_id')) {
                        modal.hide();
                        window.location.reload();
                        return;
                    }

                    // Halaman tujuan tetap bisa dibuka langsung, tetapi di popup tidak perlu sidebar kedua.
                    if (page.document.body && !page.document.getElementById('produk-popup-style')) {
                        const style = page.document.createElement('style');
                        style.id = 'produk-popup-style';
                        style.textContent = '.sidebar, .wrapper > header, .wrapper > footer { display: none !important; } .wrapper { padding-left: 0 !important; min-height: 0 !important; } .body { padding: 1rem !important; }';
                        page.document.head.appendChild(style);
                    }
                    page.addEventListener('beforeunload', function () {
                        frame.style.visibility = 'hidden';
                        loading.classList.remove('d-none');
                    }, { once: true });
                } catch (error) {
                    // Gambar dan tautan non-HTML tetap ditampilkan oleh browser di dalam iframe.
                }
                frame.style.visibility = 'visible';
                loading.classList.add('d-none');
            });

            modalElement.addEventListener('hidden.bs.modal', function () {
                opened = false;
                frame.style.visibility = 'hidden';
                loading.classList.add('d-none');
                frame.removeAttribute('src');
            });
        });
    </script>
@endpush
