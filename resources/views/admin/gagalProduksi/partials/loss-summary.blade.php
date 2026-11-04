@php
    $bolehLihatGagal = auth()->user()->can('gagal_produksi_access');
    $kerugianKomplain = (int) ($totalKerugianKomplain ?? 0);
    $kerugianGagal = $bolehLihatGagal ? (int) $gagalProduksiRecords->sum('kerugian') : 0;
    $totalKerugian = $kerugianKomplain + $kerugianGagal;
    $nilaiOrder = (int) ($order->total ?? $projectMp->total ?? 0);
@endphp

@if ($totalKerugian > 0)
    <div class="alert alert-warning mb-3">
        <div>
            @if ($bolehLihatGagal)
                <div class="small">Kerugian: komplain Rp {{ number_format($kerugianKomplain, 0, ',', '.') }} + gagal produksi Rp {{ number_format($kerugianGagal, 0, ',', '.') }} = Rp {{ number_format($totalKerugian, 0, ',', '.') }}</div>
            @else
                <div class="small">Kerugian komplain: Rp {{ number_format($totalKerugian, 0, ',', '.') }}</div>
            @endif
            <div class="fw-semibold">Total order Rp {{ number_format($nilaiOrder, 0, ',', '.') }} − kerugian Rp {{ number_format($totalKerugian, 0, ',', '.') }} = nilai setelah kerugian Rp {{ number_format($nilaiOrder - $totalKerugian, 0, ',', '.') }}</div>
        </div>
    </div>
@endif
