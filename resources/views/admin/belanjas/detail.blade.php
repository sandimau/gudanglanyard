@extends('layouts.app')

@section('title')
    Belanja Detail
@endsection

@section('content')
    <div class="bg-light rounded">
        @include('layouts.includes.messages')
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Belanjas</h5>
                    </div>
                    <a class="btn btn-primary" href="{{ route('belanja.index') }}">
                        <i class='bx bx-arrow-back'></i> back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-4">
                      <div class="border-start border-start-4 border-start-info px-3 mb-3"><small class="text-medium-emphasis">supplier</small>
                        <div class="fs-5 fw-semibold">{{ $belanja->kontak->nama }}</div>
                      </div>
                    </div>
                    <!-- /.col-->
                    <div class="col-4">
                      <div class="border-start border-start-4 border-start-info px-3 mb-3"><small class="text-medium-emphasis">diskon</small>
                        <div class="fs-5 fw-semibold">{{ $belanja->diskon }}</div>
                      </div>
                    </div>
                    <!-- /.col-->
                    <div class="col-4">
                        <div class="border-start border-start-4 border-start-info px-3 mb-3"><small class="text-medium-emphasis">total belanja</small>
                          <div class="fs-5 fw-semibold">{{ number_format($belanja->total, 0, ',', '.') }}</div>
                        </div>
                      </div>
                      <!-- /.col-->
                  </div>
                <div class="table-responsive">
                    <table class=" table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>produk</th>
                                <th>keterangan</th>
                                <th>satuan</th>
                                <th>jumlah</th>
                                <th>harga</th>
                                <th>total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($belanjaDetail as $belanja)
                                <tr data-entry-id="{{ $belanja->id }}">
                                    <td>{{ $belanja->produk->namaLengkap}}</td>
                                    <td>{{ $belanja->keterangan }}</td>
                                    <td>{{ $belanja->produk->produkModel->satuan }}</td>
                                    <td>{{ number_format($belanja->jumlah, 0, ',', '.') }}</td>
                                    <td>{{ number_format($belanja->harga, 0, ',', '.') }}</td>
                                    <td>{{ number_format($belanja->harga * $belanja->jumlah, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
