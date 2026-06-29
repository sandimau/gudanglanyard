@extends('layouts.app')

@section('title')
    PO List
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">PO</h5>
                    </div>
                    <a href="{{ route('po.create') }}" class="btn btn-primary">Add PO</a>
                </div>
            </div>
            <div class="card-body">
                @include('layouts.includes.messages')

                <!-- Tab Navigation -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Status PO</h6>
                    <div class="d-flex gap-2">
                        <span class="badge bg-warning">Total Proses: {{ $poProses->count() }}</span>
                        <span class="badge bg-success">Total Selesai: {{ $poSelesai->count() }}</span>
                    </div>
                </div>
                <ul class="nav nav-tabs" id="poTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="proses-tab" data-bs-toggle="tab" data-bs-target="#proses" type="button" role="tab" aria-controls="proses" aria-selected="true">
                            <i class="fas fa-clock me-1"></i>Proses <span class="badge bg-warning ms-1">{{ $poProses->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="selesai-tab" data-bs-toggle="tab" data-bs-target="#selesai" type="button" role="tab" aria-controls="selesai" aria-selected="false">
                            <i class="fas fa-check-circle me-1"></i>Selesai <span class="badge bg-success ms-1">{{ $poSelesai->count() }}</span>
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="poTabsContent">
                    <!-- Tab Proses -->
                    <div class="tab-pane fade show active" id="proses" role="tabpanel" aria-labelledby="proses-tab">
                        <div class="table-responsive mt-3">
                            <table class="table table-striped" id="tableProses">
                                <thead>
                                    <tr>
                                        <th scope="col">tanggal</th>
                                        <th scope="col">kontak</th>
                                        <th scope="col">produk</th>
                                        <th scope="col">ket</th>
                                        <th scope="col">perkiraan datang</th>
                                        <th scope="col">user</th>
                                        <th scope="col">status</th>
                                        <th scope="col">aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($poProses as $po)
                                        <tr>
                                            <td>{{ $po->created_at->format('d-m-Y') }}</td>
                                            <td>{{ $po->kontak->nama }}</td>
                                            <td><a href="{{ route('po.show', $po->id) }}">{{ $po->produk }}</a></td>
                                            <td>{{ $po->ket }}</td>
                                            <td>{{ $po->tglKedatangan }}</td>
                                            <td>{{ $po->user->name }}</td>
                                            <td>
                                                <span class="badge bg-warning">Proses</span>
                                            </td>
                                            <td>
                                                <form action="{{ route('po.selesai', $po->id) }}" method="POST" style="display: inline;">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Yakin ingin menandai PO ini sebagai selesai?')">
                                                        <i class="fas fa-check"></i> Selesai
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">
                                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                                <br>Tidak ada PO dalam status proses
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab Selesai -->
                    <div class="tab-pane fade" id="selesai" role="tabpanel" aria-labelledby="selesai-tab">
                        <div class="table-responsive mt-3">
                            <table class="table table-striped" id="tableSelesai">
                                <thead>
                                    <tr>
                                        <th scope="col">tanggal</th>
                                        <th scope="col">kontak</th>
                                        <th scope="col">produk</th>
                                        <th scope="col">ket</th>
                                        <th scope="col">perkiraan datang</th>
                                        <th scope="col">user</th>
                                        <th scope="col">status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($poSelesai as $po)
                                        <tr>
                                            <td>{{ $po->created_at->format('d-m-Y') }}</td>
                                            <td>{{ $po->kontak->nama }}</td>
                                            <td><a href="{{ route('po.show', $po->id) }}">{{ $po->produk }}</a></td>
                                            <td>{{ $po->ket }}</td>
                                            <td>{{ $po->tglKedatangan }}</td>
                                            <td>{{ $po->user->name }}</td>
                                            <td>
                                                <span class="badge bg-success">Selesai</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">
                                                <i class="fas fa-check-circle fa-2x mb-2"></i>
                                                <br>Tidak ada PO yang selesai
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
    </div>
@endsection

@push('styles')
    <style>
        .nav-tabs .nav-link {
            color: #6c757d;
            border: none;
            border-bottom: 2px solid transparent;
        }
        .nav-tabs .nav-link.active {
            color: #0d6efd;
            border-bottom: 2px solid #0d6efd;
            background-color: transparent;
        }
        .nav-tabs .nav-link:hover {
            border-color: transparent;
            color: #0d6efd;
        }
        .badge {
            font-size: 0.75em;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#tableProses').DataTable({
                "pageLength": 10,
                "order": [[0, "desc"]]
            });
            $('#tableSelesai').DataTable({
                "pageLength": 10,
                "order": [[0, "desc"]]
            });
        });
    </script>
@endpush
