<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $access = Permission::findOrCreate('gagal_produksi_access', 'web');
        $manage = Permission::findOrCreate('gagal_produksi_manage', 'web');

        foreach (['super', 'admin', 'Manager', 'Marketing', 'CS_ONLINE', 'supervisor', 'Setting', 'Produksi'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()?->givePermissionTo($access);
        }

        foreach (['super', 'admin', 'Manager', 'supervisor', 'Setting', 'Produksi'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()?->givePermissionTo($manage);
        }
    }

    public function down(): void
    {
        // Hak akses yang sudah diatur lewat Roles tidak dihapus saat rollback.
    }
};
