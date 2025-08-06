<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'expense_id',
        'title',
        'description',
        'amount',
        'date',
        'category',
        'payment_method',
        'reference',
        'attachment',
        'created_by',
    ];

    public static $payment_methods = [
        'cash' => 'Cash',
        'mpesa' => 'Mpesa',
        'bank' => 'Bank',
        'credit' => 'Credit',
    ];

    public function attachments()
    {
        return $this->hasMany(ExpenseAttachment::class);
    }
    

}
