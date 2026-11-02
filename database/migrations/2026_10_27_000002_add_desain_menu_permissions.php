<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $desain = Permission::findOrCreate('desain_access', 'web');
        $setup = Permission::findOrCreate('desain_setup_access', 'web');

        if (Schema::hasTable('roles')) {
            $super = Role::where('name', 'super')->where('guard_name', 'web')->first();
            $super?->givePermissionTo([$desain, $setup]);
        }
    }

    public function down(): void
    {
        // Keep permission records and role grants to avoid removing live access on rollback.
    }
};
