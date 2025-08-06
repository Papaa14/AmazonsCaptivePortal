<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;

class Customer extends Authenticatable
{
    use HasRoles;
    use Notifiable;

    protected $guard_name = 'web';

    protected $fillable = [
        'customer_id',
        'fullname',
        'username',
        'account',
        'extension_start',
        'extension_expiry',
        'is_extended',
        'email',
        'contact',
        'corporate',
        'avatar',
        'created_by',
        'is_active',
        'email_verified_at',
        'password',
        'service',
        'auto_renewal',
        'mac_address',
        'maclock',
        'site',
        'package_id',
        'charges',
        'package',
        'apartment',
        'location',
        'housenumber',
        'expiry',
        'status',
        'lang',
        'balance',
        'is_suspended',
        'suspended_at',
        'connectionstatus',
        'parent_id',
        'inherit_expiry',
        'override_download',
        'override_download_unit',
        'override_upload',
        'override_upload_unit',
        'is_override',
        'last_seen',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
    // Mutator to convert '07' and '01' numbers to '+254' format
    public function setContactAttribute($value)
    {
        if (preg_match('/^(07|01)(\d{8})$/', $value, $matches)) {
            $value = '254' . $matches[1] . $matches[2]; // Convert to 2547XXXXXXXX or 2541XXXXXXXX
        }
        $this->attributes['contact'] = $value;
    }

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_seen' => 'datetime',
        'balance' => 'float',
        'is_active' => 'boolean',
        'extension_start'  => 'datetime',
        'extension_expiry'  => 'datetime',
        'is_extended'  => 'boolean',
        'expiry'  => 'datetime',
        'auto_renewal' => 'boolean',
        'created_by' => 'integer',
        'is_suspended' => 'boolean',
        'suspended_at' => 'datetime',
        'corporate' => 'integer',
        'connectionstatus' => 'integer',
        'maclock' => 'integer',
    ];

    public $settings;


    public function authId()
    {
        return $this->id;
    }

    public function creatorId()
    {
        if ($this->created_by) {
            return $this->created_by;
        }
        return 1;
    }
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function currentLanguage()
    {
        return $this->lang;
    }

    public function priceFormat($price)
    {
        $settings = Utility::settings();

        return (($settings['site_currency_symbol_position'] == "pre") ? $settings['site_currency_symbol'] : '') . number_format($price, Utility::getValByName('decimal_number')) . (($settings['site_currency_symbol_position'] == "post") ? $settings['site_currency_symbol'] : '');
    }

    public function currencySymbol()
    {
        $settings = Utility::settings();

        return $settings['site_currency_symbol'];
    }

    public function dateFormat($date)
    {
        $settings = Utility::settings();

        return date($settings['site_date_format'], strtotime($date));
    }

    public function timeFormat($time)
    {
        $settings = Utility::settings();

        return date($settings['site_time_format'], strtotime($time));
    }

    public function invoiceNumberFormat($number)
    {
        $settings = Utility::settings();

        return $settings["invoice_prefix"] . sprintf("%05d", $number);
    }

