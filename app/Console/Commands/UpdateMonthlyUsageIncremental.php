<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use App\Models\MonthlyUsage;
use Carbon\Carbon;
use Illuminate\Support\Str;
class UpdateMonthlyUsageIncremental extends Command
{
    protected $signature = 'usage:update-incremental';
    protected $description = 'Update monthly usage from radacct, storing in corresponding months and updating last_seen.';

    public function handle()
    {
        $radaccts = DB::table('radacct')
            ->whereNotNull('acctstoptime')
            ->get();

        $processed = 0;
        $deleteIds = [];

        foreach ($radaccts as $acct) {
            if (!$acct->radacctid) {
                continue; // skip malformed entry
            }

            // Match customer
            $customer = Customer::where('username', $acct->username)
                ->where('created_by', $acct->created_by)
                ->first();

            if ($customer) {
                // Fetch package info
                // $package = DB::table('packages')->where('id', $customer->package_id)->first();

                // Calculate usage
                $start = Carbon::parse($acct->acctstarttime);
                $year = $start->year;
                $month = $start->month;

                $download = (int) $acct->acctoutputoctets;
                $upload = (int) $acct->acctinputoctets;
                $total = $upload + $download;

                // Update last_seen
                DB::table('customers')
                    ->where('id', $customer->id)
                    ->update([
                        'last_seen' => $acct->acctstoptime,
                    ]);

                // Increment used_data if limited
                if ($customer->service === 'Hotspot') {

                    $customer->used_data += $total;
                    $customer->save();

                    // Log::info('Customer is Hotspot added to customer used data');
                    // Log::info('Customer service:', ['data' => $total]);
                }
                
                // Update or create monthly usage
                $usage = MonthlyUsage::firstOrNew([
                    'customer_id' => $customer->id,
                    'year' => $year,
                    'month' => $month,
                ]);

                $usage->created_by = $customer->created_by;
                $usage->upload += $upload;
                $usage->download += $download;
                $usage->save();
                
                // Log::info('Customer service:', ['service' => $customer->service]);
            }

            // Regardless of match or not, mark for deletion
            $deleteIds[] = $acct->radacctid;
            $processed++;
        }

        // Delete all processed radacct entries
        if (!empty($deleteIds)) {
            DB::table('radacct')->whereIn('radacctid', $deleteIds)->delete();
        }

        $this->info("Processed and deleted {$processed} radacct sessions, including unmatched.");

    }
}
