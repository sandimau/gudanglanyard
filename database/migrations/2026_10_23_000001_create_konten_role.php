<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Role::firstOrCreate(['name' => 'konten', 'guard_name' => 'web']);
    }

    public function down(): void
    {
        // Retain the role so rollback does not remove user assignments.
    }
};
