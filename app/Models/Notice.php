<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    protected $fillable = [
        'title', 'message', 'superadmin_only', 'start_date', 'end_date', 'created_by'
    ];

    protected $casts = [
        'superadmin_only' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // Optional: Scope for active notices
    public function scopeActive($query)
    {
        $today = now()->toDateString();
        return $query->where(function ($q) use ($today) {
            $q->whereNull('start_date')->orWhere('start_date', '<=', $today);
        })->where(function ($q) use ($today) {
            $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
        });
    }
}

