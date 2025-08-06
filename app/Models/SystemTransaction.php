<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemTransaction extends Model
{
    protected $fillable = [
        'created_by',
        'company_id',
        'plan_id',
        'checkout',
        'payment_method',
        'reference',
        'amount',
        'currency',
        'description',
        'phone',
        'status',
        'paid_at',
    ];

    protected $dates = ['paid_at'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
