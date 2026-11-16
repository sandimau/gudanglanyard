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
            <a href="{{ route('produkModel.create', ['kategori_id' => $kategori->id]) }}" id="tambahProduk" class="btn btn-primary">Tambah
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
                            @if ($kategori->kategoriUtama->is_custom)<th>Resep / SKU Shopee</th>@endif
                            <th>Gambar</th>
                            <th>Nama</th>
                            <th>varian</th>
                            <th>Satuan</th>
                            @unless ($kategori->kategoriUtama->is_custom)
                                <th>harga beli</th>
                            @endunless
                            <th>harga jual</th>
                            <th>hpp</th>
                            @if ($kategori->kategoriUtama->is_custom)
                                <th>Margin</th>
                            @endif
                            @if ($tampilkanStok)
                                @foreach ($cabangs as $cabang)
                                    <th class="text-center text-nowrap">
                                        Stok {{ $cabang->kode ?: $cabang->nama }}
                                    </th>
                                @endforeach
                                <th class="text-center">Total</th>
                            @endif
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
                                $stokCabang = $tampilkanStok ? ($stokByProdukCabang[$produk->produk_id] ?? []) : [];
                                $totalStok = $tampilkanStok ? array_sum($stokCabang) : 0;
                            @endphp
                            <tr>
                                <td>{{ $produk->produk_id }}</td>
                                @if ($kategori->kategoriUtama->is_custom)
                                    <td><a href="{{ route('produkCustom.resep', $produk->produk_id) }}" data-produk-popup="Atur Resep">Atur resep &amp; salin SKU</a></td>
                                @endif
                                <td>
                                    @if ($produk->gambar)
                                        @if ($showModel)
                                            <a data-produk-popup="Gambar Produk"
                                                href="{{ \App\Models\ProdukModel::gambarUrl($produk->gambar) }}">
                                                <img style="height: 60px"
                                                    src="{{ \App\Models\ProdukModel::gambarUrl($produk->gambar) }}" alt=""
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
                                @unless ($kategori->kategoriUtama->is_custom)
                                    <td>Rp {{ number_format($produk->harga_beli, 0, ',', '.') }}</td>
                                @endunless
                                <td>Rp {{ number_format($produk->harga, 0, ',', '.') }}</td>
                                <td>
                                    @if ($kategori->kategoriUtama->is_custom)
                                        <a href="{{ route('produkCustom.resep', $produk->produk_id) }}" data-produk-popup="Atur Resep">Rp {{ number_format($produk->hpp, 0, ',', '.') }}</a>
                                    @else
                                        <a href="{{ route('produk.belanja', ['produk' => $produk->produk_id]) }}" data-produk-popup="Riwayat Belanja">Rp {{ number_format($produk->hpp, 0, ',', '.') }}</a>
                                    @endif
                                </td>
                                @if ($kategori->kategoriUtama->is_custom)
                                    @php
                                        $hargaJual = (float) $produk->harga;
                                        $margin = $hargaJual - (float) $produk->hpp;
                                    @endphp
                                    <td class="{{ $hargaJual > 0 && $margin < 0 ? 'text-danger' : '' }}">
                                        @if ($hargaJual > 0)
                                            Rp {{ number_format($margin, 0, ',', '.') }}
                                            <small>({{ number_format($margin / $hargaJual * 100, 1, ',', '.') }}%)</small>
                                        @else
                                            —
                                        @endif
                                    </td>
                                @endif
                                @if ($tampilkanStok)
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
                                @endif
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

    <div class="modal fade" id="tambahProdukModal" tabindex="-1" aria-labelledby="tambahProdukModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tambahProdukModalTitle">Tambah Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    @include('produkModel.partials.create-form', ['inModal' => true])
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="produkLinkModal" tabindex="-1" aria-labelledby="produkLinkModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-lg-down modal-dialog-scrollable modal-dialog-centered modal-xxl">
            <div class="modal-content">
                <div class="modal-header align-items-center">
                    <h5 class="modal-title flex-grow-1 mb-0" id="produkLinkModalTitle"></h5>
                    <a id="produkLinkOpen" class="btn btn-sm btn-outline-primary me-3" target="_blank" rel="noopener noreferrer">Buka halaman</a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body" id="produkLinkBody"></div>
            </div>
        </div>
    </div>
@endsection

@push('after-styles')
    <link rel="stylesheet" href="{{ asset('js/autocomplete.css') }}">
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

        #produkLinkModal .modal-body { min-height: 75vh; }
    </style>
@endpush

