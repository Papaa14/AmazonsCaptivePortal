<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoadUserPermissions
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            // Force reload permissions
            app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            
            // Eager load permissions with roles
            $user = Auth::user();
            $user->load(['roles.permissions', 'permissions']);
        }

        return $next($request);
    }
} 