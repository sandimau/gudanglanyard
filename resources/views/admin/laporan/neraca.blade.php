@extends('layouts.app')

@section('title')
    Neraca
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Neraca</h5>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @include('layouts.includes.messages')
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col">Akun</th>
                                <th scope="col">Debit</th>
                                <th scope="col">Kredit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Kas</td>
                                <td>{{ number_format($kas, 0, ',', '.') }}</td>
                                <td>0</td>
                            </tr>
                            <tr>
                                <td>Stok</td>
                                <td>{{ number_format($stok, 0, ',', '.') }}</td>
                                <td>0</td>
                            </tr>
                            <tr>
                                <td>Order</td>
                                <td>{{ number_format($total_order, 0, ',', '.') }}</td>
                                <td>0</td>
                            </tr>
                            <tr>
                                <td>Piutang</td>
                                <td>{{ number_format($total_piutang, 0, ',', '.') }}</td>
                                <td>0</td>
                            </tr>
                            <tr>
                                <td>Hutang</td>
                                <td>0</td>
                                <td>{{ number_format($total_hutang, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Modal</td>
                                <td>0</td>
                                <td>{{ number_format(abs($modal), 0, ',', '.') }}</td>
                            </tr>
                            <tr class="table-success">
                                <td>Laba</td>
                                <td>0</td>
                                <td>{{ number_format($kas + $stok + $total_order + $total_piutang - $total_hutang - abs($modal), 0, ',', '.') }}</td>
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
        let table = new DataTable('#myTable');
    </script>
@endpush
