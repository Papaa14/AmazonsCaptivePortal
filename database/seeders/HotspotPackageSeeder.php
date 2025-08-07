<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\HotspotPackage; 

class HotspotPackageSeeder extends Seeder
{
    public function run(): void
    {
      
       HotspotPackage::query()->delete();


        $packages = [
            // Time-based unlimited plans
            ['id' => 25, 'name' => 'Sh0= 20Minutes UnlimiNET', 'price' => 0, 'duration_minutes' => 30, 'is_unlimited' => true, 'device_limit' => 1, 'is_free' => true],
            ['id' => 26, 'name' => 'Sh5= 30Minutes UnlimiNET', 'price' => 5, 'duration_minutes' => 30, 'is_unlimited' => true, 'device_limit' => 1],
            ['id' => 1, 'name' => 'Sh9= 1Hour UnlimiNET', 'price' => 9, 'duration_minutes' => 60, 'is_unlimited' => true, 'device_limit' => 1],
            ['id' => 12, 'name' => 'Sh13= 2Hours UnlimiNET', 'price' => 13, 'duration_minutes' => 120, 'is_unlimited' => true, 'device_limit' => 1],
            ['id' => 2, 'name' => 'Sh20= 4Hours UnlimiNET', 'price' => 20, 'duration_minutes' => 240, 'is_unlimited' => true, 'device_limit' => 1],
            ['id' => 11, 'name' => 'Sh30= 10Hours UnlimiNET', 'price' => 30, 'duration_minutes' => 600, 'is_unlimited' => true, 'device_limit' => 1],
            ['id' => 8, 'name' => 'Sh40= UnlimiNET till midnight', 'price' => 40, 'duration_minutes' => 0, 'is_unlimited' => true, 'device_limit' => 1, 'description' => 'Validity lasts until midnight of the activation day.'], // Special case
            ['id' => 17, 'name' => 'Sh49= 10Hours UnlimiNET', 'price' => 49, 'duration_minutes' => 600, 'is_unlimited' => true, 'device_limit' => 2],
            ['id' => 4, 'name' => 'Sh50= 24Hours UnlimiNET', 'price' => 50, 'duration_minutes' => 1440, 'is_unlimited' => true, 'device_limit' => 1],
            ['id' => 28, 'name' => 'Sh79= 24Hours UnlimiNET', 'price' => 79, 'duration_minutes' => 1440, 'is_unlimited' => true, 'device_limit' => 2],
            ['id' => 18, 'name' => 'Sh99= 24Hours UnlimiNET', 'price' => 99, 'duration_minutes' => 1440, 'is_unlimited' => true, 'device_limit' => 3],
            ['id' => 45, 'name' => 'Sh125= 3Days UnlimiNET', 'price' => 125, 'duration_minutes' => 4320, 'is_unlimited' => true, 'device_limit' => 1],
            ['id' => 29, 'name' => 'Sh249= 72Hours UnlimiNET', 'price' => 249, 'duration_minutes' => 4320, 'is_unlimited' => true, 'device_limit' => 3],
            ['id' => 5, 'name' => 'Sh250= 7Days UnlimiNET', 'price' => 250, 'duration_minutes' => 10080, 'is_unlimited' => true, 'device_limit' => 1],
            ['id' => 6, 'name' => 'Sh850= 30Days UnlimiNET', 'price' => 850, 'duration_minutes' => 43200, 'is_unlimited' => true, 'device_limit' => 1],

            // Data-capped plans
            ['id' => 15, 'name' => 'Sh29= 1GB + 500MB bonus valid 24Hours', 'price' => 29, 'duration_minutes' => 1440, 'is_unlimited' => false, 'data_limit_mb' => 1024, 'bonus_data_mb' => 512, 'device_limit' => 1],
            ['id' => 21, 'name' => 'Sh39= 2GB + 1GB bonus valid for 24Hours', 'price' => 39, 'duration_minutes' => 1440, 'is_unlimited' => false, 'data_limit_mb' => 2048, 'bonus_data_mb' => 1024, 'device_limit' => 1],
            ['id' => 27, 'name' => 'Sh59= 4GB + 500MB bonus valid for 48Hours', 'price' => 59, 'duration_minutes' => 2880, 'is_unlimited' => false, 'data_limit_mb' => 4096, 'bonus_data_mb' => 512, 'device_limit' => 1],
            ['id' => 22, 'name' => 'Sh69= 5GB + 500MB bonus valid for 72Hours', 'price' => 69, 'duration_minutes' => 4320, 'is_unlimited' => false, 'data_limit_mb' => 5120, 'bonus_data_mb' => 512, 'device_limit' => 1],
        ];

        foreach ($packages as $package) {
            HotspotPackage::create($package);
        }
    }
}