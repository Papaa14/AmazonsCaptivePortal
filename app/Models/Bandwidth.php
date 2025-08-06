<?php
namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class Bandwidth extends Model
{
    protected $fillable = [
        'created_by',
        'name_plan', 
        'rate_down', 
        'rate_down_unit',
        'rate_up', 
        'rate_up_unit', 
        'burst',
        'package_id'
    ];
    
    protected $casts = [
        'rate_down' => 'integer',
        'rate_up' => 'integer',
        'burst' => 'integer',
    ];
    
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }
}
