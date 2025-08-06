<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


    class Payment extends Model
    {
        protected $fillable = [
            'created_by', 'customer_id', 'payment_reference',
            'payment_method', 'amount', 'paid_at', 'status', 'notes',
        ];

        public function customer()
        {
            return $this->belongsTo(Customer::class);
        }

        public function createdBy()
        {
            return $this->belongsTo(User::class, 'created_by');
        }

        public function invoices()
        {
            return $this->belongsToMany(Invoice::class, 'invoice_payment')
                ->withPivot('amount_applied')
                ->withTimestamps();
        }
    }
