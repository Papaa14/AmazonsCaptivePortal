<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'created_by', 'tax_id', 'customer_id', 'invoice_number', 'status',
        'total_amount', 'amount_paid', 'issue_date', 'due_date',
        'paid_at', 'notes',
    ];

    public static $statues = [
        'Draft',
        'Sent',
        'Unpaid',
        'Partialy Paid',
        'Paid',
    ];


    public function tax()
    {
        return $this->hasOne('App\Models\Tax', 'id', 'tax_id');
    }

    public function packages()
    {
        return $this->hasMany('App\Models\InvoicePackage', 'invoice_id', 'id');
    }

    public function payments()
    {
        return $this->hasMany('App\Models\InvoicePayment', 'invoice_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo('App\Models\Customer', 'customer_id');
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    public function transactions()
    {
        return $this->hasMany('App\Models\Transaction', 'invoice_id', 'id');
    }
    private static $overallTotal = null;
    public static function getOverallTotal()
    {
        if (self::$overallTotal === null) {
            $invoice = new self();
            self::$overallTotal = $invoice->invoiceTotal();
        }
        return self::$overallTotal;
    }

    // public function getTotal()
    // {
    //     return ($this->getSubTotal() - $this->getTotalDiscount()) + $this->getTotalTax();
    // }
    public function getTotal()
    {
        return $this->getSubTotal();
    }

    public function getSubTotal()
    {
        $subTotal = 0;
        foreach ($this->packages as $package) {
            $subTotal += ($package->unit_price * $package->quantity);
        }
        return $subTotal;
    }

    public function getTotalTax()
    {
        $taxData = Utility::getTaxData();
        $totalTax = 0;
        foreach ($this->packages as $package) {
            $taxArr = explode(',', $package->tax);
            $taxes = 0;
            foreach ($taxArr as $tax) {
                $taxes += !empty($taxData[$tax]['rate']) ? $taxData[$tax]['rate'] : 0;
            }
            $totalTax += ($taxes / 100) * ($package->price * $package->quantity);
        }
        return $totalTax;
    }

    public function getTotalDiscount()
    {
        $totalDiscount = 0;
        foreach ($this->packages as $package) {
            $totalDiscount += $package->discount;
        }
        return $totalDiscount;
    }

    public function getDue()
    {
        $due = 0;
        foreach ($this->payments as $payment) {
            $due += $payment->amount;
        }
        return $this->getTotal() - $due;
    }

    public static function changeStatus($invoice_id, $status)
    {
        $invoice = self::find($invoice_id);
        $invoice->status = $status;
        $invoice->update();
    }
}
