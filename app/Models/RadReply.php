<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadReply extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'radreply';
    
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
        'attribute',
        'op',
        'value',
        'created_by'
    ];
    
    /**
     * Get the user that owns this reply.
     */
    public function user()
    {
        return $this->belongsTo(Customer::class, 'username', 'username');
    }
    
    /**
     * Get the creator.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
} 