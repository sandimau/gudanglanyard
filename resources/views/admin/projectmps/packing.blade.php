@extends('layouts.app')

@section('title')
    Packing Marketplace
@endsection

@section('content')
    <header class="header mb-4">
        <div class="container-fluid">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb my-0 ms-2">
                    <li class="breadcrumb-item">
                        <b>Packing Marketplace</b>
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
                    @foreach ($statuses as $statusKey => $statusData)
                        @php
                            $count = isset($marketplaces[$statusKey]) ? count($marketplaces[$statusKey]) : 0;
                        @endphp
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $loop->first ? 'active' : '' }} nav-nonaktif"
                                id="{{ Str::slug($statusKey) }}-tab" data-bs-toggle="tab"
                                data-bs-target="#tab-{{ Str::slug($statusKey) }}" type="button" role="tab"
                                aria-controls="tab-{{ Str::slug($statusKey) }}"
                                aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                {{ $statusData['nama'] }}
                                <span class="badge bg-success rounded-pill">{{ $count }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
                <div class="tab-content" id="mpTabContent">
                    @foreach ($statuses as $statusKey => $statusData)
                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                            id="tab-{{ Str::slug($statusKey) }}" role="tabpanel"
                            aria-labelledby="{{ Str::slug($statusKey) }}-tab">
                            <div class="card mb-3">
                                <div class="card-body">
                                    @if (isset($marketplaces[$statusKey]))
                                        @foreach ($marketplaces[$statusKey] as $projectId => $details)
                                            @php
                                                $first = $details[0];
                                                $total = $first->total ?? 0;

                                                // Format nominal
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

                                                $mpKey = str_replace(' ', '_', $first->nama_marketplace ?? '');
                                            @endphp
                                            <div class="order-item mp-item" data-mp="{{ $mpKey }}">
                                                <a class="popup d-flex"
                                                    href="{{ url('admin/projectMpDetail/' . $projectId) }}">
                                                    <p style="font-weight:600" class="text-default">
                                                        @if ($first->config_warna)
                                                            <span class="label label-rounded"
                                                                style="background-color: {{ $first->config_warna }}">
                                                                {{ $first->nama_marketplace ?? '' }}
                                                            </span>
                                                        @endif
                                                        <span class="label label-rounded mr-1"
                                                            style="background-color: {{ $warna }}">
                                                            {{ $nominal }}
                                                        </span>
                                                        {{ $first->konsumen ?? $first->nota ?? '' }}
                                                        @if ($first->keterangan)
                                                            <small class="text-muted">{{ $first->keterangan }}</small>
                                                        @endif
                                                    </p>
                                                    <div class="d-flex align-items-center gap-1 flex-wrap">
                                                        @foreach ($details as $detail)
                                                            @if ($detail->nama_model)
                                                                <span class="badge bg-secondary">
                                                                    {{ $detail->nama_model }}
                                                                </span>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </a>
                                            </div>
                                        @endforeach
                                    @else
                                        <p class="text-muted">Tidak ada data</p>
                                    @endif
                                </div>
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
