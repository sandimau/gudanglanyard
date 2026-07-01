@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Hutang Piutang</h5>
                            </div>
                            @can('keuangan')
                                <div>
                                    <a href="{{ route('hutang.create', ['jenis' => 'hutang']) }}" class="btn btn-primary">Hutang Baru</a>
                                    <a href="{{ route('hutang.create', ['jenis' => 'piutang']) }}" class="btn btn-primary">Piutang Baru</a>
                                </div>
                            @endcan
                        </div>
                    </div>
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        <ul class="nav nav-tabs mb-3" id="hutangTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ $jenis === 'hutang' ? 'active' : '' }}"
                                    href="{{ route('hutang.index', array_merge(request()->except(['page', 'jenis', 'status']), ['jenis' => 'hutang'])) }}">
                                    Hutang
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ $jenis === 'piutang' ? 'active' : '' }}"
                                    href="{{ route('hutang.index', array_merge(request()->except(['page', 'jenis', 'status']), ['jenis' => 'piutang'])) }}">
                                    Piutang
                                </a>
                            </li>
                        </ul>

                        @if ($jenis === 'hutang')
                            <form method="GET" action="{{ route('hutang.index') }}" class="row g-2 align-items-end mb-3">
                                <input type="hidden" name="jenis" value="hutang">
                                <div class="col-auto">
                                    <label for="status" class="form-label mb-0">Status</label>
                                    <select name="status" id="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="">Semua</option>
                                        <option value="lunas" {{ $status === 'lunas' ? 'selected' : '' }}>Lunas</option>
                                        <option value="belum_lunas" {{ $status === 'belum_lunas' ? 'selected' : '' }}>Belum Lunas</option>
                                    </select>
                                </div>
                            </form>
                        @endif

                        {{ $hutangs->links() }}

                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Kontak</th>
                                    <th>Jumlah</th>
                                    <th>Jenis</th>
                                    <th>Kas</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($hutangs as $hutang)
                                    <tr>
                                        <td>{{ $hutang->tanggal->format('d/m/Y') }}</td>
                                        <td>{{ $hutang->kontak->nama }}</td>
                                        <td>Rp {{ number_format($hutang->jumlah, 0, ',', '.') }}</td>
                                        <td>{{ $hutang->jenis }}</td>
                                        <td>
                                            @if ($hutang->akun_detail)
                                                <a href="{{ route('akundetail.bukubesar', ['akunDetail' => $hutang->akun_detail_id]) }}">
                                                    {{ $hutang->akun_detail->nama }}
                                                </a>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($hutang->sisa <= 0)
                                                <a href="{{ route('hutang.detail', $hutang) }}" class="btn btn-sm btn-success">
                                                    Lunas
                                                </a>
                                            @else
                                                <a href="{{ route('hutang.bayar', $hutang) }}" class="btn btn-sm btn-warning">
                                                    Belum Lunas
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">Tidak ada data</td>
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
