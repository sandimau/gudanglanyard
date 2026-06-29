@extends('layouts.app')

@section('title')
    Analisa Operasional
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Analisa Operasional</h5>
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
                        <div id="chartOperasional" style="height: 400px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('after-scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    let chartOperasional = null;
    let chartData = [];

    function loadData() {
        const tahun = $('#tahun').val();

        $.ajax({
            url: '{{ route('analisa.operasional.data') }}',
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

        // Ambil semua kategori yang ada dari data
        const allKategori = new Set();
        data.forEach(item => {
            Object.keys(item).forEach(key => {
                if (key !== 'bulan' && key !== 'nama_bulan') {
                    allKategori.add(key);
                }
            });
        });

        const labels = data.map(item => item.nama_bulan);
        const kategoriArray = Array.from(allKategori);

        // Warna untuk setiap kategori
        const colors = [
            '#4472C4', '#70AD47', '#7030A0', '#5B9BD5', '#FFC000',
            '#C55A11', '#E7E6E6', '#A5A5A5', '#FF0000', '#00B0F0',
            '#92D050', '#0070C0', '#7030A0', '#C00000', '#00B050'
        ];

        // Siapkan series data untuk ApexCharts
        const series = kategoriArray.map((kategori, index) => {
            return {
                name: kategori.replace(/_/g, ' '),
                data: data.map(item => item[kategori] || 0)
            };
        });

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
                        const kategori = seriesName.replace(/\s/g, '_').toLowerCase();

                        const url = '{{ url('admin/operasional') }}?bulan=' + urlBulan + '&kategori=' + kategori;
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
        if (chartOperasional) {
            chartOperasional.destroy();
        }

        chartOperasional = new ApexCharts(document.querySelector("#chartOperasional"), options);
        chartOperasional.render();
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
