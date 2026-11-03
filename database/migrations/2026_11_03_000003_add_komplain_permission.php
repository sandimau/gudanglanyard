<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $access = Permission::findOrCreate('komplain_access', 'web');
        $approve = Permission::findOrCreate('komplain_approve', 'web');

        foreach (['super', 'Manager', 'Marketing', 'CS_ONLINE', 'supervisor', 'Setting', 'Produksi'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()?->givePermissionTo($access);
        }

        foreach (['super', 'Manager'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()?->givePermissionTo($approve);
        }
    }

    public function down(): void
    {
        // Keep permission records and role grants to avoid removing live access on rollback.
    }
};
