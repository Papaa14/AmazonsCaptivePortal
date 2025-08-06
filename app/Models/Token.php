<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'token';
    
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'tokenID';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'token_card_id',
        'username',
        'password',
        'package_id',
        'salesperson',
        'datetime',
        'status',
        'token_usage_status',
        'nas_id',
        'serial',
        'duration',
        'duration_type',
        'expiration',
        'expiration_action',
        'total_data_vol',
        'used_data_vol',
        'data_vol_action',
        'total_session_vol',
        'used_session_vol',
        'session_vol_action',
        'allow_multi_use',
        'allow_other_nas',
        'allow_multiple_mac',
        'allow_multiple_ip',
        'created_by',
        'updated_by',
        'created_at',
        'update_at'
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'token_card_id' => 'integer',
        'package_id' => 'integer',
        'salesperson' => 'integer',
        'datetime' => 'datetime',
        'status' => 'integer',
        'token_usage_status' => 'integer',
        'nas_id' => 'integer',
        'duration' => 'integer',
        'duration_type' => 'integer',
        'expiration' => 'datetime',
        'expiration_action' => 'integer',
        'total_data_vol' => 'integer',
        'used_data_vol' => 'integer',
        'data_vol_action' => 'integer',
        'total_session_vol' => 'integer',
        'used_session_vol' => 'integer',
        'session_vol_action' => 'integer',
        'allow_multi_use' => 'boolean',
        'allow_other_nas' => 'boolean',
        'allow_multiple_mac' => 'boolean',
        'allow_multiple_ip' => 'boolean',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'update_at' => 'datetime'
    ];
    
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
    
    /**
     * Get the package associated with this token.
     */
    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }
    
    /**
     * Get the NAS device associated with this token.
     */
    public function nas()
    {
        return $this->belongsTo(Nas::class, 'nas_id');
    }
    
    /**
     * Get the creator user.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    /**
     * Get the updater user.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    
    /**
     * Get the salesperson.
     */
    public function salesperson()
    {
        return $this->belongsTo(User::class, 'salesperson');
    }
} 