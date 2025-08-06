<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoicePackage extends Model
{
    protected $fillable = [
        'invoice_id', 'type', 'description', 'quantity',
        'unit_price', 'total', 'package_id'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }
}
