<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class CostCalculatorPermissionSeeder extends Seeder
{
    public function run()
    {
        Permission::create(['name' => 'manage cost calculator']);
    }
} 