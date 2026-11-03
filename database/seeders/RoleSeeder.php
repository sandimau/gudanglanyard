<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['super', 'admin', 'user', 'konten'] as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $kontenRole = Role::findByName('konten', 'web');
        $kontenRole->givePermissionTo([
            Permission::findOrCreate('konten_jadwal_access', 'web'),
            Permission::findOrCreate('konten_proses_access', 'web'),
        ]);

        $superRole = Role::findByName('super', 'web');
        $superRole->givePermissionTo([
            Permission::findOrCreate('desain_access', 'web'),
            Permission::findOrCreate('desain_setup_access', 'web'),
            Permission::findOrCreate('hpp_access', 'web'),
            Permission::findOrCreate('komplain_access', 'web'),
            Permission::findOrCreate('komplain_approve', 'web'),
        ]);

        foreach (['Manager', 'Marketing', 'CS_ONLINE', 'supervisor', 'Setting', 'Produksi'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $role?->givePermissionTo(Permission::findOrCreate('komplain_access', 'web'));
        }

        $managerRole = Role::where('name', 'Manager')->where('guard_name', 'web')->first();
        $managerRole?->givePermissionTo(Permission::findOrCreate('komplain_approve', 'web'));
    }
}
