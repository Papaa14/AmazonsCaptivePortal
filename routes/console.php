<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/**
 * Laravel 11 Scheduling Configuration
 * 
 * This file defines the scheduled tasks for the application using Laravel's
 * Schedule facade, which is the recommended approach for Laravel 11.
 */

// Check for expired customers every minute with overlap protection
Schedule::command('customer:checkexpiry')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
    
// Delete customers expired for more than 1 day - daily at midnight
Schedule::command('app:customer-expired')
    ->dailyAt('00:00')
    ->withoutOverlapping();
    
// Send reminder SMS to customers daily at 7 AM
Schedule::command('app:customer-reminder')
    ->dailyAt('07:00')
    ->withoutOverlapping();
    
// Pre-warm payment gateway settings cache every 5 minutes
Schedule::command('payment:prewarm-cache')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/payment-cache.log')); 

// Update monthly usage from radacct every 10 minutes
Schedule::command('usage:update-incremental')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Clean up stale RADIUS sessions every 1 minutes that have acctterminatecause
Schedule::command('app:close-stale-radacct-sessions')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Schedule::command('app:backup-database')
//     ->everyFiveMinutes()
//     ->withoutOverlapping()
//     ->appendOutputTo(storage_path('logs/database-buckup.log')); 

Schedule::command('app:backup-database')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/database-backup.log'));
