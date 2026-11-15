<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $this->seedAkunAset();
        $this->seedPermissions();
    }

    private function seedAkunAset(): void
    {
        if (! Schema::hasTable('akun_details') || ! Schema::hasTable('akun_kategoris')) {
            return;
        }

        $kategoriId = function (string $nama): ?int {
            return DB::table('akun_kategoris')
                ->where('nama', $nama)
                ->whereNull('deleted_at')
                ->value('id');
        };

        $akun = [
            '12101 Aset Tetap' => $kategoriId('aset tetap'),
            '12201 Akumulasi Penyusutan Aset Tetap' => $kategoriId('aset tetap'),
            '21101 Hutang Usaha' => $kategoriId('hutang'),
            '42101 Keuntungan Penjualan Aset' => $kategoriId('pendapatan'),
            '52301 Beban Penyusutan Aset Tetap' => $kategoriId('operasional'),
            '55101 Kerugian Penjualan Aset' => $kategoriId('operasional'),
        ];

        foreach ($akun as $nama => $kategori) {
            if (! $kategori) {
                continue;
            }

            $sudahAda = DB::table('akun_details')
                ->where('nama', $nama)
                ->whereNull('deleted_at')
                ->exists();

            if ($sudahAda) {
                continue;
            }

            DB::table('akun_details')->insert([
                'nama' => $nama,
                'saldo' => 0,
                'akun_kategori_id' => $kategori,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedPermissions(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        $access = Permission::findOrCreate('aset_access', 'web');
        $manage = Permission::findOrCreate('aset_manage', 'web');

        foreach (['super', 'admin', 'Manager', 'Keuangan'] as $roleName) {
            Role::where('name', $roleName)
                ->where('guard_name', 'web')
                ->first()
                ?->givePermissionTo([$access, $manage]);
        }
    }

    public function down(): void
    {
        // Akun dan hak akses yang sudah dipakai tidak dihapus saat rollback.
    }
};
