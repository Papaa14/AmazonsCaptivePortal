<?php

namespace App\Http\Middleware;

use Closure;
use Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class XSS
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            if(auth()->check())
            {
                $settings = \App\Models\Utility::settingsById(auth()->user()->creatorId());
                if (!empty($settings['timezone'])) {
                    Config::set('app.timezone', $settings['timezone']);
                    date_default_timezone_set(Config::get('app.timezone', 'UTC'));
                }

                app()->setLocale(auth()->user()->lang);
            }

            // Sanitize input data
            $input = $request->all();
            array_walk_recursive($input, function (&$input) {
                $input = strip_tags($input);
            });
            $request->merge($input);

            return $next($request);
        } catch (\Throwable $e) {
            Log::error('[XSS Middleware Error] ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            // For development, you can return the error message. In production, return a generic error.
            if (config('app.debug')) {
                return response()->json([
                    'error' => 'XSS Middleware Exception',
                    'message' => $e->getMessage(),
                ], 500);
            } else {
                return response()->view('errors.500', [], 500);
            }
        }
    }
}
