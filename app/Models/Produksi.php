<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Produksi extends Model
{
    use SoftDeletes, HasFactory;

    public $table = 'produksis';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'nama',
        'grup',
        'warna',
        'urutan',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function orderDetail()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function projectMpDetail()
    {
        return $this->hasMany(ProjectMpDetail::class);
    }

    public static function ambilFlow($grup)
    {
        return self::where('nama', $grup)->first()->id;
    }

    public static function dashboardGroupOrder(): array
    {
        return [
            'awal',
            'Desain',
            'Setting',
            'Produksi ID Card',
            'Produksi Lanyard',
            'Selesai',
            'batal',
        ];
    }

    public static function groupedForDashboard(): Collection
    {
        return static::sortDashboardGroups(
            static::orderBy('grup')->orderBy('urutan')->orderBy('id')->get()->groupBy('grup')
        );
    }

    public static function sortDashboardGroups(Collection $produksis): Collection
    {
        $order = array_flip(static::dashboardGroupOrder());

        return $produksis->sortKeysUsing(function ($a, $b) use ($order) {
            $posA = $order[$a] ?? 998;
            $posB = $order[$b] ?? 998;

            if ($posA === $posB) {
                return strcmp($a ?? '', $b ?? '');
            }

            return $posA <=> $posB;
        });
    }

    public static function flowItems()
    {
        return static::groupedForDashboard()
            ->flatten()
            ->filter(fn ($produksi) => ! in_array($produksi->nama, ['finish', 'batal']))
            ->values();
    }

    public static function orderedForStatusSelect(): Collection
    {
        return static::groupedForDashboard()->flatten()->values();
    }

    public static function statusPathForDetail($detail): Collection
    {
        $route = static::resolveProdukRoute($detail);
        $grouped = static::groupedForDashboard();
        $path = collect();

        foreach (static::dashboardGroupOrder() as $grup) {
            if ($grup === 'batal') {
                continue;
            }

            if (! $grouped->has($grup)) {
                continue;
            }

            $items = $grouped->get($grup);

            $path = $path->concat(match ($grup) {
                'Setting' => match ($route) {
                    'idcard' => $items->where('nama', 'Setting_IDCARD'),
                    'lanyard' => $items->where('nama', 'Setting_Lanyard'),
                    default => $items,
                },
                'Produksi ID Card' => $route === 'lanyard' ? collect() : $items,
                'Produksi Lanyard' => $route === 'idcard' ? collect() : $items,
                default => $items,
            });
        }

        $batal = static::where('nama', 'batal')->first();
        if ($batal) {
            $path->push($batal);
        }

        return $path->values();
    }

    public static function initialStatus(): ?self
    {
        return static::firstInDashboardGroup('awal');
    }

    public static function resolveProdukRoute($detail): ?string
    {
        $produk = $detail->produk ?? null;
        if (! $produk) {
            return null;
        }

        $kategori = $produk->produkModel?->kategori;
        $utama = strtoupper($kategori?->kategoriUtama?->nama ?? '');
        $kategoriNama = strtoupper($kategori?->nama ?? '');
        $modelNama = strtoupper($produk->produkModel?->nama ?? '');
        $text = trim($utama.' '.$kategoriNama.' '.$modelNama.' '.strtoupper($produk->nama ?? ''));

        $isIdCard = str_contains($text, 'IDCARD') || str_contains($text, 'ID CARD');
        $isLanyard = str_contains($text, 'LANYARD');

        if ($isIdCard && ! $isLanyard) {
            return 'idcard';
        }

        if ($isLanyard && ! $isIdCard) {
            return 'lanyard';
        }

        if ($isIdCard && $isLanyard) {
            if (str_contains($utama, 'LANYARD')) {
                return 'lanyard';
            }

            if (str_contains($utama, 'ID')) {
                return 'idcard';
            }

            return 'lanyard';
        }

        return null;
    }

    public function nextInFlow($detail = null): ?self
    {
        if ($this->nama === 'ACC') {
            if (! $detail) {
                return null;
            }

            $route = static::resolveProdukRoute($detail);
            $target = match ($route) {
                'idcard' => 'Setting_IDCARD',
                'lanyard' => 'Setting_Lanyard',
                default => null,
            };

            return $target ? static::where('nama', $target)->first() : null;
        }

        $explicit = [
            'Setting_IDCARD' => 'PRINT_IDCARD',
            'Setting_Lanyard' => 'PRINT_LANYARD',
            'Finishing_IDCARD' => 'Packing',
            'Finishing_LANYARD' => 'Packing',
            'Packing' => 'Beres',
        ];

        if (isset($explicit[$this->nama])) {
            return static::where('nama', $explicit[$this->nama])->first();
        }

        if ($this->isAwalGroup()) {
            return static::firstInDashboardGroup('Desain');
        }

        if ($this->grup !== 'Setting') {
            $siblings = static::where('grup', $this->grup)
                ->whereNotIn('nama', ['finish', 'batal'])
                ->orderBy('urutan')
                ->orderBy('id')
                ->get();

            $index = $siblings->search(fn ($produksi) => $produksi->id === $this->id);
            if ($index !== false && $siblings->has($index + 1)) {
                return $siblings->get($index + 1);
            }
        }

        $nextGrup = static::nextDashboardGroup($this->grup);
        if ($nextGrup) {
            return static::firstInDashboardGroup($nextGrup);
        }

        return null;
    }

    protected static function nextDashboardGroup(?string $currentGrup): ?string
    {
        $order = static::dashboardGroupOrder();
        $index = array_search($currentGrup, $order, true);

        if ($index === false) {
            return null;
        }

        for ($i = $index + 1; $i < count($order); $i++) {
            if (in_array($order[$i], ['batal'], true)) {
                continue;
            }

            if (static::firstInDashboardGroup($order[$i])) {
                return $order[$i];
            }
        }

        return null;
    }

    protected static function firstInDashboardGroup(string $grup): ?self
    {
        return static::where('grup', $grup)
            ->whereNotIn('nama', ['finish', 'batal'])
            ->orderBy('urutan')
            ->orderBy('id')
            ->first();
    }

    public function isAwalGroup(): bool
    {
        return $this->grup === 'awal';
    }

    public function isSelesaiGroup(): bool
    {
        return in_array($this->grup, ['selesai', 'Selesai'], true);
    }

    public static function shouldDeductStock(?self $from, ?self $to): bool
    {
        return $from
            && $to
            && $from->isAwalGroup()
            && ! $to->isAwalGroup()
            && $to->grup !== 'batal';
    }

    public static function shouldRestoreStock(?self $from, ?self $to): bool
    {
        return $from
            && $to
            && $from->isSelesaiGroup()
            && $to->grup === 'batal';
    }

    public static function produkTracksStock($detail): bool
    {
        return (int) ($detail->produk?->produkModel?->stok ?? 0) === 1;
    }
}
