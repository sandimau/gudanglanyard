<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    public function up(): void
    {
        $permission = Permission::findOrCreate('hpp_access', 'web');
        Role::where('name', 'super')->where('guard_name', 'web')->first()?->givePermissionTo($permission);
    }

    public function down(): void
    {
        // Pertahankan pengaturan akses yang mungkin sudah diubah melalui halaman Roles.
    }
};
