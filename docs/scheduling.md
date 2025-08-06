# Scheduling in Laravel Multi-Tenant System

## Overview

This document explains how scheduled tasks are configured and managed in our multi-tenant Laravel application. Due to server environment constraints, we use a customized approach to Laravel's standard scheduling.

## How Scheduling Works

### 1. Task Definition Location: `routes/console.php`

**All scheduled tasks MUST be defined in `routes/console.php` using the Artisan::command()->cron() pattern.**

Example:
```php
Artisan::command('payment:prewarm-cache', function () {
    $command = new \App\Console\Commands\PrewarmPaymentCache;
    $command->handle();
})->purpose('Pre-warm payment gateway settings cache')->cron('*/5 * * * *');
```

### 2. Do NOT Use `app/Console/Kernel.php` for Scheduling

While Laravel's standard approach is to define tasks in the `schedule()` method of `app/Console/Kernel.php`, this approach does not work correctly in our server environment. The `schedule()` method is kept for compatibility but should not be used for actual scheduling.

### 3. Crontab Configuration

The system requires a single crontab entry to run the Laravel scheduler every minute:

```
* * * * * cd /path/to/project && php artisan schedule:run
```

Use the `scripts/update-cron.sh` script to properly install or update this entry.

## Common Schedule Patterns

- **Every minute**: `* * * * *`
- **Every 5 minutes**: `*/5 * * * *`
- **Hourly**: `0 * * * *`
- **Daily at midnight**: `0 0 * * *`
- **Daily at specific time**: Use `dailyAt('HH:MM')` instead of cron

## Debugging Scheduled Tasks

To test if a scheduled task works:

1. Run the command directly via artisan:
   ```
   php artisan payment:prewarm-cache
   ```

2. Check the logs:
   - General Laravel logs: `storage/logs/laravel.log`
   - Specific task logs: `storage/logs/payment-cache.log`

## Multi-tenancy Considerations

For payment gateway caching and other multi-tenant operations:

1. The `PrewarmPaymentCache` command runs every 5 minutes to ensure all tenants have their payment gateway details cached
2. The cache is warmed for both Hotspot and PPPoE payment types
3. This significantly reduces gateway lookup times during high-traffic periods
4. Most active users are also pre-cached during application boot

## Preventing Job Overlap

For high-frequency jobs, we use file locks to prevent overlapping runs. This is implemented in the command wrappers in `routes/console.php`. 