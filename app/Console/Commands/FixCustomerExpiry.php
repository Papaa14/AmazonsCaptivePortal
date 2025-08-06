<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\Package;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FixCustomerExpiry extends Command
{
    protected $signature = 'fix:customer-expiry';
    protected $description = 'Fix customer expiry dates using radacct uptime';

    // public function handle()
    // {
    //     $customers = Customer::where('status', 'on')->where('service', 'Hotspot')->get();
    
    //     foreach ($customers as $customer) {
    
    //         $package = Package::where('name_plan', $customer->package)->first();
    
    //         if (!$package) {
    //             $this->error("Package not found for customer ID {$customer->id}");
    //             continue;
    //         }
    
    //         $activationTime = $customer->updated_at ? Carbon::parse($customer->updated_at) : Carbon::now();
    
    //         if ($package->validity_unit === 'Months') {
    //             $newExpiry = $activationTime->copy()->addMonths($package->validity);
    //         } else {
    //             $seconds = match ($package->validity_unit) {
    //                 'Minutes' => $package->validity * 60,
    //                 'Hours'   => $package->validity * 3600,
    //                 'Days'    => $package->validity * 86400,
    //                 default   => 0,
    //             };
    
    //             $newExpiry = $activationTime->copy()->addSeconds($seconds);
    //         }
    
    //         $customer->expiry = $newExpiry->toDateTimeString();
    //         $customer->save();
    
    //         Log::info("Customer ID {$customer->id} expiry updated to {$customer->expiry}");
    //     }
    
    //     Log::info("Customer expiry correction completed.");
    // }
    public function handle()
{
    $customers = Customer::where('status', 'on')->where('service', 'Hotspot')->get();

    foreach ($customers as $customer) {

        $package = $customer->package_id ? 
            Package::find($customer->package_id) : 
            Package::where('name_plan', $customer->package)->first();

        if (!$package) {
            $this->error("Package not found for customer ID {$customer->id}");
            continue;
        }

        // ✅ Get latest transaction date
        $latestTransaction = DB::table('transactions')
            ->where('user_id', $customer->id)
            ->orderBy('date', 'desc')
            ->value('date'); // just get the latest 'date' column

        $activationTime = $latestTransaction ? Carbon::parse($latestTransaction) : Carbon::now();

        if ($package->validity_unit === 'Months') {
            $newExpiry = $activationTime->copy()->addMonths($package->validity);
        } else {
            $seconds = match ($package->validity_unit) {
                'Minutes' => $package->validity * 60,
                'Hours'   => $package->validity * 3600,
                'Days'    => $package->validity * 86400,
                default   => 0,
            };

            $newExpiry = $activationTime->copy()->addSeconds($seconds);
        }

        $customer->expiry = $newExpiry->toDateTimeString();
        $customer->save();

        $this->info("Customer ID {$customer->id} expiry updated to {$customer->expiry}");
    }

    $this->info("Customer expiry correction completed.");
}


}
