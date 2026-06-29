@extends('layouts.app')

@section('title')
    Analisa Beban
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Analisa Beban</h5>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label for="tahun">Tahun</label>
                        <select class="form-control" id="tahun" name="tahun">
                            @for ($i = date('Y'); $i >= date('Y') - 1; $i--)
                                <option value="{{ $i }}" {{ $i == date('Y') ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-primary d-block" id="btnCari">Cari</button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div id="chartBeban" style="height: 400px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('after-scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    let chartBeban = null;
    let chartData = [];

    function loadData() {
        const tahun = $('#tahun').val();

        $.ajax({
            url: '{{ route('analisa.beban.data') }}',
            method: 'GET',
            data: { tahun: tahun },
            success: function(response) {
                chartData = response;
                renderChart(response);
            },
            error: function(xhr) {
                console.error('Error loading data:', xhr);
                alert('Gagal memuat data. Silakan coba lagi.');
            }
        });
    }

    // Fungsi untuk menyederhanakan angka
    function formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(0) + 'K';
        } else {
            return num.toFixed(0);
        }
    }

    function renderChart(data) {
        if (!data || data.length === 0) {
            console.warn('No data to render');
            return;
        }

        const labels = data.map(item => item.nama_bulan);
        const operasional = data.map(item => item.operasional || 0);
        const penggajian = data.map(item => item.penggajian || 0);
        const tunjangan = data.map(item => item.tunjangan || 0);
        const pemakaianStok = data.map(item => item.pemakaian_stok || 0);

        const series = [
            {
                name: 'operasional',
                data: operasional
            },
            {
                name: 'penggajian',
                data: penggajian
            },
            {
                name: 'tunjangan',
                data: tunjangan
            },
            {
                name: 'pemakaian_stok',
                data: pemakaianStok
            }
        ];

        const colors = ['#4472C4', '#70AD47', '#7030A0', '#5B9BD5'];

        const options = {
            series: series,
            chart: {
                type: 'bar',
                height: 400,
                stacked: true,
                toolbar: {
                    show: false
                },
                events: {
                    dataPointSelection: function(event, chartContext, config) {
                        const dataPointIndex = config.dataPointIndex;
                        const seriesIndex = config.seriesIndex;
                        const bulan = chartData[dataPointIndex].bulan;
                        const tahun = $('#tahun').val();
                        const bulanFormatted = bulan < 10 ? '0' + bulan : bulan;
                        const urlBulan = tahun + '-' + bulanFormatted;
                        const seriesName = chartContext.w.globals.seriesNames[seriesIndex];
                        const kategori = seriesName.toLowerCase();

                        console.log('Kategori diklik:', kategori, 'Series Index:', seriesIndex);

                        let url = '';
                        switch(kategori) {
                            case 'operasional':
                                url = '{{ url('admin/operasional') }}?bulan=' + urlBulan;
                                break;
                            case 'penggajian':
                                url = '{{ url('admin/penggajian') }}?bulan=' + urlBulan;
                                break;
                            case 'tunjangan':
                                url = '{{ url('admin/tunjangan') }}?bulan=' + urlBulan;
                                break;
                            case 'pemakaian_stok':
                                url = '{{ url('admin/produk-stok') }}?bulan=' + urlBulan;
                                break;
                            default:
                                url = '{{ url('admin/operasional') }}?bulan=' + urlBulan;
                        }

                        console.log('URL redirect:', url);
                        window.location.href = url;
                    }
                }
            },
            colors: colors,
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    dataLabels: {
                        position: 'center'
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    if (val > 0) {
                        return formatNumber(val);
                    }
                    return '';
                },
                style: {
                    colors: ['#fff'],
                    fontSize: '12px',
                    fontWeight: 'bold'
                },
                offsetY: 0
            },
            xaxis: {
                categories: labels,
                labels: {
                    style: {
                        colors: '#666'
                    }
                }
            },
            yaxis: {
                labels: {
                    formatter: function(val) {
                        return val.toLocaleString('id-ID');
                    },
                    style: {
                        colors: '#666'
                    }
                },
                grid: {
                    color: '#e0e0e0'
                }
            },
            legend: {
                position: 'bottom',
                horizontalAlign: 'center',
                itemMargin: {
                    horizontal: 10,
                    vertical: 5
                },
                markers: {
                    width: 12,
                    height: 12,
                    radius: 0
                }
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function(val) {
                        return new Intl.NumberFormat('id-ID', {
                            style: 'currency',
                            currency: 'IDR',
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 0
                        }).format(val);
                    }
                }
            },
            fill: {
                opacity: 1
            },
            grid: {
                xaxis: {
                    lines: {
                        show: false
                    }
                }
            }
        };

        // Destroy existing chart if it exists
        if (chartBeban) {
            chartBeban.destroy();
        }

        chartBeban = new ApexCharts(document.querySelector("#chartBeban"), options);
        chartBeban.render();
    }

    $(document).ready(function() {
        loadData();

        $('#btnCari').on('click', function() {
            loadData();
        });

        $('#tahun').on('change', function() {
            loadData();
        });
    });
</script>
@endpush
