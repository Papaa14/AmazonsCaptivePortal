<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = ['user_id', 'hotspot_package_id', 'activated_at', 'expires_at','voucher_code', 'usage_bytes'];
    protected $casts = ['activated_at' => 'datetime', 'expires_at' => 'datetime'];

    /**
     * Get the user that owns the subscription.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the package associated with the subscription.
     */
    public function hotspotPackage()
    {
        return $this->belongsTo(HotspotPackage::class);
    }
     /**
     * Get all of the active device sessions for the subscription.
     */
    public function activeSessions(): HasMany
    {
        return $this->hasMany(ActiveSession::class);
    }
}