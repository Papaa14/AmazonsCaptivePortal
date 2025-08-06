<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helpers\CustomHelper;

class CustomerExpiry extends Command
{
    protected $signature = 'app:customer-expired';

    protected $description = 'Delete customers whose expiry is more than 2 days';

    public function handle()
    {
        Log::info('Processing customers...');
        $customers = Customer::where('expiry', '<', Carbon::now()->subDays(1))->where('service', 'Hotspot')->get();
        // $customers = Customer::whereNull('expiry')->where('service', 'Hotspot')->get();

        foreach ($customers as $customer) {
            Log::info("Deleting customer ID: {$customer->id}, Expiry: {$customer->expiry}");

            $activeSession = DB::table('radacct')
                ->where('username', $customer->username)
                ->whereNull('acctstoptime')
                ->orderBy('acctstarttime', 'desc')
                ->first();
    
            if ($activeSession) {
                $nasObj = DB::table('nas')->where('nasname', $activeSession->nasipaddress)->first();
                $attributes = [
                    'acctSessionID' => $activeSession->acctsessionid,
                    'framedIPAddress' => $activeSession->framedipaddress,
                ];
    
                CustomHelper::kickOutUsersByRadius($nasObj, $customer, $attributes);
            }
            DB::table('radusergroup')->where('username', $customer->username)->delete();
            DB::table('radcheck')->where('username', $customer->username)->delete();
            DB::table('radreply')->where('username', $customer->username)->delete();

            $customer->delete();

            Log::info("Customer {$customer->username} deleted.");
        }

        return 0;
    }
}