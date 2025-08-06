<?php
namespace App\Jobs;

use App\Models\Customer;
use App\Models\Package;
use App\Models\Bandwidth;
use App\Helpers\CustomHelper;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RefreshCustomerRadiusRecordsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $createdBy;
    public $timeout = 300;

    public function __construct($createdBy)
    {
        $this->createdBy = $createdBy;

    }

    public function handle()
    {
        DB::beginTransaction();
        try {
            DB::table('radcheck')->where('created_by', $this->createdBy)->delete();
            DB::table('radreply')->where('created_by', $this->createdBy)->delete();
            DB::table('radusergroup')->where('created_by', $this->createdBy)->delete();

            $customers = Customer::where('created_by', $this->createdBy)
                ->whereNotNull('expiry')
                ->get();

            $now = now();

            foreach ($customers as $customer) {
                $username = $customer->username ?? $customer->account;
                $password = $customer->password;
                $packageId = $customer->package_id;
                $expiry = Carbon::parse($customer->expiry);
                $extensionExpiry = $customer->extension_expiry ? Carbon::parse($customer->extension_expiry) : null;

                // Determine status
                $isExpired = $customer->status === 'off' || (
                    $extensionExpiry
                        ? $extensionExpiry->lt($now)
                        : $expiry->lt($now)
                );
                $isDisabled = $customer->is_active == 0 || $customer->is_suspended == 1;

                // Skip expired hotspot customers
                if ($isExpired && strtolower(trim($customer->service)) === 'hotspot') {
                    continue;
                }

                // Determine group and rate
                $groupName = 'Expired_Plan';
                $mikroRate = '';

                if ($isDisabled) {
                    $groupName = 'Disabled_Plan';
                } elseif (!$isExpired && $packageId) {
                    $package = Package::find($packageId);
                    if ($package) {
                        $bandwidth = Bandwidth::where('package_id', $package->id)->first();
                        $groupName = 'package_' . $package->id;

                        if ($bandwidth) {
                            $mikroRate = "{$bandwidth->rate_down}{$bandwidth->rate_down_unit}/{$bandwidth->rate_up}{$bandwidth->rate_up_unit}";
                        }
                    }
                }

                // Insert into RADIUS
                DB::table('radcheck')->insert([
                    [
                        'username' => $username,
                        'attribute' => 'Cleartext-Password',
                        'op' => ':=',
                        'value' => $password,
                        'created_by' => $this->createdBy
                    ]
                ]);

                DB::table('radreply')->insert([
                    [
                        'username' => $username,
                        'attribute' => 'Mikrotik-Rate-Limit',
                        'op' => ':=',
                        'value' => $mikroRate,
                        'created_by' => $this->createdBy
                    ]
                ]);

                DB::table('radusergroup')->insert([
                    [
                        'username' => $username,
                        'groupname' => $groupName,
                        'priority' => 1,
                        'created_by' => $this->createdBy
                    ]
                ]);

                CustomHelper::refreshCustomerInRadius($customer);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Radius refresh failed: " . $e->getMessage());
        }
    }

}
