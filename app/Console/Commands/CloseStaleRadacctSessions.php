<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CloseStaleRadacctSessions extends Command
{

    protected $signature = 'app:close-stale-radacct-sessions';

    protected $description = 'Closes radacct sessions that are stale or have a termination cause but no acctstoptime.';

    public function handle()
    {
        // 1. Close sessions that have acctterminatecause but no acctstoptime
        $closedByCause = DB::table('radacct')
            ->whereNull('acctstoptime')
            ->whereNotNull('acctterminatecause')
            ->where('acctterminatecause', '!=', '')
            ->update([
                'acctstoptime' => now()
            ]);

        // Output results
        $this->info("Closed $closedByCause sessions via acctterminatecause.");
        $this->info("All stale RADIUS sessions processed.");
        Log::info("Closed $closedByCause sessions via acctterminatecause.");
    }
}
