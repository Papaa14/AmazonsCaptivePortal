<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadPostAuth extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'radpostauth';
    
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'pass',
        'reply',
        'nasipaddress',
        'nasportid',
        'mac',
        'authdate', 
        'class'
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'authdate' => 'datetime',
    ];
    
    /**
     * Get the user that made this authentication attempt.
     */
    public function user()
    {
        return $this->belongsTo(Customer::class, 'username', 'username');
    }
} 