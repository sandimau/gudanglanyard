@extends('layouts.app')

@section('title')
    SLip Gaji
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-body printableArea m-b-0">
                <div class="row">
                    <div class="col-7">
                        <h4 class="m-b-0">kepada: <br> {{ $penggajian->member->nama_lengkap }} </h4>
                        <p class="text-muted m-t-0">{{ $penggajian->member->alamat }}</p>
                    </div>
                    <div class="col-5">
                        <b>bulan : {{ $penggajian->bulan }}</b>
                        <br> <i class="fa fa-calendar"></i> {{ date_format($penggajian->created_at, 'd-m-Y') }}
                    </div>
                    <div class="col-md-12">
                        <div class="table-responsive" style="clear: both;">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Jenis</th>
                                        <th style="width: 500px" class="text-right">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Gaji Pokok</td>
                                        <td class="text-right">{{ number_format($penggajian->pokok, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td>Tunjangan Lama Kerja</td>
                                        <td class="text-right">{{ number_format($penggajian->lama_kerja, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Tunjangan Bagian</td>
                                        <td class="text-right">{{ number_format($penggajian->bagian, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td>Tunjangan Performance</td>
                                        <td class="text-right">{{ number_format($penggajian->performance, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Tunjangan transportasi</td>
                                        <td class="text-right">{{ number_format($penggajian->transportasi, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Tunjangan komunikasi</td>
                                        <td class="text-right">{{ number_format($penggajian->komunikasi, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Tunjangan Kehadiran</td>
                                        <td class="text-right">{{ number_format($penggajian->kehadiran, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Tunjangan lainnya</td>
                                        <td class="text-right">{{ $penggajian->lain_lain }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Tunjangan Jumlah lainnya</td>
                                        <td class="text-right">{{ number_format($penggajian->jumlah_lain, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Tunjangan lembur {{ number_format($penggajian->jam_lembur, 0, ',', '.') }}</td>
                                        <td class="text-right">{{ number_format($penggajian->lembur, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td>Kasbon</td>
                                        <td class="text-right inline-block">
                                            {{ number_format($penggajian->kasbon, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Bonus</td>
                                        <td class="text-right">{{ number_format($penggajian->bonus, 0, ',', '.') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <hr>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-2" style="font-weight: 400">
                            hormat kami
                        </div>
                        <div class="mt-5" style="font-weight: 400">
                            (_____________)
                        </div>
                    </div>
                    <div class="col-6">
                        <h5>Total: {{ number_format($penggajian->total, 0, ',', '.') }}</h5>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <button id="print" class="btn btn-primary m-t-15" type="button"> <span><i class="fa fa-print"></i>
                        Print</span> </button>
            </div>
        </div>
    </div>
@endsection
@push('after-scripts')
    <script src="{{ asset('js/jquery.PrintArea.js') }}"></script>
    <script>
        $(document).ready(function() {
            $("#print").click(function() {
                var mode = 'iframe'; //popup
                var close = mode == "popup";
                var options = {
                    mode: mode,
                    popClose: close
                };
                $("div.printableArea").printArea(options);
            });
        });
    </script>
@endpush