    public function invoiceChartData()
    {
        $month[]       = __('January');
        $month[]       = __('February');
        $month[]       = __('March');
        $month[]       = __('April');
        $month[]       = __('May');
        $month[]       = __('June');
        $month[]       = __('July');
        $month[]       = __('August');
        $month[]       = __('September');
        $month[]       = __('October');
        $month[]       = __('November');
        $month[]       = __('December');
        $data['month'] = $month;

        $data['currentYear'] = date('M-Y');

        $totalInvoice = Invoice::where('customer_id', \Auth::user()->id)->count();
        $unpaidArr    = array();


        for($i = 1; $i <= 12; $i++)
        {
            $unpaidInvoice  = Invoice:: where('customer_id', \Auth::user()->id)->whereRaw('year(`send_date`) = ?', array(date('Y')))->whereRaw('month(`send_date`) = ?', $i)->where('status', '1')->where('due_date', '>', date('Y-m-d'))->get();
            $paidInvoice    = Invoice:: where('customer_id', \Auth::user()->id)->whereRaw('year(`send_date`) = ?', array(date('Y')))->whereRaw('month(`send_date`) = ?', $i)->where('status', '4')->get();
            $partialInvoice = Invoice:: where('customer_id', \Auth::user()->id)->whereRaw('year(`send_date`) = ?', array(date('Y')))->whereRaw('month(`send_date`) = ?', $i)->where('status', '3')->get();
            $dueInvoice     = Invoice:: where('customer_id', \Auth::user()->id)->whereRaw('year(`send_date`) = ?', array(date('Y')))->whereRaw('month(`send_date`) = ?', $i)->where('status', '1')->where('due_date', '<', date('Y-m-d'))->get();


            $totalUnpaid = 0;
            for($j = 0; $j < count($unpaidInvoice); $j++)
            {
                $unpaidAmount = $unpaidInvoice[$j]->getDue();
                $totalUnpaid  += $unpaidAmount;

            }

            $totalPaid = 0;
            for($j = 0; $j < count($paidInvoice); $j++)
            {
                $paidAmount = $paidInvoice[$j]->getTotal();
                $totalPaid  += $paidAmount;

            }

            $totalPartial = 0;
            for($j = 0; $j < count($partialInvoice); $j++)
            {
                $partialAmount = $partialInvoice[$j]->getDue();
                $totalPartial  += $partialAmount;

            }

            $totalDue = 0;
            for($j = 0; $j < count($dueInvoice); $j++)
            {
                $dueAmount = $dueInvoice[$j]->getDue();
                $totalDue  += $dueAmount;

            }

            $unpaidData[]  = $totalUnpaid;
            $paidData[]    = $totalPaid;
            $partialData[] = $totalPartial;
            $dueData[]     = $totalDue;

            $statusData['unpaid']  = $unpaidData;
            $statusData['paid']    = $paidData;
            $statusData['partial'] = $partialData;
            $statusData['due']     = $dueData;
        }

        $data['data'] = $statusData;


        $unpaidInvoice  = Invoice:: where('customer_id', \Auth::user()->id)->whereRaw('year(`send_date`) = ?', array(date('Y')))->where('status', '1')->where('due_date', '>', date('Y-m-d'))->get();
        $paidInvoice    = Invoice:: where('customer_id', \Auth::user()->id)->whereRaw('year(`send_date`) = ?', array(date('Y')))->where('status', '4')->get();
        $partialInvoice = Invoice:: where('customer_id', \Auth::user()->id)->whereRaw('year(`send_date`) = ?', array(date('Y')))->where('status', '3')->get();
        $dueInvoice     = Invoice:: where('customer_id', \Auth::user()->id)->whereRaw('year(`send_date`) = ?', array(date('Y')))->where('status', '1')->where('due_date', '<', date('Y-m-d'))->get();

        $progressData['totalInvoice']        = $totalInvoice = Invoice:: where('customer_id', \Auth::user()->id)->whereRaw('year(`send_date`) = ?', array(date('Y')))->count();
        $progressData['totalUnpaidInvoice']  = $totalUnpaidInvoice = count($unpaidInvoice);
        $progressData['totalPaidInvoice']    = $totalPaidInvoice = count($paidInvoice);
        $progressData['totalPartialInvoice'] = $totalPartialInvoice = count($partialInvoice);
        $progressData['totalDueInvoice']     = $totalDueInvoice = count($dueInvoice);

        $progressData['unpaidPr']  = ($totalInvoice != 0) ? ($totalUnpaidInvoice * 100) / $totalInvoice : 0;
        $progressData['paidPr']    = ($totalInvoice != 0) ? ($totalPaidInvoice * 100) / $totalInvoice : 0;
        $progressData['partialPr'] = ($totalInvoice != 0) ? ($totalPartialInvoice * 100) / $totalInvoice : 0;
        $progressData['duePr']     = ($totalInvoice != 0) ? ($totalDueInvoice * 100) / $totalInvoice : 0;

        $progressData['unpaidColor']  = '#fc544b';
        $progressData['paidColor']    = '#63ed7a';
        $progressData['partialColor'] = '#6777ef';
        $progressData['dueColor']     = '#ffa426';

        $data['progressData'] = $progressData;


        return $data;
    }


    public function customerInvoice($customerId)
    {
        $invoices  = Invoice:: where('customer_id', $customerId)->orderBy('issue_date', 'desc')->get();

        return $invoices;
    }

    public function customerOverdue($customerId)
    {
        $dueInvoices = Invoice:: where('customer_id', $customerId)->whereNotIn(
            'status', [
                        '0',
                        '4',
                    ]
        )->where('due_date', '<', date('Y-m-d'))->get();
        $due         = 0;
        foreach($dueInvoices as $invoice)
        {
            $due += $invoice->getDue();
        }

        return $due;
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }

    public function customerTotalInvoiceSum($customerId)
    {
        return $this->invoices()
            ->with('items')
            ->get()
            ->sum->getTotal();
    }

    // public function customerTotalInvoiceSum($customerId)
    // {
    //     $invoices = Invoice:: where('customer_id', $customerId)->get();
    //     $total    = 0;
    //     foreach($invoices as $invoice)
    //     {
    //         $total += $invoice->getTotal();
    //     }

    //     return $total;
    // }
    public function customerTotalInvoice($customerId)
    {
        $invoices = Invoice:: where('customer_id', $customerId)->count();

        return $invoices;
    }

    public static function customer_id($customer_name)
    {
        $customer = DB::table('customers')
        ->where('name', $customer_name)
        ->where('created_by', Auth::user()->creatorId())
        ->select('id')
        ->first();

        return ($customer != null) ? $customer->id : 0;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id', 'id');
    }

    public function radiusUser()
    {
        return $this->hasOne(RadiusUser::class, 'customer_id');
    }

    // Relationship to Package model
    public function subscribedPackage()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    // Parent-child relationship
    public function parent()
    {
        return $this->belongsTo(Customer::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Customer::class, 'parent_id');
    }

    // Check if customer is a child account
    public function isChild()
    {
        return !is_null($this->parent_id);
    }
}
