<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadGroupReply extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'radgroupreply';
    
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
        'groupname',
        'attribute',
        'op',
        'value',
        'created_by'
    ];
    
    /**
     * Get the group.
     */
    public function group()
    {
        return $this->belongsTo(RadiusGroup::class, 'groupname', 'name');
    }
    
    /**
     * Get the creator.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
} 