<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyUsage extends Model
{
    protected $fillable = [
        'customer_id', 'created_by', 'year', 'month', 'upload', 'download'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}

