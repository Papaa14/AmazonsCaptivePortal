<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadAcct extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'rad_acct';
    
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    
    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;
    
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
        'acctsessionid',
        'username',
        'realm',
        'nasid',
        'nasipaddress',
        'nasportid',
        'nasporttype',
        'framedipaddress',
        'acctinputoctets',
        'acctoutputoctets',
        'acctstatustype',
        'macaddr',
        'dateAdded'
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'acctinputoctets' => 'integer',
        'acctoutputoctets' => 'integer',
        'dateAdded' => 'datetime'
    ];
    
    /**
     * Get the customer associated with this accounting record.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'username', 'username');
    }
    
    /**
     * Get the NAS device associated with this accounting record.
     */
    public function nas()
    {
        return $this->belongsTo(Nas::class, 'nasid', 'nasname');
    }
} 