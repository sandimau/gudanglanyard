@extends('layouts.app')

@section('title')
    Proses Produksi
@endsection

@section('content')
    <header class="header mb-4">
        <div class="container-fluid">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb my-0 ms-2">
                    <li class="breadcrumb-item">
                        <b>Dashboard</b>
                    </li>
                </ol>
            </nav>
            @can('order_create')
                <a href="{{ route('order.create') }}" class="btn btn-primary rounded-pill text-white">Tambah Orders</a>
            @endcan
        </div>
    </header>
    <div class="bg-light rounded">
        @include('layouts.includes.messages')
        <div class="row">
            <div class="col-12">
                <ul class="nav nav-tabs" id="orderTab" role="tablist">
                    @foreach ($produksi as $item)
                        @if ($item->nama != 'finish' && $item->nama != 'batal')
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $loop->first ? 'active' : '' }} nav-nonaktif" id="{{ $item->nama }}-tab"
                                    data-bs-toggle="tab" data-bs-target="#{{ $item->nama }}" type="button" role="tab"
                                    aria-controls="{{ $item->nama }}" aria-selected="false">
                                    {{ $item->nama }}
                                    <span class="badge bg-success rounded-pill">{{ $item->orderDetail()->whereNotNull('order_id')->whereHas('order')->distinct()->count('order_id') }}</span>
                                </button>
                            </li>
                        @endif
                    @endforeach
                </ul>
                <div class="tab-content" id="orderTabContent">
                    @foreach ($produksi as $item)
                        @if ($item->nama != 'finish' && $item->nama != 'batal')
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $item->nama }}"
                                role="tabpanel" aria-labelledby="{{ $item->nama }}-tab">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        @if ($item->orderDetail)
                                            @php
                                                $hasil = [];
                                                $tampilan = '';
                                                $order_id = 0;

                                                foreach ($item->orderDetail()->with(['order.kontak.ar', 'produk', 'pemproses'])->orderBy('order_id')->get() as $detail) {
                                                    // Filter: skip jika order_id tidak ada atau null
                                                    if (!$detail->order_id) {
                                                        continue;
                                                    }

                                                    if ($order_id != $detail->order_id) {
                                                        if ($order_id != 0) {
                                                            $tampilan .= '<div class=pull-right></div></a>';
                                                        }

                                                        $warna = '';
                                                        $nominal = '';
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
                                                            $model_ar = $konsumen->ar ?? null;
                                                            $kode = $model_ar ? $model_ar->kode : '';
                                                            $test = $model_ar ? $model_ar->warna : '';
                                                            $tampilan .=
                                                                "<a class='popup d-flex'  href='" .
                                                                url('admin/order/' . $detail->order_id . '/detail') .
                                                                "' ><p style='font-weight:600' class='text-default'>";

                                                            $tampilan .=
                                                                " <span class='label label-rounded' style='background-color: " .
                                                                $test .
                                                                "'> " .
                                                                $kode .
                                                                '  </span>';

                                                            $tampilan .=
                                                                " <span class='label label-rounded mr-1' style='background-color: " .
                                                                $warna .
                                                                "'> " .
                                                                $nominal .
                                                                '  </span> ';

                                                            $tampilan .=
                                                                $konsumen->nama .
                                                                ' <span style="color:#222222">' .
                                                                $konsumen_detail .
                                                                '</span></p>';
                                                        }
                                                    }

                                                    $pemprosesBadge = '';
                                                    if (!empty($detail->pemproses)) {
                                                        $pemprosesBadge =
                                                            "<span class='label label-info label-rounded' style='background-color: #" .
                                                            ltrim($detail->pemproses->warna, '#') .
                                                            ";'>" .
                                                            $detail->pemproses->nama .
                                                            '</span> ';
                                                    }

                                                    $nama_produk = '';
                                                    $nama_produk .= $detail->produk->namaLengkap;

                                                    $jadwalx = '';
                                                    if ($detail->deathline) {
                                                        $time1 = new DateTime(date('Y-m-d'));
                                                        $time2 = new DateTime($detail->deathline);
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

                                                    $tampilan .=
                                                        "<span style='color:#636363; padding-right:5px;'> " .
                                                        $nama_produk .
                                                        ' ' .
                                                        $pemprosesBadge .
                                                        $jadwalx .
                                                        '</span> ';

                                                    $order_id = $detail->order_id;
                                                }

                                                if ($order_id != 0) {
                                                    $tampilan .= '<div class=pull-right></div></a>';
                                                }

                                                echo $tampilan;
                                            @endphp
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
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
    </script>
    <style>
        @include('admin.orders.partials.detail-order-modal-styles')
    </style>
@endpush
