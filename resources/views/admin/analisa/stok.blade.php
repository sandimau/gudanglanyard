@extends('layouts.app')

@section('title')
    Analisa Stok
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Analisa Stok</h5>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label for="kategori">Kategori</label>
                        <select class="form-control" id="kategori" name="kategori">
                            <option value="all">Semua Kategori</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-primary d-block" id="btnCari">Cari</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="tableStok">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kategori</th>
                                <th>Produk</th>
                                <th>Varian</th>
                                <th>Penjualan Harian</th>
                                <th>Stok Total</th>
                                <th>Stok Minimal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyStok">
                            <tr>
                                <td colspan="8" class="text-center">Memuat data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('after-scripts')
<script>
    function loadData() {
        const kategori = $('#kategori').val();
        const tbody = $('#tbodyStok');
        tbody.html('<tr><td colspan="8" class="text-center">Memuat data...</td></tr>');

        $.ajax({
            url: '{{ route('analisa.stok.data') }}',
            method: 'GET',
            data: { kategori: kategori },
            success: function(response) {
                if (response.error) {
                    tbody.html('<tr><td colspan="8" class="text-center text-danger">Error: ' + response.message + '</td></tr>');
                    console.error('Error:', response.message);
                } else {
                    renderTable(response);
                }
            },
            error: function(xhr) {
                console.error('Error loading data:', xhr);
                let errorMsg = 'Gagal memuat data';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg += ': ' + xhr.responseJSON.message;
                }
                tbody.html('<tr><td colspan="8" class="text-center text-danger">' + errorMsg + '</td></tr>');
                alert(errorMsg);
            }
        });
    }

    function renderTable(data) {
        const tbody = $('#tbodyStok');
        tbody.empty();

        if (data.length === 0) {
            tbody.append('<tr><td colspan="8" class="text-center">Tidak ada data</td></tr>');
            return;
        }

        data.forEach((item, index) => {
            let statusClass = 'success';
            let statusText = 'Cukup';
            if (item.kurang) {
                statusClass = 'danger';
                statusText = 'Kurang';
            } else if (item.lebih) {
                statusClass = 'info';
                statusText = 'Berlebih';
            }

            const row = `
                <tr class="${item.kurang ? 'table-danger' : ''}">
                    <td>${index + 1}</td>
                    <td>${item.kategori || '-'}</td>
                    <td>${item.produk || '-'}</td>
                    <td>${item.varian || '-'}</td>
                    <td>${formatNumber(item.penjualan_harian)}</td>
                    <td>${item.stok_html || item.stok_total}</td>
                    <td>${formatNumber(item.stok_minimal)}</td>
                    <td><span>${statusText}</span></td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    function formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(0) + 'K';
        } else {
            return num.toFixed(2);
        }
    }

    function loadKategori() {
        $.ajax({
            url: '{{ route('analisa.stok.kategori') }}',
            method: 'GET',
            success: function(response) {
                if (response.error) {
                    console.error('Error loading kategori:', response.message);
                } else {
                    const select = $('#kategori');
                    response.forEach(function(kategori) {
                        select.append(`<option value="${kategori.id}">${kategori.nama}</option>`);
                    });
                }
            },
            error: function(xhr) {
                console.error('Error loading kategori:', xhr);
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    console.error('Message:', xhr.responseJSON.message);
                }
            }
        });
    }

    $(document).ready(function() {
        loadKategori();
        loadData();

        $('#btnCari').on('click', function() {
            loadData();
        });

        $('#kategori').on('change', function() {
            loadData();
        });
    });
</script>
@endpush
