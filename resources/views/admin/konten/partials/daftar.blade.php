<div class="table-responsive">
    <table class="table table-hover align-middle mb-0 konten-table">
        <thead class="table-secondary">
            <tr>
                <th scope="col" class="fw-bold">Topik</th>
                <th scope="col" class="fw-bold">Posting</th>
                <th scope="col" class="fw-bold">Platform</th>
                <th scope="col" class="fw-bold">Tujuan</th>
                @if ($allTabs->isNotEmpty())
                    <th scope="col" class="fw-bold">Proses</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($kontenItems as $konten)
                @include('admin.konten.partials.kartu')
            @empty
                <tr>
                    <td colspan="{{ $allTabs->isNotEmpty() ? 5 : 4 }}" class="text-center text-muted py-3">Tidak ada konten di tab ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
