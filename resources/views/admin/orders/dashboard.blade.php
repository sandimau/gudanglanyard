@extends('layouts.app')

@section('title')
    Proses Produksi
@endsection

@section('content')
    <header class="header mb-4">
        <div class="container-fluid dashboard-page-header">
            <nav aria-label="breadcrumb" class="dashboard-page-header__title">
                <ol class="breadcrumb my-0">
                    <li class="breadcrumb-item">
                        <b>Dashboard</b>
                    </li>
                </ol>
            </nav>
            <div class="dashboard-page-header__actions">
                <input type="search" id="orderDashboardKontakSearch"
                    class="form-control form-control-sm dashboard-page-header__search"
                    placeholder="Cari nama kontak..." autocomplete="off">
                @can('order_create')
                    <a href="{{ route('order.create') }}"
                        class="btn btn-primary rounded-pill text-white text-nowrap dashboard-page-header__btn">
                        <i class="bx bx-plus-circle"></i> Orders
                    </a>
                @endcan
            </div>
        </div>
    </header>
    <div class="bg-light rounded order-dashboard-shell">
        @include('layouts.includes.messages')
        <div class="row">
            <div class="col-12">
                <ul class="nav nav-tabs order-dashboard-tabs flex-nowrap" id="grupTab" role="tablist">
                    @php $firstGrup = true; @endphp
                    @foreach ($produksis as $grup => $items)
                        @php
                            $visibleItems = $items->filter(fn($i) => !in_array($i->nama, ['finish', 'batal']));
                            if ($visibleItems->isEmpty()) {
                                continue;
                            }
                            $grupSlug = 'grup-' . $loop->index;
                            $grupCount = $visibleItems->sum(
                                fn($item) => $orderCountsByProduksiId->get($item->id, 0),
                            );
                            $isActiveGrup = $firstGrup;
                            $firstGrup = false;
                        @endphp
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $isActiveGrup ? 'active' : '' }}" id="{{ $grupSlug }}-tab"
                                data-bs-toggle="tab" data-bs-target="#{{ $grupSlug }}" type="button" role="tab"
                                aria-controls="{{ $grupSlug }}" aria-selected="{{ $isActiveGrup ? 'true' : 'false' }}">
                                {{ $grup ?: '(Tanpa Grup)' }}
                                <span class="badge bg-primary rounded-pill">{{ $grupCount }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>

                <div class="tab-content" id="grupTabContent">
                    @php $firstGrup = true; @endphp
                    @foreach ($produksis as $grup => $items)
                        @php
                            $visibleItems = $items->filter(fn($i) => !in_array($i->nama, ['finish', 'batal']));
                            if ($visibleItems->isEmpty()) {
                                continue;
                            }
                            $grupSlug = 'grup-' . $loop->index;
                            $isActiveGrup = $firstGrup;
                            $firstGrup = false;
                        @endphp
                        <div class="tab-pane fade {{ $isActiveGrup ? 'show active' : '' }}" id="{{ $grupSlug }}"
                            role="tabpanel" aria-labelledby="{{ $grupSlug }}-tab">
                            <ul class="nav nav-tabs order-dashboard-tabs flex-nowrap mt-3" id="orderTab-{{ $grupSlug }}" role="tablist">
                                @foreach ($visibleItems as $item)
                                    @php $count = $orderCountsByProduksiId->get($item->id, 0); @endphp
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link {{ $loop->first ? 'active' : '' }} nav-nonaktif"
                                            id="{{ $grupSlug }}-{{ $item->nama }}-tab" data-bs-toggle="tab"
                                            data-bs-target="#{{ $grupSlug }}-{{ $item->nama }}" type="button"
                                            role="tab" aria-controls="{{ $grupSlug }}-{{ $item->nama }}"
                                            aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                            {{ $item->nama }}
                                            <span
                                                class="badge bg-success rounded-pill">{{ $count }}</span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="tab-content" id="orderTabContent-{{ $grupSlug }}">
                                @foreach ($visibleItems as $item)
                                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                        id="{{ $grupSlug }}-{{ $item->nama }}" role="tabpanel"
                                        aria-labelledby="{{ $grupSlug }}-{{ $item->nama }}-tab">
                                        <div class="card mb-3">
                                            <div class="card-body order-dashboard-list p-2 p-md-3">
                                                @php
                                                    $hasil = [];
                                                    $tampilan = '';
                                                    $order_id = 0;
                                                    $details = $detailsByProduksiId->get($item->id, collect());

                                                    foreach ($details as $detail) {
                                                            if (!$detail->order_id) {
                                                                continue;
                                                            }

                                                            if ($order_id != $detail->order_id) {
                                                                if ($order_id != 0) {
                                                                    $tampilan .= '</div></div>';
                                                                }

                                                                $order = $detail->order;

                                                                if ($order) {
                                                                    $total = $order->total;
                                                                    if ($total < 1000000) {
                                                                        $warna = 'black';
                                                                        if ($total == 0) {
                                                                            $nominal = 0;
                                                                        } else {
                                                                            $nominal = floor($total / 1000) . 'rb';
                                                                        }
                                                                    } else {
                                                                        if ($total <= 5000000) {
                                                                            $warna = 'green';
                                                                        } elseif ($total <= 10000000) {
                                                                            $warna = '#FAA814';
                                                                        } else {
                                                                            $warna = '#D93007';
                                                                        }

                                                                        $nominal = round($total, -5) / 1000000 . 'jt';
                                                                    }

                                                                    $konsumen = $order->kontak;
                                                                    $konsumen_detail = $order->konsumen_detail;
                                                                    $kontakSearch = mb_strtolower(
                                                                        trim(($konsumen->nama ?? '') . ' ' . ($konsumen_detail ?? '')),
                                                                    );
                                                                    $model_ar = $konsumen->ar ?? null;
                                                                    $kode = $model_ar ? $model_ar->kode : '';
                                                                    $test = $model_ar ? $model_ar->warna : '';

                                                                    $tampilan .=
                                                                        "<div class='order-card' data-kontak-search='" .
                                                                        htmlspecialchars($kontakSearch, ENT_QUOTES, 'UTF-8') .
                                                                        "'><a class='popup order-card-link' href='" .
                                                                        route('order.detail', $detail->order_id, false) .
                                                                        "'>";
                                                                    $tampilan .= "<div class='order-card-header'>";
                                                                    $tampilan .= "<div class='order-card-title-row'>";
                                                                    if ($kode) {
                                                                        $tampilan .=
                                                                            "<span class='label label-rounded order-card-kode' style='background-color: " .
                                                                            $test .
                                                                            "'>" .
                                                                            $kode .
                                                                            '</span>';
                                                                    }
                                                                    $tampilan .=
                                                                        "<span class='label label-rounded order-card-harga' style='background-color: " .
                                                                        $warna .
                                                                        "'>" .
                                                                        $nominal .
                                                                        '</span>';
                                                                    $tampilan .=
                                                                        "<span class='text-default order-card-customer'>" .
                                                                        $konsumen->nama .
                                                                        ' <span class="order-card-detail">' .
                                                                        $konsumen_detail .
                                                                        '</span></span>';
                                                                    $tampilan .= '</div>';
                                                                    $tampilan .= '</div>';
                                                                    $tampilan .= '</a>';
                                                                    $tampilan .= "<div class='order-card-products'>";
                                                                }
                                                            }

                                                            $pemprosesBadge = '';
                                                            if (!empty($detail->pemproses)) {
                                                                $pemprosesBadge =
                                                                    "<span class='label label-info label-rounded order-card-pemproses' style='background-color: #" .
                                                                    ltrim($detail->pemproses->warna, '#') .
                                                                    ";'>" .
                                                                    $detail->pemproses->nama .
                                                                    '</span>';
                                                            }

                                                            $nama_produk = $detail->produk->namaLengkap;

                                                            $jadwalx = '';
                                                            if ($detail->deathline) {
                                                                $time1 = new DateTime(date('Y-m-d'));
                                                                $time2 = new DateTime($detail->deathline);
                                                                $interval = $time1->diff($time2)->format('%r%a');

                                                                $hasil = $interval;
                                                                if ($interval == 0) {
                                                                    $hasil = 'hari ini';
                                                                    $class = 'warning';
                                                                } elseif ($interval == 1) {
                                                                    $hasil = 'besok';
                                                                    $class = 'info';
                                                                } elseif ($interval > 1) {
                                                                    $hasil = $interval . ' hari lagi';
                                                                    $class = 'success';
                                                                } else {
                                                                    $hasil = $interval . ' hari';
                                                                    $class = 'danger';
                                                                }

                                                                $jadwalx =
                                                                    "<span class='badge text-white text-bg-" .
                                                                    $class .
                                                                    " order-card-deadline'>" .
                                                                    $hasil .
                                                                    '</span>';
                                                            }

                                                            $tampilan .= "<div class='order-card-product'>";
                                                            $tampilan .=
                                                                "<span class='order-product-name'>" . $nama_produk . '</span>';
                                                            if ($isProduksiLevel) {
                                                                $nextProduksi = $detail->produksi?->nextInFlow($detail);
                                                                if ($nextProduksi) {
                                                                    $tampilan .=
                                                                        "<form class='d-inline-block' method='post' action='" .
                                                                        route('orderDetail.nextStatus', $detail->id) .
                                                                        "' style='margin:0; padding:0;'>" .
                                                                        csrf_field() .
                                                                        method_field('patch') .
                                                                        "<button type='submit' class='btn btn-primary btn-sm text-white text-nowrap' style='padding:.125rem .5rem;'>" .
                                                                        "<i class='bx bx-right-arrow-circle'></i> " .
                                                                        e($nextProduksi->nama) .
                                                                        "</button></form>";
                                                                }
                                                            }
                                                            $tampilan .= $pemprosesBadge . $jadwalx;
                                                            $tampilan .= '</div>';

                                                            $order_id = $detail->order_id;
                                                        }

                                                        if ($order_id != 0) {
                                                            $tampilan .= '</div></div>';
                                                        }

                                                    echo $tampilan ?: '<p class="text-muted">Tidak ada data</p>';
                                                @endphp
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>

    @include('admin.orders.partials.detail-order-modal')
@endsection

@push('after-scripts')
    <script>
        @include('admin.orders.partials.detail-order-modal-js')

        (function() {
            const STORAGE_GRUP = 'orderDashboard_grupTab';
            const STORAGE_ORDER = 'orderDashboard_orderTab';

            function saveActiveTabs() {
                const activeGrupPane = document.querySelector('#grupTabContent > .tab-pane.active');
                const activeOrderPane = activeGrupPane?.querySelector(':scope > .tab-content > .tab-pane.active');

                if (activeGrupPane) {
                    sessionStorage.setItem(STORAGE_GRUP, activeGrupPane.id);
                }
                if (activeOrderPane) {
                    sessionStorage.setItem(STORAGE_ORDER, activeOrderPane.id);
                }
            }

            function restoreDashboardTabs() {
                const savedOrder = sessionStorage.getItem(STORAGE_ORDER);
                const savedGrup = sessionStorage.getItem(STORAGE_GRUP)
                    || (savedOrder && savedOrder.match(/^(grup-\d+)/)?.[1]);

                if (!savedGrup && !savedOrder) {
                    return;
                }

                const activateOrder = () => {
                    if (!savedOrder) {
                        return;
                    }
                    const orderTabBtn = document.getElementById(savedOrder + '-tab');
                    if (orderTabBtn) {
                        bootstrap.Tab.getOrCreateInstance(orderTabBtn).show();
                    }
                };

                if (savedGrup) {
                    const grupTabBtn = document.getElementById(savedGrup + '-tab');
                    if (grupTabBtn) {
                        if (!grupTabBtn.classList.contains('active')) {
                            grupTabBtn.addEventListener('shown.bs.tab', activateOrder, { once: true });
                            bootstrap.Tab.getOrCreateInstance(grupTabBtn).show();
                        } else {
                            activateOrder();
                        }
                        return;
                    }
                }

                activateOrder();
            }

            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function(tab) {
                    tab.addEventListener('shown.bs.tab', saveActiveTabs);
                });

                document.querySelectorAll('form[action*="next-status"]').forEach(function(form) {
                    form.addEventListener('submit', saveActiveTabs);
                });

                restoreDashboardTabs();
            });

            const searchInput = document.getElementById('orderDashboardKontakSearch');
            if (searchInput) {
                document.querySelectorAll('.order-dashboard-tabs .badge').forEach(function(badge) {
                    badge.dataset.originalCount = badge.textContent.trim();
                });

                function updateTabBadges(hasFilter) {
                    document.querySelectorAll('.order-dashboard-tabs [data-bs-toggle="tab"]').forEach(function(tabBtn) {
                        const targetId = (tabBtn.getAttribute('data-bs-target') || '').replace('#', '');
                        const pane = targetId ? document.getElementById(targetId) : null;
                        const badge = tabBtn.querySelector('.badge');

                        if (!pane || !badge) {
                            return;
                        }

                        const count = pane.querySelectorAll('.order-card:not(.order-dashboard-hidden)').length;
                        badge.textContent = hasFilter ? count : (badge.dataset.originalCount || count);
                    });
                }

                function filterByKontak() {
                    const query = searchInput.value.trim().toLowerCase();
                    const hasFilter = query.length > 0;

                    document.querySelectorAll('.order-dashboard-list').forEach(function(list) {
                        const cards = list.querySelectorAll('.order-card');
                        let visibleCount = 0;

                        cards.forEach(function(card) {
                            const text = card.dataset.kontakSearch || '';
                            const match = !hasFilter || text.includes(query);
                            card.classList.toggle('order-dashboard-hidden', !match);
                            if (match) {
                                visibleCount++;
                            }
                        });

                        let emptyMsg = list.querySelector('.order-dashboard-empty-filter');
                        if (cards.length > 0 && visibleCount === 0) {
                            if (!emptyMsg) {
                                emptyMsg = document.createElement('p');
                                emptyMsg.className = 'text-muted order-dashboard-empty-filter mb-0';
                                emptyMsg.textContent = 'Tidak ada data untuk pencarian ini';
                                list.appendChild(emptyMsg);
                            }
                            emptyMsg.hidden = false;
                        } else if (emptyMsg) {
                            emptyMsg.hidden = true;
                        }
                    });

                    updateTabBadges(hasFilter);
                }

                searchInput.addEventListener('input', filterByKontak);
            }
        })();
    </script>
    <style>
        @include('admin.orders.partials.detail-order-modal-styles')

        header.header.mb-4 .container-fluid.dashboard-page-header {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            width: 100%;
        }

        .dashboard-page-header__title {
            flex: 0 1 auto;
            min-width: 0;
        }

        .dashboard-page-header__title .breadcrumb {
            margin-left: 0;
        }

        .dashboard-page-header__actions {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            justify-content: flex-end;
            gap: 0.5rem;
            flex: 1 1 auto;
            min-width: 0;
            margin-left: auto;
        }

        .dashboard-page-header__search {
            flex: 1 1 160px;
            min-width: 0;
            width: auto;
            max-width: 280px;
        }

        .dashboard-page-header__btn {
            flex: 0 0 auto;
        }

        .order-dashboard-shell {
            overflow-x: hidden;
            max-width: 100%;
        }

        .order-dashboard-tabs {
            display: flex !important;
            flex-wrap: nowrap !important;
            overflow-x: auto;
            overflow-y: hidden;
            max-width: 100%;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }

        .order-dashboard-tabs .nav-item {
            flex-shrink: 0;
        }

        .order-dashboard-tabs .nav-link {
            white-space: nowrap;
            font-size: 0.85rem;
            padding: 0.5rem 0.75rem;
        }

        .order-dashboard-list {
            overflow-x: hidden;
        }

        .order-card.order-dashboard-hidden {
            display: none !important;
        }

        .order-card {
            display: block;
            padding: 0.5rem;
            margin-bottom: 0;
            border-radius: 8px;
            border: 1px solid var(--app-border, #dee2e6);
            background: var(--app-card-bg, #fff);
        }

        .order-card:last-child {
            margin-bottom: 0;
        }

        a.popup.order-card-link {
            display: block;
            color: inherit;
            text-decoration: none;
            border-bottom: 0 !important;
        }

        a.popup.order-card-link:hover {
            color: inherit;
        }

        .order-card-header {
            margin-bottom: 0.5rem;
        }

        .order-card .order-card-title-row {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
            align-items: center !important;
            gap: 0.5rem;
        }

        .order-card .order-card-kode,
        .order-card .order-card-harga {
            flex-shrink: 0;
            margin-right: 0 !important;
            white-space: nowrap;
        }

        .order-card .order-card-customer {
            display: inline !important;
            flex: 1 1 auto;
            min-width: 0;
            font-size: 0.9rem;
            line-height: 1.35;
            word-break: break-word;
            padding: 0 !important;
            margin: 0 !important;
        }

        .order-card-detail {
            color: var(--app-text-muted, #636363) !important;
            font-weight: 400;
        }

        .order-card-products {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px dashed var(--app-border, #dee2e6);
        }

        .order-card-product {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.85rem;
        }

        .order-product-name {
            flex: 1 1 100%;
            color: #636363;
            word-break: break-word;
            line-height: 1.4;
        }

        .order-card-pemproses,
        .order-card-deadline {
            flex-shrink: 0;
        }

        .order-card-product .btn-primary {
            color: #fff;
        }

        @media (max-width: 767.98px) {
            .dashboard-page-header {
                gap: 0.5rem;
            }

            .dashboard-page-header__title {
                display: none;
            }

            .dashboard-page-header__actions {
                margin-left: 0;
                flex: 1 1 100%;
            }

            .dashboard-page-header__search {
                flex: 1 1 0;
                max-width: none;
                padding: 0.35rem 0.5rem;
                min-height: 2rem;
                height: 2rem;
                font-size: 0.8125rem;
                line-height: 1.25;
            }

            .dashboard-page-header__btn {
                padding: 0.35rem 0.75rem;
                font-size: 0.8125rem;
                min-height: 2rem;
            }

            .order-dashboard-tabs .nav-link {
                font-size: 0.8rem;
                padding: 0.45rem 0.6rem;
            }

            .order-card .order-card-title-row {
                flex-wrap: wrap !important;
                align-items: flex-start !important;
            }

            .order-card .order-card-customer {
                flex: 1 1 100%;
                margin-top: 0.15rem;
            }
        }

        @media (min-width: 768px) {
            .order-card {
                display: flex;
                align-items: flex-start;
                gap: 1rem;
            }

            a.popup.order-card-link {
                flex: 1 1 35%;
                max-width: 360px;
            }

            .order-card-header {
                margin-bottom: 0;
            }

            .order-card .order-card-title-row {
                align-items: flex-start !important;
            }

            .order-card-products {
                flex: 1;
                flex-direction: row;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.5rem 1rem;
                padding-top: 0;
                border-top: none;
                border-left: 1px dashed var(--app-border, #dee2e6);
                padding-left: 1rem;
            }

            .order-card-product {
                flex: 0 1 auto;
                flex-wrap: nowrap;
            }

            .order-product-name {
                flex: 0 1 auto;
            }
        }
    </style>
@endpush
