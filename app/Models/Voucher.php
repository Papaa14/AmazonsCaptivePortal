<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Package;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'code',
        'package_id',
        'devices',
        'used_devices',
        'is_compensation',
        'status',
        'used_by',
    ];

    // Optional: relation to Package model (if you have one)
    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
