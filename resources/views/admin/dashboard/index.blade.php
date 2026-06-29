@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-2">
        @include('layouts.includes.messages')
    </div>

    {{-- Chart Section --}}
    <div class="row mb-4">
        {{-- Omzet Offline Pekanan --}}
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="card-title fw-bold mb-0">
                                <span class="d-inline-block me-2"
                                    style="width: 4px; height: 20px; background: linear-gradient(180deg, #00b894, #00cec9); border-radius: 2px;"></span>
                                Omzet Offline Pekanan
                            </h5>
                        </div>
                        <a href="{{ route('order.index') }}" class="btn btn-sm px-3"
                            style="background: linear-gradient(135deg, #00b894, #00cec9); color: white; border-radius: 20px;">
                            <i class='bx bx-show'></i>
                        </a>
                    </div>
                    <div id="chart-offline"></div>
                </div>
            </div>
        </div>

        {{-- Omzet Online Pekanan --}}
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="card-title fw-bold mb-0">
                                <span class="d-inline-block me-2"
                                    style="width: 4px; height: 20px; background: linear-gradient(180deg, #6c5ce7, #a29bfe); border-radius: 2px;"></span>
                                Omzet Marketplace Pekanan
                            </h5>
                        </div>
                        <a href="{{ route('projectmp.index') }}" class="btn btn-sm px-3"
                            style="background: linear-gradient(135deg, #6c5ce7, #a29bfe); color: white; border-radius: 20px;">
                            <i class='bx bx-show'></i>
                        </a>
                    </div>
                    <div id="chart-online"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tables Section --}}
    <div class="row">
        {{-- Order Terbesar Offline Pekanan --}}
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="card-title fw-bold mb-0">
                                <span class="d-inline-block me-2"
                                    style="width: 4px; height: 18px; background: linear-gradient(180deg, #fd79a8, #e84393); border-radius: 2px;"></span>
                                Order Terbesar Offline
                            </h6>
                            <small class="text-muted">7 hari terakhir</small>
                        </div>
                        <a href="{{ route('order.index') }}" class="btn btn-sm px-3"
                            style="background: linear-gradient(135deg, #fd79a8, #e84393); color: white; border-radius: 20px;">
                            <i class='bx bx-list-ul'></i>
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover" style="font-size: 13px;">
                            <thead>
                                <tr>
                                    <th class="border-0">Tanggal</th>
                                    <th class="border-0" style="width: 45%;">Konsumen</th>
                                    <th class="border-0 text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($orderTerbesarOffline as $order)
                                    <tr>
                                        <td class="align-middle">
                                            <span
                                                class="badge bg-light text-dark">{{ date('d/m', strtotime($order->created_at)) }}</span>
                                        </td>
                                        <td class="align-middle">
                                            <a href="{{ route('order.detail', $order->id) }}"
                                                class="text-decoration-none fw-medium">
                                                {{ \Illuminate\Support\Str::limit($order->kontak->nama ?? '-', 18, '...') }}
                                            </a>
                                        </td>
                                        <td class="align-middle text-end">
                                            <span class="fw-bold"
                                                style="color: #00b894;">{{ number_format($order->total, 0, ',', '.') }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">
                                            <i class='bx bx-package bx-lg mb-2 d-block'></i>
                                            Tidak ada data
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Penjualan Terbaik Pekanan --}}
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="card-title fw-bold mb-0">
                                <span class="d-inline-block me-2"
                                    style="width: 4px; height: 18px; background: linear-gradient(180deg, #fdcb6e, #f39c12); border-radius: 2px;"></span>
                                Penjualan Terbaik
                            </h6>
                            <small class="text-muted">7 hari terakhir</small>
                        </div>
                        <a href="{{ route('order.omzet') }}" class="btn btn-sm px-3"
                            style="background: linear-gradient(135deg, #fdcb6e, #f39c12); color: white; border-radius: 20px;">
                            <i class='bx bx-trending-up'></i>
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover" style="font-size: 13px;">
                            <thead>
                                <tr>
                                    <th class="border-0">Produk</th>
                                    <th class="border-0 text-end">Omzet</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($produkTerlaris as $produk)
                                    <tr>
                                        <td class="align-middle">
                                            <span
                                                class="fw-medium">{{ \Illuminate\Support\Str::limit($produk['nama_produk'] ?? '-', 24, '...') }}</span>
                                        </td>
                                        <td class="align-middle text-end">
                                            <span class="fw-bold"
                                                style="color: #f39c12;">{{ number_format($produk['omzet'] ?? 0, 0, ',', '.') }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted py-4">
                                            <i class='bx bx-cart bx-lg mb-2 d-block'></i>
                                            Tidak ada data
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Order Terbesar Hari Ini --}}
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="card-title fw-bold mb-0">
                                <span class="d-inline-block me-2"
                                    style="width: 4px; height: 18px; background: linear-gradient(180deg, #74b9ff, #0984e3); border-radius: 2px;"></span>
                                Order Terbesar Hari Ini
                            </h6>
                            <small class="text-muted">{{ now()->translatedFormat('d F Y') }}</small>
                        </div>
                        <span class="badge px-3 py-2"
                            style="background: linear-gradient(135deg, #74b9ff, #0984e3); color: white; border-radius: 20px;">
                            <i class='bx bx-time-five'></i> Live
                        </span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover" style="font-size: 13px;">
                            <thead>
                                <tr>
                                    <th class="border-0">Produk</th>
                                    <th class="border-0 text-end">Omzet</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($orderTerbesarHariIni as $order)
                                    <tr>
                                        <td class="align-middle">
                                            <span
                                                class="fw-medium">{{ \Illuminate\Support\Str::limit($order['nama_produk'] ?? '-', 24, '...') }}</span>
                                        </td>
                                        <td class="align-middle text-end">
                                            <span class="fw-bold"
                                                style="color: #0984e3;">{{ number_format($order['omzet'] ?? 0, 0, ',', '.') }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted py-4">
                                            <i class='bx bx-calendar-x bx-lg mb-2 d-block'></i>
                                            Belum ada order hari ini
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('after-scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script type="text/javascript">
        @php
            // Prepare data Omzet Offline
            $sortedOffline = collect($omzetOffline ?? [])->sortKeys();
            $today = now()->format('Y-m-d');
            $yesterday = now()->subDay()->format('Y-m-d');

            $offlineDates = $sortedOffline->keys()->toArray();
            $formattedDatesOffline = [];
            foreach ($offlineDates as $date) {
                if ($date === $today) {
                    $formattedDatesOffline[] = 'hari ini';
                } elseif ($date === $yesterday) {
                    $formattedDatesOffline[] = 'kemarin';
                } else {
                    $formattedDatesOffline[] = date('j M', strtotime($date));
                }
            }

            // Prepare data Omzet Online
            $sortedOnline = collect($omzetOnline ?? [])->sortKeys();
            // Jangan ambil dari item pertama (bisa kosong). Kumpulkan dari semua hari.
            $mpList = $sortedOnline
                ->flatMap(function ($row) {
                    $vars = is_object($row) ? get_object_vars($row) : (array) $row;
                    return array_keys($vars);
                })
                ->filter(fn ($key) => $key !== 'date')
                ->unique()
                ->values()
                ->all();

            $onlineDates = $sortedOnline->keys()->toArray();
            $formattedDatesOnline = [];
            foreach ($onlineDates as $date) {
                if ($date === $today) {
                    $formattedDatesOnline[] = 'hari ini';
                } elseif ($date === $yesterday) {
                    $formattedDatesOnline[] = 'kemarin';
                } else {
                    $formattedDatesOnline[] = date('j M', strtotime($date));
                }
            }
        @endphp

        // Data untuk chart offline
        var datesOffline = [];
        var valuesOffline = [];

        @foreach ($sortedOffline as $date => $data)
            datesOffline.push('{{ $formattedDatesOffline[array_search($date, $offlineDates)] }}');
            valuesOffline.push({{ $data->offline ?? 0 }});
        @endforeach

        // Data untuk chart online
        var datesOnline = [];
        @foreach ($mpList as $mp)
            var values_mp_{{ $mp }} = [];
        @endforeach

        @foreach ($sortedOnline as $date => $data)
            datesOnline.push('{{ $formattedDatesOnline[array_search($date, $onlineDates)] }}');
            @foreach ($mpList as $mp)
                values_mp_{{ $mp }}.push({{ $data->$mp ?? 0 }});
            @endforeach
        @endforeach

        function getThemeMode() {
            return (document.documentElement.getAttribute('data-theme') || 'dark') === 'light' ? 'light' : 'dark';
        }

        function getChartThemeOptions() {
            var isDark = getThemeMode() === 'dark';
            return {
                theme: { mode: isDark ? 'dark' : 'light' },
                grid: { borderColor: isDark ? '#334155' : '#f1f1f1' },
                xaxis: {
                    labels: {
                        style: {
                            fontSize: '11px',
                            colors: isDark ? '#94a3b8' : '#64748b'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            fontSize: '11px',
                            colors: isDark ? '#94a3b8' : '#64748b'
                        }
                    }
                },
                legend: {
                    labels: { colors: isDark ? '#94a3b8' : '#64748b' }
                }
            };
        }

        function applyChartTheme(options) {
            var themeOpts = getChartThemeOptions();
            options.theme = themeOpts.theme;
            options.grid = themeOpts.grid;
            options.xaxis.labels.style = Object.assign(
                options.xaxis.labels.style || {},
                themeOpts.xaxis.labels.style
            );
            options.yaxis.labels.style = Object.assign(
                options.yaxis.labels.style || {},
                themeOpts.yaxis.labels.style
            );
            if (options.legend) {
                options.legend.labels = themeOpts.legend.labels;
            }
            return options;
        }

        // Chart Offline
        var optionsOffline = {
            chart: {
                type: 'area',
                height: 320,
                toolbar: {
                    show: false
                },
                fontFamily: 'inherit'
            },
            colors: ['#00b894'],
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    if (val >= 1000000) {
                        return Math.round(val / 1000000) + 'jt'
                    } else if (val >= 1000) {
                        return Math.round(val / 1000) + 'rb'
                    }
                    return val
                },
                style: {
                    fontSize: '11px',
                    fontWeight: 600
                }
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.5,
                    opacityTo: 0.1,
                    stops: [0, 90, 100]
                }
            },
            series: [{
                name: 'Omzet Offline',
                data: valuesOffline
            }],
            xaxis: {
                categories: datesOffline,
                labels: {
                    style: {
                        fontSize: '11px'
                    }
                }
            },
            yaxis: {
                labels: {
                    formatter: function(value) {
                        if (value >= 1000000) {
                            return Math.round(value / 1000000) + 'jt'
                        } else if (value >= 1000) {
                            return Math.round(value / 1000) + 'rb'
                        }
                        return value
                    },
                    style: {
                        fontSize: '11px'
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(value) {
                        return 'Rp ' + value.toLocaleString('id-ID')
                    }
                }
            }
        };
        applyChartTheme(optionsOffline);

        // Chart Online (Marketplace)
        var optionsOnline = {
            chart: {
                type: 'bar',
                height: 320,
                stacked: true,
                toolbar: {
                    show: false
                },
                fontFamily: 'inherit'
            },
            colors: ['#6c5ce7', '#a29bfe', '#fd79a8', '#fdcb6e', '#74b9ff', '#00cec9', '#fab1a0'],
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    if (val >= 1000000) {
                        return Math.round(val / 1000000) + 'jt'
                    } else if (val >= 1000) {
                        return Math.round(val / 1000) + 'rb'
                    } else if (val > 0) {
                        return val
                    }
                    return ''
                },
                style: {
                    fontSize: '10px',
                    fontWeight: 600
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    columnWidth: '60%'
                }
            },
            series: [
                @foreach ($mpList as $mp)
                    {
                        name: '{{ ucfirst(str_replace('_', ' ', $mp)) }}',
                        data: values_mp_{{ $mp }}
                    },
                @endforeach
            ],
            xaxis: {
                categories: datesOnline,
                labels: {
                    style: {
                        fontSize: '11px'
                    }
                }
            },
            yaxis: {
                labels: {
                    formatter: function(value) {
                        if (value >= 1000000) {
                            return Math.round(value / 1000000) + 'jt'
                        } else if (value >= 1000) {
                            return Math.round(value / 1000) + 'rb'
                        }
                        return value
                    },
                    style: {
                        fontSize: '11px'
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(value) {
                        return 'Rp ' + value.toLocaleString('id-ID')
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left',
                fontSize: '12px'
            }
        };
        applyChartTheme(optionsOnline);

        var chartOffline = new ApexCharts(document.querySelector("#chart-offline"), optionsOffline);
        var chartOnline = new ApexCharts(document.querySelector("#chart-online"), optionsOnline);

        chartOffline.render();
        chartOnline.render();

        function updateDashboardChartsTheme() {
            var themeOpts = getChartThemeOptions();
            chartOffline.updateOptions({
                theme: themeOpts.theme,
                grid: themeOpts.grid,
                xaxis: { labels: { style: themeOpts.xaxis.labels.style } },
                yaxis: { labels: { style: themeOpts.yaxis.labels.style } }
            });
            chartOnline.updateOptions({
                theme: themeOpts.theme,
                grid: themeOpts.grid,
                xaxis: { labels: { style: themeOpts.xaxis.labels.style } },
                yaxis: { labels: { style: themeOpts.yaxis.labels.style } },
                legend: { labels: themeOpts.legend.labels }
            });
        }

        document.getElementById('theme-toggle')?.addEventListener('click', function() {
            setTimeout(updateDashboardChartsTheme, 50);
        });
    </script>
@endpush

@push('after-styles')
    <style>
        .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: var(--app-shadow-lg) !important;
        }

        .table tbody tr {
            transition: background-color 0.2s ease;
        }

        [data-theme="light"] .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        :root:not([data-theme="light"]) .table tbody tr:hover,
        [data-theme="dark"] .table tbody tr:hover {
            background-color: rgba(99, 102, 241, .08);
        }

        .bg-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
@endpush
