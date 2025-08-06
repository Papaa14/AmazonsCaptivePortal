<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostCalculator extends Model
{
    protected $fillable = [
        'min_users',
        'max_users',
        'cost_per_user',
        'created_by'
    ];

    protected $casts = [
        'min_users' => 'integer',
        'max_users' => 'integer',
        'cost_per_user' => 'decimal:2'
    ];
} 