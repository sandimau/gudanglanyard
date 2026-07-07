@extends('layouts.app')

@section('title')
    Produksi List
@endsection

@section('content')
    <div class="bg-light rounded">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Produksis</h5>
                    </div>
                    <a href="{{ route('produksis.create') }}" class="btn btn-primary">Add produksis</a>
                </div>
            </div>
            <div class="card-body">
                @include('layouts.includes.messages')

                @foreach ($produksis as $grup => $items)
                    <div class="mb-4">
                        <h6 class="fw-bold mb-2">{{ $grup ?: '(Tanpa Grup)' }}</h6>
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col" style="width: 40px;"></th>
                                        <th scope="col">Nama</th>
                                        <th scope="col">Warna</th>
                                        <th scope="col">Urutan</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="sortable-grup" data-grup="{{ $grup }}">
                                    @foreach ($items as $produksi)
                                        <tr data-id="{{ $produksi->id }}">
                                            <td class="text-center drag-handle" style="cursor: move;">
                                                <i class='bx bx-move'></i>
                                            </td>
                                            <td>{{ $produksi->nama }}</td>
                                            <td>{{ $produksi->warna }}</td>
                                            <td class="urutan-value">{{ $produksi->urutan }}</td>
                                            <td>
                                                <div class="d-flex">
                                                    <a href="{{ route('produksis.edit', $produksi->id) }}"
                                                        class="btn btn-info btn-sm me-1"><i class='bx bxs-edit'></i> Edit</a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach

            </div>
        </div>
    </div>
@endsection

@push('after-scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script>
        document.querySelectorAll('.sortable-grup').forEach(function(tbody) {
            new Sortable(tbody, {
                handle: '.drag-handle',
                animation: 150,
                onEnd: function() {
                    let ids = Array.from(tbody.querySelectorAll('tr')).map(tr => tr.dataset.id);

                    tbody.querySelectorAll('tr').forEach(function(tr, index) {
                        tr.querySelector('.urutan-value').innerText = index + 1;
                    });

                    fetch('{{ route('produksis.updateUrutan') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            ids: ids
                        }),
                    });
                },
            });
        });
    </script>
@endpush
