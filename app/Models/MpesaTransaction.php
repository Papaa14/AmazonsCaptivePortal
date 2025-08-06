<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MpesaTransaction extends Model
{
    protected $table = 'mpesa_transactions';

    protected $fillable = [
        'created_by',
        'TransID',
        'TransactionType',
        'TransTime',
        'TransAmount',
        'BusinessShortCode',
        'BillRefNumber',
        'OrgAccountBalance',
        'MSISDN',
        'FirstName',
        'status',
        'site',
    ];
}
