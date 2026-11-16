<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProdukModel extends Model
{
    use HasFactory, SoftDeletes;

    public static function gambarUrl(?string $gambar): ?string
    {
        if (! $gambar) {
            return null;
        }

        if (str_starts_with($gambar, 'storage:')) {
            return route('produkModel.gambar', ['filename' => substr($gambar, strlen('storage:'))]);
        }

        return asset('uploads/produk/' . $gambar);
    }

    protected $fillable = [
        'nama',
        'harga',
        'satuan',
        'deskripsi',
        'jual',
        'beli',
        'stok',
        'produksi',
        'gambar',
        'kategori_id',
        'kontak_id',
        'stok_min_mp',
    ];

    public function kategori()
    {
        return $this->belongsTo(ProdukKategori::class, 'kategori_id');
    }

    public function kontak()
    {
        return $this->belongsTo(Kontak::class, 'kontak_id');
    }

    public function produk()
    {
        return $this->hasMany(Produk::class, 'produk_model_id');
    }
}
