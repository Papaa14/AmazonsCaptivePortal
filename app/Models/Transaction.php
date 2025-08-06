<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'user_type',
        'account',
        'site',
        'type',
        'amount',
        'description',
        'date',
        'created_by',
        'payment_id',
        'category',
        'gateway',
        'checkout_id',
        'mpesa_code',
        'package_id',
        'phone',
        'status'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // public function plan()
    // {
    //     return $this->belongsTo(Plan::class, 'plan_id');
    // }

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }
}
