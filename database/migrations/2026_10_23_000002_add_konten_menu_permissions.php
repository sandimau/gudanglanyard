<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $jadwal = Permission::findOrCreate('konten_jadwal_access', 'web');
        $proses = Permission::findOrCreate('konten_proses_access', 'web');

        Role::findByName('konten', 'web')->givePermissionTo([$jadwal, $proses]);
    }

    public function down(): void
    {
        // Keep permission records and role grants to avoid removing live access on rollback.
    }
};
