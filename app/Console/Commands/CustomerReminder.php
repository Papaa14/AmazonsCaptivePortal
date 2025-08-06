<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Helpers\CustomHelper;

class CustomerReminder extends Command
{
    protected $signature = 'app:customer-reminder';
    protected $description = 'Check and send customer Reminder';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        Log::info('Processing expiring customers reminders...');

        $now = Carbon::now();
        $in1Day = $now->copy()->addDay()->toDateString(); 
        $in3Days = $now->copy()->addDays(3)->toDateString();

        $customers = Customer::where('status', 'on')
            ->where('corporate', 0)
            ->whereDate('expiry', $in1Day)
            ->orWhereDate('expiry', $in3Days)
            ->get();

        foreach ($customers as $customer) {
            CustomHelper::handleReminder($customer);
        }

        return 0;
    }
}

