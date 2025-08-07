<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\HotspotPackage
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property float $price
 * @property int $duration_minutes
 * @property bool $is_unlimited
 * @property int|null $data_limit_mb
 * @property int|null $bonus_data_mb
 * @property int $device_limit
 * @property bool $is_free
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class HotspotPackage extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'hotspot_packages';

    /**
     * The attributes that are mass assignable.
     *
     * This array defines which columns in your database table can be filled
     * using methods like `HotspotPackage::create()` or `$package->update()`.
     * It's a security feature to protect against unintended updates.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_minutes',
        'is_unlimited',
        'data_limit_mb',
        'bonus_data_mb',
        'device_limit',
        'is_free',
        'is_active',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * This ensures that when you access these attributes on your model,
     * they are of the correct data type (e.g., boolean, integer, float),
     * which makes your code more reliable.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'duration_minutes' => 'integer',
        'is_unlimited' => 'boolean',
        'data_limit_mb' => 'integer',
        'bonus_data_mb' => 'integer',
        'device_limit' => 'integer',
        'is_free' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}