@extends('layouts.app')

@section('title')
    Buffer Marketplace Pending
@endsection

@section('content')
    <header class="header mb-4">
        <div class="container-fluid">
            <h5 class="card-title">Buffer Marketplace (Belum Terhubung Project)</h5>
            <p class="text-muted mb-0">Data <code>marketplace_buffers</code> dengan <code>project_id</code> null</p>
        </div>
    </header>
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <span>Total: {{ $buffers->total() }} record</span>
                    <a href="{{ route('buffer.proses') }}" class="btn btn-sm btn-primary">Proses Buffer</a>
                </div>
            </div>
            <div class="card-body">
                @include('layouts.includes.messages')
                <div class="d-flex justify-content-center">
                    {{ $buffers->links() }}
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nota</th>
                                <th>Marketplace</th>
                                <th>Shop ID</th>
                                <th>Status</th>
                                <th>MP</th>
                                <th>Custom</th>
                                <th>Dibuat</th>
                                <th>Diupdate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($buffers as $buffer)
                                <tr>
                                    <td>{{ $buffer->id }}</td>
                                    <td>{{ $buffer->nota ?? '-' }}</td>
                                    <td>{{ $buffer->nama_marketplace ?? '-' }}</td>
                                    <td>{{ $buffer->shop_id ?? '-' }}</td>
                                    <td>{{ $buffer->status ?? '-' }}</td>
                                    <td>{{ $buffer->mp ?? '-' }}</td>
                                    <td>{{ $buffer->custom ? 'Ya' : 'Tidak' }}</td>
                                    <td>{{ $buffer->created_at ? $buffer->created_at->format('d/m/Y H:i') : '-' }}</td>
                                    <td>{{ $buffer->updated_at ? $buffer->updated_at->format('d/m/Y H:i') : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">Tidak ada data buffer pending</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
