<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; // Make sure to import Carbon

class HotspotUsers extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone_number',
        'otp',
        'expires_at'
    ];

   
}