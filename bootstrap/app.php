<?php
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\LocaleMiddleware;
use App\Http\Middleware\XSS;
use App\Http\Middleware\FilterRequest;
use App\Console\Commands\CheckExpired;
use App\Console\Commands\CustomerExpiry;
use App\Console\Commands\CustomerReminder;
use App\Console\Commands\PrewarmPaymentCache;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web([
            \App\Http\Middleware\LocaleMiddleware::class,
            \App\Http\Middleware\XSS::class,
            \App\Http\Middleware\FilterRequest::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
