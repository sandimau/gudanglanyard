@extends('layouts.app')

@section('title')
    Dashboard Marketplace Custom
@endsection

@section('content')
    <header class="header mb-4">
        <div class="container-fluid">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb my-0 ms-2">
                    <li class="breadcrumb-item">
                        <b>Dashboard Marketplace Custom</b>
                    </li>
                </ol>
            </nav>
            <div class="d-flex gap-2 align-items-center">
                <select id="filterMp" class="form-select form-select-sm" style="width: auto;">
                    @foreach ($mps as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </header>
    <div class="bg-light rounded">
        @include('layouts.includes.messages')
        <div class="row">
            <div class="col-12">
                <ul class="nav nav-tabs order-dashboard-tabs" id="grupTab" role="tablist">
                    @php $firstGrup = true; @endphp
                    @foreach ($produksis as $grup => $items)
                        @php
                            $visibleItems = $items->filter(fn($i) => !in_array($i->nama, ['finish', 'batal']));
                            if ($visibleItems->isEmpty()) {
                                continue;
                            }
                            $grupSlug = 'grup-' . $loop->index;
                            $grupCount = $visibleItems->sum(
                                fn($item) => $countsByProduksiId->get($item->id, 0),
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
                            <ul class="nav nav-tabs order-dashboard-tabs mt-3" id="orderTab-{{ $grupSlug }}"
                                role="tablist">
                                @foreach ($visibleItems as $item)
                                    @php
                                        $count = $countsByProduksiId->get($item->id, 0);
                                    @endphp
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link {{ $loop->first ? 'active' : '' }} nav-nonaktif"
                                            id="{{ $grupSlug }}-{{ $item->nama }}-tab" data-bs-toggle="tab"
                                            data-bs-target="#{{ $grupSlug }}-{{ $item->nama }}" type="button"
                                            role="tab" aria-controls="{{ $grupSlug }}-{{ $item->nama }}"
                                            aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                            {{ $item->nama }}
                                            <span class="badge bg-success rounded-pill">{{ $count }}</span>
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
                                                    $project_id = 0;

                                                    $details = $detailsByProduksiId->get($item->id, collect());

                                                    foreach ($details as $detail) {
                                                        if (!$detail->project_id) {
                                                            continue;
                                                        }

                                                        if ($project_id != $detail->project_id) {
                                                            if ($project_id != 0) {
                                                                $tampilan .= '</div></div></div>';
                                                            }

                                                            $project = $detail->projectMp;

                                                            if ($project) {
                                                                $total = $project->total ?? 0;
                                                                $buffer = $project->buffer;
                                                                $marketplace = $project->marketplace;

                                                                if ($total < 1000000) {
                                                                    $warna = 'black';
                                                                    $nominal = $total == 0 ? 0 : floor($total / 1000) . 'rb';
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

                                                                $mpKey = str_replace(' ', '_', $marketplace->nama ?? '');
                                                                $mpWarna = $marketplace->warna ?? '#6c757d';
                                                                $mpNama = $marketplace->nama ?? '';
                                                                $konsumen = $project->konsumen ?? $project->nota ?? '';

                                                                $tampilan .= "<div class='mp-item' data-mp='" . $mpKey . "'>";
                                                                $tampilan .= "<div class='order-card'><a class='popup order-card-link' href='" . url('admin/projectMpDetail/' . $detail->project_id) . "'>";
                                                                $tampilan .= "<div class='order-card-header'>";
                                                                $tampilan .= "<div class='order-card-title-row'>";
                                                                if ($mpNama) {
                                                                    $tampilan .=
                                                                        "<span class='label label-rounded order-card-kode' style='background-color: " .
                                                                        $mpWarna .
                                                                        "'>" .
                                                                        $mpNama .
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
                                                                    $konsumen .
                                                                    '</span>';
                                                                $tampilan .= '</div>';
                                                                $tampilan .= '</div>';
                                                                $tampilan .= '</a>';
                                                                $tampilan .= "<div class='order-card-products'>";
                                                            }
                                                        }

                                                        $nama_produk = $detail->produk->namaLengkap ?? ($detail->tema ?? '');

                                                        $pemprosesBadge = '';
                                                        if (!empty($detail->pemproses)) {
                                                            $pemprosesBadge =
                                                                "<span class='label label-info label-rounded order-card-pemproses' style='background-color: #" .
                                                                ltrim($detail->pemproses->warna, '#') .
                                                                ";'>" .
                                                                $detail->pemproses->nama .
                                                                '</span>';
                                                        }

                                                        $jadwalx = '';
                                                        if ($detail->projectMp->deadline) {
                                                            $waktu = $detail->deadline ?? $detail->projectMp->deadline;
                                                            $time1 = new DateTime(date('Y-m-d'));
                                                            $time2 = new DateTime($waktu);
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
                                                        $tampilan .= "<span class='order-product-name'>" . $nama_produk . '</span>';
                                                        if ($isProduksiLevel) {
                                                            $nextProduksi = $detail->produksi?->nextInFlow($detail);
                                                            if ($nextProduksi) {
                                                                $tampilan .=
                                                                    "<form class='d-inline-block' method='post' action='" .
                                                                    route('projectMpDetail.nextStatus', $detail->id) .
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

                                                        $project_id = $detail->project_id;
                                                    }

                                                    if ($project_id != 0) {
                                                        $tampilan .= '</div></div></div>';
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

    @include('admin.projectmps.partials.detail-projectmp-modal')
@endsection

@push('after-scripts')
    <script>
        @include('admin.projectmps.partials.detail-projectmp-modal-js')

        document.getElementById('filterMp').addEventListener('change', function() {
            const selected = this.value;
            const items = document.querySelectorAll('.mp-item');

            items.forEach(function(item) {
                if (selected === 'semua' || item.dataset.mp === selected) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        (function() {
            const STORAGE_GRUP = 'projectMpDashboard_grupTab';
            const STORAGE_ORDER = 'projectMpDashboard_orderTab';

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
        })();
    </script>
    <style>
        @include('admin.projectmps.partials.detail-projectmp-modal-styles')

        .order-dashboard-tabs {
            flex-wrap: nowrap;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }

        .order-dashboard-tabs .nav-link {
            white-space: nowrap;
            font-size: 0.85rem;
            padding: 0.5rem 0.75rem;
        }

        .order-dashboard-list {
            overflow-x: hidden;
        }

        .mp-item {
            transition: all 0.3s ease;
        }

        .order-card {
            display: block;
            padding: 0.5rem;
            margin-bottom: 0;
            border-radius: 8px;
            border: 1px solid var(--app-border, #dee2e6);
            background: var(--app-card-bg, #fff);
        }

        .mp-item:last-child .order-card {
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
