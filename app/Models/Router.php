<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Router extends Model
{
    protected $table = 'routers'; // Explicitly define table name
    protected $fillable = [
        'created_by',
        'nas_id',
        'name',
        'ip_address',
        'type',
        'location',
        'secret',
        'api_port'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function nas()
    {
        return $this->belongsTo(Nas::class, 'nas_id', 'id');
    }

    public function packages()
    {
        return $this->belongsToMany(Package::class, 'router_packages');
    }
}

