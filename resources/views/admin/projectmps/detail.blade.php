@extends('layouts.app')

@section('title')
    Detail Order
@endsection

@section('content')
    <div class="bg-light rounded">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active" aria-current="page"> <a
                        href="{{ route('projectmp.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Order Detail</li>
            </ol>
        </nav>
        @include('layouts.includes.messages')
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-details-center">
                            <div>
                                <h5 class="card-title">{{ $projectMp->nota }} | {{ $marketplace->nama }} -
                                    {{ $projectMp->konsumen }}</h5>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-lg-2 col-sm-4">
                                        <h6 class="mb-0 text-secondary">Ongkir</h6>
                                        <p>{{ number_format($projectMp->ongkir, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="col-lg-2 col-sm-4">
                                        <h6 class="mb-0 text-secondary">Diskon</h6>
                                        <p>{{ number_format($projectMp->diskon, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="col-lg-2 col-sm-4">
                                        <h6 class="mb-0 text-secondary">Total</h6>
                                        <p>{{ number_format($projectMp->total, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="col-lg-2 col-sm-4">
                                        <h6 class="mb-0 text-secondary">Pembayaran</h6>
                                        <p>{{ number_format($projectMp->bayar, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="col-lg-2 col-sm-4">
                                        <h6 class="mb-0 text-secondary">Kekurangan</h6>
                                        <p>{{ number_format($projectMp->kekurangan, 0, ',', '.') }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="{{ route('projectMpDetail.add', $projectMp->id) }}"
                                    class="btn btn-success rounded-pill text-white">
                                    <i class='bx bx-plus-circle'></i> tambah
                                </a>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <h6 class="mb-0 text-secondary">Keterangan</h6>
                                    <p>{{ $projectMp->keterangan }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped" id="myTable">
                                <thead>
                                    <tr>
                                        <th>produk</th>
                                        <th>tema</th>
                                        <th>jml</th>
                                        <th>harga</th>
                                        <th>subtotal</th>
                                        @if ($projectMp->buffer)
                                            @if ($projectMp->buffer->custom == 1)
                                                <th>status</th>
                                                <th>pemproses</th>
                                            @endif
                                        @endif
                                        <th>gambar</th>
                                        <th>Deadline</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($projectMpdetails as $detail)
                                        <tr>
                                            <td>{{ $detail->produk->namaLengkap ?? '-' }}</td>
                                            <td>{{ $detail->tema }}</td>
                                            <td>{{ $detail->jumlah }}</td>
                                            <td>{{ number_format($detail->harga) }}</td>
                                            <td>{{ number_format($detail->harga * $detail->jumlah) }}</td>
                                            @if ($projectMp->buffer)
                                                @if ($detail->projectMP->buffer->custom != null)
                                                    <td>
                                                        @if ($canEditLimited && ! $isMarketingOnly && ! $isProduksiLevel)
                                                            <form action="{{ route('projectMpDetail.status', $detail->id) }}"
                                                                method="post" class="projectmp-detail-ajax-form">
                                                                {{ csrf_field() }}
                                                                {{ method_field('patch') }}
                                                                <select class="form-select" aria-label="Default select example"
                                                                    name="produksi_id"
                                                                    onchange="this.form.requestSubmit()">
                                                                    @foreach ($produksi as $entry)
                                                                        <option value="{{ $entry->id }}"
                                                                            {{ $detail->produksi_id == $entry->id ? 'selected' : '' }}>
                                                                            {{ $entry->nama }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </form>
                                                        @else
                                                            {{ $detail->produksi->nama ?? '-' }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <form action="{{ route('projectMpDetail.pemproses', $detail->id) }}"
                                                            method="post" class="projectmp-detail-ajax-form">
                                                            {{ csrf_field() }}
                                                            {{ method_field('patch') }}
                                                            <select class="form-select" aria-label="Pilih pemproses"
                                                                name="pemproses_id" onchange="this.form.requestSubmit()">
                                                                <option value="">- pilih -</option>
                                                                @foreach (($pemproses ?? collect()) as $entry)
                                                                    <option value="{{ $entry->id }}"
                                                                        {{ $detail->pemproses_id == $entry->id ? 'selected' : '' }}>
                                                                        {{ $entry->nama }}</option>
                                                                @endforeach
                                                            </select>
                                                        </form>
                                                    </td>
                                                @endif
                                            @endif
                                            <td>
                                                @if ($detail->gambar)
                                                    <a href="#"
                                                        class="projectmp-detail-image-thumb"
                                                        data-image-src="{{ asset('uploads/projectMp/' . $detail->gambar) }}"
                                                        data-edit-url="{{ route('projectMpDetail.editGambar', $detail->id) }}">
                                                        <img style="width: 100px"
                                                            src="{{ asset('uploads/projectMp/' . $detail->gambar) }}"
                                                            alt="">
                                                    </a>
                                                @else
                                                    <a href="{{ route('projectMpDetail.gambar', $detail->id) }}"
                                                        class="btn btn-success text-white"><i
                                                            class='bx bx-image-alt'></i></a>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('projectMpDetail.edit', $detail->id) }}">{{ $detail->deadline ? \Carbon\Carbon::parse($detail->deadline)->format('d-m-Y') : 'Belum ada' }}</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="card mt-4">
                    <div class="card-header">
                        notes
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('projectMp.chatStore', $projectMp->id) }}"
                            enctype="multipart/form-data" class="projectmp-detail-ajax-form"
                            data-reload-detail="{{ route('projectmp.detail', $projectMp->id) }}">
                            @csrf
                            <div class="input-group mb-3">
                                <input type="text" class="form-control chat" placeholder="tulis pesan" name="isi">
                                <button class="input-group-text btn btn-primary rounded-pill" type="submit"><i
                                        class='bx bx-send'></i></button>
                            </div>
                        </form>
                        <div class="iframe">
                            <small>
                                <ul class="chat-list p-0 m-0">
                                    @foreach ($chats as $chat)
                                        <li class="d-flex justify-content-between align-items-end pt-2">
                                            <div class="chat-content">
                                                @if ($chat->author_name)
                                                    <div class="text-primary"><b>{{ $chat->author_name }}</b></div>
                                                @endif
                                                <div class="box">{{ $chat->isi }}</div>
                                            </div>
                                            <div class="ps-2">{{ date('d/m/Y', strtotime($chat->created_at)) }}</div>
                                        </li>
                                    @endforeach
                                </ul>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('after-scripts')
    <style>
        .chat {
            border: none;
            border-bottom: solid #7c7c7c 1px
        }

        .chat:focus {
            box-shadow: none
        }

        .iframe {
            padding: 0px 10px;
        }

        .iframe ul {
            list-style: none;
        }

        .iframe .chat-content .box {
            padding: 10px 20px 10px 10px;
            background-color: #dddddd;
            border-radius: 5px;
        }
    </style>
@endpush
