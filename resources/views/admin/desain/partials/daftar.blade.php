<div class="table-responsive">
    <table class="table table-hover align-middle mb-0 desain-table">
        <thead class="table-secondary">
            <tr>
                <th scope="col" class="fw-bold">Konsumen</th>
                <th scope="col" class="fw-bold">Produk</th>
                <th scope="col" class="fw-bold">Tema</th>
                <th scope="col" class="fw-bold">Tipe</th>
                @if ($allTabs->isNotEmpty())
                    <th scope="col" class="fw-bold">Proses</th>
                @endif
                <th scope="col" class="fw-bold">Pemproses</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($desainItems as $desain)
                @include('admin.desain.partials.kartu')
            @empty
                <tr>
                    <td colspan="{{ $allTabs->isNotEmpty() ? 6 : 5 }}" class="text-center text-muted py-3">Tidak ada desain di sub kategori ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
