<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = RolePermission::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->pluck('id')
            ->implode(',');

        Role::updateOrCreate(
            ['name' => 'Admin'],
            [
                'permissions' => $permissions,
                'status' => 'active',
            ]
        );
    }
}
