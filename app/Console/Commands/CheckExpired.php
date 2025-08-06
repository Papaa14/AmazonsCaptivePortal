<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Package;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Helpers\CustomHelper;

class CheckExpired extends Command
{
    protected $signature = 'customer:checkexpiry';
    protected $description = 'Check and update customer expiry status';
    public function __construct()
    {
        parent::__construct();
    }
    public function handle()
    {
        Log::info('Processing customers...');

        $customers = Customer::where('corporate', 0)
            ->where('expiry', '<', now())
            ->where(function ($query) {
                $query->where('status', 'on')
                    ->orWhere(function ($q) {
                        $q->where('status', 'off')
                            ->where('balance', '>', 0);
                    });
            })
            ->get();

        foreach ($customers as $customer) {
            $balance = $customer->balance;

            if ($customer->extension_expiry && Carbon::parse($customer->extension_expiry)->isFuture()) {
                continue;
            }

            $package = Package::where(function ($q) use ($customer) {
                    if ($customer->package_id) {
                        $q->where('id', $customer->package_id);
                    } else {
                        $q->where('name_plan', $customer->package);
                    }
                })
                ->where('created_by', $customer->created_by)
                ->first();

            if (!$package) {
                Log::error("Package not found for customer ID {$customer->id} with package name '{$customer->package}'");
                $customer->update(['status' => 'off']);
                CustomHelper::handleExpiry($customer);
                continue;
            }

            $packagePrice = $package->price;

            if ($balance < $packagePrice && $customer->status === 'on') {
                Log::info("Expiring customer ID {$customer->id} — insufficient balance ({$balance} < {$packagePrice})");
                $customer->update(['status' => 'off']);
                CustomHelper::handleExpiry($customer);
            } elseif ($balance >= $packagePrice && $customer->expiry < now()) {
                // Renew
                $transactionDate = now();
                $TransID = "REN-" . strtoupper(Str::random(6));
                $amount = (int)$packagePrice;
                $type = $customer->service;
                $isp = $customer->created_by;

                CustomHelper::handlePayments($amount, $package, $customer, $transactionDate, $TransID, $isp, $type);
            } elseif ($customer->status === 'on') {
                $customer->update(['status' => 'off']);
                CustomHelper::handleExpiry($customer);
            }
        }

        return 0;
    }
}
