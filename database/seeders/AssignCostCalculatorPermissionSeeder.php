<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AssignCostCalculatorPermissionSeeder extends Seeder
{
    public function run()
    {
        $superAdminRole = Role::where('name', 'super admin')->first();
        $permission = Permission::where('name', 'manage cost calculator')->first();
        
        if ($superAdminRole && $permission) {
            $superAdminRole->givePermissionTo($permission);
        }
    }
} 