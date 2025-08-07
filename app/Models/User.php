<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['phone_number', 'credit_points', 'net_points'];
    protected $hidden = ['remember_token'];
    protected $casts = ['credit_points' => 'float', 'net_points' => 'integer'];

    /**
     * Get all of the subscriptions for the User.
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get only the active subscriptions for the User.
     */
    public function activeSubscriptions()
    {
        return $this->hasMany(Subscription::class)->where('expires_at', '>', now());
    }
}