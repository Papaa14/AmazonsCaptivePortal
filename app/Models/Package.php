<?php
namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{


    protected $fillable = [
        'created_by',
        'name_plan',
        'price',
        'type',
        'typebp',
        'is_limited',
        'time_limit',
        'time_unit',
        'data_limit',
        'data_unit',
        'validity',
        'validity_unit',
        'shared_users',
        'tax_value',
        'tax_type',
        'fup_limit',
        'fup_unit',
        'fup_down_speed',
        'fup_down_unit',
        'fup_up_speed',
        'fup_up_unit',
        'fup_limit_status',
        'device',
        'assigned_to'
    ];
    protected $casts = [
        'assigned_to' => 'array',
        'price' => 'integer',
        'time_limit' => 'integer',
        'data_limit' => 'integer',
        'validity' => 'integer',
        'shared_users' => 'integer',
        'tax_value' => 'integer',
        'fup_limit' => 'integer',
        'fup_down_speed' => 'integer',
        'fup_up_speed' => 'integer',
        'fup_limit_status' => 'boolean',
        'is_limited' => 'boolean',
    ];
    public function bandwidth()
    {
        return $this->hasOne(Bandwidth::class, 'package_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function routers()
    {
        return $this->belongsToMany(Router::class, 'router_packages');
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'package_id');
    }

    public function vouchers()
    {
        return $this->hasMany(Voucher::class, 'package_id');
    }
}