@push('after-scripts')
    <script src="{{ asset('js/autocomplete.min.js') }}"></script>
    <script src="{{ asset('js/produk-custom-resep.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tambahModal = new bootstrap.Modal(document.getElementById('tambahProdukModal'));
            document.getElementById('tambahProduk').addEventListener('click', function (event) {
                if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
                event.preventDefault();
                tambahModal.show();
            });
            @if ($errors->any())
                tambahModal.show();
            @endif
            const modalElement = document.getElementById('produkLinkModal');
            const body = document.getElementById('produkLinkBody');
            const title = document.getElementById('produkLinkModalTitle');
            const openLink = document.getElementById('produkLinkOpen');
            const modal = new bootstrap.Modal(modalElement);
            const indexUrl = new URL(@json(route('produkModel.index', ['kategori_id' => $kategori->id])));
            let controller;
            let currentUrl;
            let imageUrl;

            function isIndex(url) {
                return url.pathname === indexUrl.pathname &&
                    url.searchParams.get('kategori_id') === indexUrl.searchParams.get('kategori_id');
            }

            function showError(url, error) {
                body.replaceChildren();
                const message = document.createElement('div');
                message.className = 'alert alert-danger m-3';
                message.textContent = 'Halaman tidak dapat dimuat di popup' +
                    (error && error.message ? ' (' + error.message + ')' : '') + '. Silakan buka langsung.';
                const link = document.createElement('a');
                link.className = 'btn btn-primary ms-3';
                link.href = url;
                link.textContent = 'Buka halaman';
                body.append(message, link);
            }

            function showImage(url) {
                if (controller) controller.abort();
                controller = new AbortController();
                const signal = controller.signal;
                currentUrl = url;
                openLink.href = url;
                body.innerHTML = '<div class="d-flex justify-content-center align-items-center" style="min-height: 70vh" role="status"><div class="spinner-border text-primary"><span class="visually-hidden">Memuat...</span></div></div>';
                const image = document.createElement('img');
                image.className = 'img-fluid d-block mx-auto';
                image.alt = title.textContent;
                image.onload = function () {
                    if (!signal.aborted) body.replaceChildren(image);
                };
                image.onerror = function () {
                    if (!signal.aborted) showError(url);
                };
                image.src = url;
            }

            async function loadPage(url, options) {
                const fallbackUrl = options ? currentUrl : url;
                if (controller) controller.abort();
                if (imageUrl) URL.revokeObjectURL(imageUrl);
                imageUrl = null;
                controller = new AbortController();
                const signal = controller.signal;
                currentUrl = url;
                openLink.href = url;
                body.innerHTML = '<div class="d-flex justify-content-center align-items-center" style="min-height: 70vh" role="status"><div class="spinner-border text-primary"><span class="visually-hidden">Memuat...</span></div></div>';

                try {
                    const response = await fetch(url, { ...options, signal, credentials: 'same-origin', referrer: fallbackUrl });
                    if (signal.aborted) return;
                    if (!response.ok) throw new Error('HTTP ' + response.status);
                    const destination = new URL(response.url);
                    if (destination.origin !== window.location.origin) throw new Error('Halaman di luar situs');
                    if (isIndex(destination)) {
                        window.location.reload();
                        return;
                    }
                    currentUrl = destination.href;
                    openLink.href = destination.href;
                    const contentType = response.headers.get('content-type') || '';
                    if (contentType.startsWith('image/')) {
                        imageUrl = URL.createObjectURL(await response.blob());
                        if (signal.aborted) return;
                        const image = document.createElement('img');
                        image.src = imageUrl;
                        image.alt = title.textContent;
                        image.className = 'img-fluid d-block mx-auto';
                        body.replaceChildren(image);
                        return;
                    }
                    if (!contentType.includes('text/html')) throw new Error('Bukan halaman HTML');
                    const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                    if (signal.aborted) return;
                    const content = page.querySelector('.wrapper .body .container-fluid');
                    if (!content) throw new Error('Konten tidak ditemukan');
                    body.replaceChildren(...Array.from(content.children));
                    body.scrollTop = 0;
                    window.initProdukCustomResep(body);
                } catch (error) {
                    if (!signal.aborted) {
                        openLink.href = fallbackUrl;
                        showError(fallbackUrl, error);
                    }
                }
            }

            document.querySelectorAll('[data-produk-popup]').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
                    event.preventDefault();
                    title.textContent = link.dataset.produkPopup;
                    modal.show();
                    if (link.querySelector('img')) {
                        showImage(link.href);
                    } else {
                        loadPage(link.href);
                    }
                });
            });

            body.addEventListener('click', function (event) {
                const link = event.target.closest('a[href]');
                if (!link || link.target === '_blank' || link.hasAttribute('download') ||
                    event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
                const url = new URL(link.href);
                if (url.origin !== window.location.origin || !['http:', 'https:'].includes(url.protocol)) return;
                if (url.hash && url.pathname === new URL(currentUrl).pathname && url.search === new URL(currentUrl).search) return;
                event.preventDefault();
                if (isIndex(url)) {
                    window.location.reload();
                } else {
                    loadPage(url.href);
                }
            });

            body.addEventListener('submit', function (event) {
                const form = event.target;
                if (!(form instanceof HTMLFormElement)) return;
                if (event.defaultPrevented) return;
                event.preventDefault();
                const url = new URL(form.action || currentUrl);
                const data = new FormData(form, event.submitter);
                if (form.method.toLowerCase() === 'get') {
                    url.search = new URLSearchParams(data).toString();
                    loadPage(url.href);
                } else {
                    loadPage(url.href, { method: form.method, body: data });
                }
            });

            modalElement.addEventListener('hidden.bs.modal', function () {
                if (controller) controller.abort();
                if (imageUrl) URL.revokeObjectURL(imageUrl);
                imageUrl = null;
                body.replaceChildren();
            });
        });
    </script>
@endpush
