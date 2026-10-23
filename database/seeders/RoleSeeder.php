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
    }
}
