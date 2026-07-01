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
                <ul class="nav nav-tabs" id="mpTab" role="tablist">
                    @foreach ($produksi as $item)
                        @if ($item->nama != 'finish' && $item->nama != 'batal')
                            @php
                                // Count project_mp_details with custom = 1 for this produksi
                                $count = $item->projectMpDetail()
                                    ->whereHas('projectMp', function($q) {
                                        $q->whereHas('buffer', function($q2) {
                                            $q2->where('custom', 1)
                                               ->where(function($q3) {
                                                   $q3->where('status', 'PROCESSED')
                                                      ->orWhere('status', 'READY_TO_SHIP')
                                                      ->orWhere('status', 'UNPAID');
                                               });
                                        });
                                    })
                                    ->count();
                            @endphp
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $loop->first ? 'active' : '' }} nav-nonaktif"
                                    id="{{ $item->nama }}-tab" data-bs-toggle="tab"
                                    data-bs-target="#{{ $item->nama }}" type="button" role="tab"
                                    aria-controls="{{ $item->nama }}" aria-selected="false">
                                    {{ $item->nama }}
                                    <span class="badge bg-success rounded-pill">{{ $count }}</span>
                                </button>
                            </li>
                        @endif
                    @endforeach
                </ul>
                <div class="tab-content" id="mpTabContent">
                    @foreach ($produksi as $item)
                        @if ($item->nama != 'finish' && $item->nama != 'batal')
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $item->nama }}"
                                role="tabpanel" aria-labelledby="{{ $item->nama }}-tab">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        @php
                                            $hasil = [];
                                            $tampilan = '';
                                            $project_id = 0;

                                            // Get project_mp_details for this produksi where custom = 1
                                            $details = $item->projectMpDetail()
                                                ->with(['projectMp.buffer', 'projectMp.marketplace', 'produk.produkModel'])
                                                ->whereHas('projectMp', function($q) {
                                                    $q->whereHas('buffer', function($q2) {
                                                        $q2->where('custom', 1)
                                                           ->where(function($q3) {
                                                               $q3->where('status', 'PROCESSED')
                                                                  ->orWhere('status', 'READY_TO_SHIP')
                                                                  ->orWhere('status', 'UNPAID');
                                                           });
                                                    });
                                                })
                                                ->orderBy('project_id')
                                                ->get();

                                            foreach ($details as $detail) {
                                                if (!$detail->project_id) {
                                                    continue;
                                                }

                                                if ($project_id != $detail->project_id) {
                                                    if ($project_id != 0) {
                                                        $tampilan .= '<div class="pull-right"></div></a></div>';
                                                    }

                                                    $warna = '';
                                                    $nominal = '';
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
                                                        $status = $buffer->status ?? '';

                                                        $tampilan .= "<div class='order-item mp-item' data-mp='" . $mpKey . "'>";
                                                        $tampilan .= "<a class='popup d-flex' href='" . url('admin/projectMpDetail/' . $detail->project_id) . "'>";
                                                        $tampilan .= "<p style='font-weight:600' class='text-default'>";

                                                        if ($mpWarna) {
                                                            $tampilan .= "<span class='label label-rounded' style='background-color: " . $mpWarna . "'>" . $mpNama . "</span>";
                                                        }

                                                        $tampilan .= "<span class='label label-rounded mr-1' style='background-color: " . $warna . "'>" . $nominal . "</span>";
                                                        $tampilan .= ($project->konsumen ?? $project->nota ?? '');

                                                        // if ($project->keterangan) {
                                                        //     $tampilan .= " <small class='text-muted'>" . $project->keterangan . "</small>";
                                                        // }

                                                        $tampilan .= "</p>";
                                                    }
                                                }

                                                // Tampilkan produk
                                                $nama_produk = $detail->produk->namaLengkap ?? ($detail->tema ?? '');

                                                $jadwalx = '';
                                                if ($detail->projectMp->deadline) {
                                                    $waktu = $detail->deadline ?? $detail->projectMp->deadline;
                                                    $time1 = new DateTime(date('Y-m-d'));
                                                    $time2 = new DateTime($waktu);
                                                    $interval = $time1->diff($time2)->format('%r%a');

                                                    $hasil = $interval;
                                                    if ($interval == 0) {
                                                        $hasil = ' hari ini';
                                                        $class = 'warning';
                                                    }
                                                    if ($interval == 1) {
                                                        $hasil = ' besok';
                                                        $class = 'info';
                                                    }
                                                    if ($interval > 1) {
                                                        $hasil = $interval . ' hari lagi';
                                                        $class = 'success';
                                                    }
                                                    if ($interval < 0) {
                                                        $hasil = $interval . ' hari';
                                                        $class = 'danger';
                                                    }

                                                    $jadwalx =
                                                        " <small> <span class='badge text-white text-bg-" .
                                                        $class .
                                                        "''>" .
                                                        $hasil .
                                                        '</span></small>';
                                                }

                                                $tampilan .= "<span style='color:#636363; padding-right:5px;'>" . $nama_produk . $jadwalx . "</span>";

                                                $project_id = $detail->project_id;
                                            }

                                            if ($project_id != 0) {
                                                $tampilan .= '<div class="pull-right"></div></a></div>';
                                            }

                                            echo $tampilan ?: '<p class="text-muted">Tidak ada data</p>';
                                        @endphp
                                    </div>
                                </div>
                            </div>
                        @endif
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
    </script>
    <style>
        a {
            text-decoration: none;
        }

        .text-default {
            font-weight: 700 !important;
            margin: 0;
            padding: 10px 5px;
            color: #398bf7 !important;
        }

        .label {
            font-weight: 400;
            font-size: 13px;
            color: #ffffff;
            padding: 2px 5px;
            border-radius: 5px;
            margin-right: 8px;
        }

        .popup {
            align-items: center;
            border-bottom: 1px solid #e9e9e9;
        }

        .popup:hover {
            background-color: #e0e0e0;
            border-radius: 6px;
        }

        .order-item {
            transition: all 0.3s ease;
        }

        @include('admin.projectmps.partials.detail-projectmp-modal-styles')
    </style>
@endpush
