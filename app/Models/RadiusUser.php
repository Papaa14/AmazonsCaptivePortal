<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadiusUser extends Model
{
    protected $fillable = ['customer_id', 'nas_id', 'username', 'password'];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function nas()
    {
        return $this->belongsTo(Nas::class, 'nas_id');
    }
}
