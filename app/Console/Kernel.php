<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        // In Laravel 11, schedule registration happens in routes/console.php
        // using the Schedule facade. This method is still needed by the 
        // framework but doesn't need to contain anything.
        
        // Clean up stale RADIUS sessions every 6 hours
        $schedule->call(function () {
            DB::table('radacct')
                ->whereNull('acctstoptime')
                ->where(function($query) {
                    // Close sessions that haven't been updated in 6 hours
                    $query->where('acctupdatetime', '<', DB::raw('DATE_SUB(NOW(), INTERVAL 6 HOUR)'))
                        ->orWhereNull('acctupdatetime');
                })
                ->whereNotNull('acctstarttime')
                ->update([
                    'acctstoptime' => DB::raw('COALESCE(acctupdatetime, NOW())'),
                    'acctterminatecause' => 'stale_session_cleanup'
                ]);
        })->everyFourHours();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */ 
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        // This loads any Artisan commands and the Schedule facade from console.php
        require base_path('routes/console.php');
    }
}
