<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NasReload extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'nasreload';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nasname',
        'admin',
        'trigger_time',
        'completed',
        'completed_time',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'trigger_time' => 'datetime',
        'completed' => 'integer',
        'completed_time' => 'datetime',
    ];
    
    /**
     * Get the NAS device associated with this reload request.
     */
    public function nas()
    {
        return $this->belongsTo(Nas::class, 'nasname', 'nasname');
    }
} 