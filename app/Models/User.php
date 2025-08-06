<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Lab404\Impersonate\Models\Impersonate;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles ,Impersonate;

    protected $appends = ['profile'];

    protected $fillable = [
        'name',
        'company_id',
        'email',
        'owner',
        'email_verified_at',
        'password',
        'plan',
        'extra_customers',
        'plan_expire_date',
        'requested_plan',
        'trial_plan',
        'trial_expire_date',
        'type',
        'phone_number',
        'location',
        'avatar',
        'active_status',
        'delete_status',
        'mode',
        'dark_mode',
        'is_disable',
        'is_enable_login',
        'is_active',
        'referral_code',
        'used_referral_code',
        'commission_amount',
        'last_login_at',
        'created_by',
        'is_email_verified',
        'pppoe_pay',
        'hotspot_pay',
        'is_system_api_enable',
        'payment_settings',
        'has_payment_settings',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'plan' => 'integer',
        'extra_customers' => 'integer',
        'plan_expire_date' => 'date',
        'requested_plan' => 'integer',
        'trial_plan' => 'integer',
        'trial_expire_date' => 'date',
        'active_status' => 'boolean',
        'delete_status' => 'integer',
        'dark_mode' => 'boolean',
        'is_disable' => 'integer',
        'is_enable_login' => 'integer',
        'is_active' => 'integer',
        'referral_code' => 'integer',
        'used_referral_code' => 'integer',
        'commission_amount' => 'integer',
        'last_login_at' => 'datetime',
        'created_by' => 'integer',
        'is_email_verified' => 'boolean',
        'is_system_api_enable' => 'integer',
        'payment_settings' => 'json',
        'has_payment_settings'=> 'integer',
    ];

    public $settings;

    public function getProfileAttribute()
    {

        if (!empty($this->avatar) && \Storage::exists($this->avatar)) {
            return $this->attributes['avatar'] = asset(\Storage::url($this->avatar));
        } else {
            return $this->attributes['avatar'] = asset(\Storage::url('avatar.png'));
        }
    }

    public function authId()
    {
        return $this->id;
    }

    public function creatorId()
    {
        if ($this->type == 'company' || $this->type == 'super admin') {
            return $this->id;
        } else {
            return $this->created_by;
        }
    }

    public function ownerId()
    {
        if ($this->type == 'company' || $this->type == 'super admin') {
            return $this->id;
        } else {
            return $this->created_by;
        }
    }

    public function ownerDetails()
    {

        if ($this->type == 'company' || $this->type == 'super admin') {
            return User::where('id', $this->id)->first();
        } else {
            return User::where('id', $this->created_by)->first();
        }
    }

    public function currentLanguage()
    {
        return $this->lang;
    }

    public function priceFormat($price)
    {
        $number = explode('.', $price);
        $length = strlen(trim($number[0]));
        $float_number = Utility::getValByName('float_number') == 'dot' ? '.' : ',';

        if ($length > 3) {
            $decimal_separator = Utility::getValByName('decimal_separator') == 'dot' ? ',' : ',';
            $thousand_separator = Utility::getValByName('thousand_separator') == 'dot' ? '.' : ',';
        } else {
            $decimal_separator = Utility::getValByName('decimal_separator') == 'dot' ? '.' : ',';
            $thousand_separator = Utility::getValByName('thousand_separator') == 'dot' ? '.' : ',';
        }

        $currency = Utility::getValByName('currency_symbol') == 'withcurrencysymbol' ? Utility::getValByName('site_currency_symbol') : Utility::getValByName('site_currency');
        $settings = Utility::settings();
        // dd($currency,$settings['site_currency']);
        $decimal_number = Utility::getValByName('decimal_number') ? Utility::getValByName('decimal_number') : 0;
        $currency_space = Utility::getValByName('currency_space');
        $price = number_format($price, $decimal_number, $decimal_separator, $thousand_separator);

        if ($float_number == 'dot') {
            $price = preg_replace('/' . preg_quote($thousand_separator, '/') . '([^' . preg_quote($thousand_separator, '/') . ']*)$/', $float_number . '$1', $price);
        } else {
            $price = preg_replace('/' . preg_quote($decimal_separator, '/') . '([^' . preg_quote($decimal_separator, '/') . ']*)$/', $float_number . '$1', $price);
        }

        return (($settings['site_currency_symbol_position'] == "pre") ? $currency : '') . ($currency_space == 'withspace' ? ' ' : '') . $price . ($currency_space == 'withspace' ? ' ' : '') . (($settings['site_currency_symbol_position'] == "post") ? $currency : '');
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
    public function DateTimeFormat($date)
    {
        $settings = Utility::settings();

        $date_formate = !empty($settings['site_date_format']) ? $settings['site_date_format'] : 'd-m-y';
        $time_formate = !empty($settings['site_time_format']) ? $settings['site_time_format'] : 'H:i';

        return date($date_formate . ' ' . $time_formate, strtotime($date));
    }

    public function invoiceNumberFormat($number)
    {
        $settings = Utility::settings();

        return $settings["invoice_prefix"] . sprintf("%05d", $number);
    }

    public function getPlan()
    {
        return $this->hasOne('App\Models\Plan', 'id', 'plan');
    }

    public function assignPlan($planID, $company_id = 0)
    {
        $plan = Plan::find($planID);
        if ($plan) {
            $this->plan = $plan->id;

            $duration = strtolower($plan->duration);
            if ($duration == 'month') {
                $this->plan_expire_date = Carbon::now()->addMonths(1)->isoFormat('YYYY-MM-DD');
            } elseif ($duration == 'year') {
                $this->plan_expire_date = Carbon::now()->addYears(1)->isoFormat('YYYY-MM-DD');
            } else {
                $this->plan_expire_date = null;
            }
            $this->save();

            if ($company_id != 0) {
                $user_id = $company_id;
            } else {
                $user_id = \Auth::user()->creatorId();
            }

            $customers = Customer::where('created_by', '=', $user_id)->get();

            if ($plan->max_customers == -1) {
                foreach ($customers as $customer) {
                    $customer->is_active = 1;
                    $customer->save();
                }
            } else {
                $customerCount = 0;
                foreach ($customers as $customer) {
                    $customerCount++;
                    if ($customerCount <= $plan->max_customers) {
                        $customer->is_active = 1;
                        $customer->save();
                    } else {
                        $customer->is_active = 0;
                        $customer->save();
                    }
                }
            }

            return ['is_success' => true];
        } else {
            return [
                'is_success' => false,
                'error' => 'Plan is deleted.',
            ];
        }
    }

    public function customerNumberFormat($number)
    {
        $settings = Utility::settings();

        return $settings["customer_prefix"] . sprintf("%05d", $number);
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function countUsers()
    {
        return User::where('type', '!=', 'super admin')->where('type', '!=', 'company')->where('type', '!=', 'client')->where('created_by', '=', $this->creatorId())->count();
    }

    public function countCompany()
    {
        return User::where('type', '=', 'company')->where('created_by', '=', $this->creatorId())->count();
    }

    public function countOrder()
    {
        return Order::count();
    }

    public function countplan()
    {
        return Plan::count();
    }

    public function countPaidCompany()
    {
        return User::where('type', '=', 'company')->whereNotIn(
            'plan', [
                0,
                1,
            ]
        )->where('created_by', '=', \Auth::user()->id)->count();
    }

    public function countCustomers()
    {
        return Customer::where('created_by', '=', $this->creatorId())->count();
    }

    public function countInvoices()
    {
        return Invoice::where('created_by', '=', $this->creatorId())->count();
    }

    public function todayIncome()
    {
        $revenue = Revenue::where('created_by', '=', $this->creatorId())->whereRaw('Date(date) = CURDATE()')->where('created_by', \Auth::user()->creatorId())->sum('amount');
        $invoiceTotal = self::getInvoiceProductsData((date('y-m-d')));

        $totalIncome = (!empty($revenue) ? $revenue : 0) + (!empty($invoiceTotal) ? ($invoiceTotal) : 0);

        return $totalIncome;
    }

    public function incomeCurrentMonth()
    {
        $currentMonth = date('m');
        $revenue = Revenue::where('created_by', '=', $this->creatorId())->whereRaw('MONTH(date) = ?', [$currentMonth])->sum('amount');
        $invoiceTotal = self::getInvoiceProductsData('', $currentMonth);

        $totalIncome = (!empty($revenue) ? $revenue : 0) + (!empty($invoiceTotal) ? ($invoiceTotal) : 0);

        return $totalIncome;

    }
    public function incomecat()
    {

        $currentMonth = date('m');
        $revenue = Revenue::where('created_by', '=', $this->creatorId())->whereRaw('MONTH(date) = ?', [$currentMonth])->sum('amount');

        $incomes = Revenue::selectRaw('sum(revenues.amount) as amount,MONTH(date) as month,YEAR(date) as year,category_id')->leftjoin('product_service_categories', 'revenues.category_id', '=', 'product_service_categories.id')->where('product_service_categories.type', '=', 1);

        $invoices = Invoice::select('*')->where('created_by', \Auth::user()->creatorId())->whereRAW('MONTH(send_date) = ?', [$currentMonth])->get();

        $invoiceArray = array();
        foreach ($invoices as $invoice) {
            $invoiceArray[] = $invoice->getTotal();
        }
        $totalIncome = (!empty($revenue) ? $revenue : 0) + (!empty($invoiceArray) ? array_sum($invoiceArray) : 0);

        return $totalIncome;
    }


    public function totalCompanyUser($id)
    {
        return User::where('created_by', '=', $id)->count();
    }

    public function totalCompanyCustomer($id)
    {
        return Customer::where('created_by', '=', $id)->count();
    }


    public function planPrice()
    {
        $user = \Auth::user();
        if ($user->type == 'super admin') {
            $userId = $user->id;
        } else {
            $userId = $user->created_by;
        }

        return DB::table('settings')->where('created_by', '=', $userId)->get()->pluck('value', 'name');

    }

    public function currentPlan()
    {
        return $this->hasOne('App\Models\Plan', 'id', 'plan');
    }

    public function invoicesData($start, $current)
    {
        $InvoiceProducts = Invoice::select('invoices.invoice_id as invoice')
            ->selectRaw('sum((invoice_packages.price * invoice_packages.quantity) - invoice_packages.discount) as price')
            ->selectRaw('(SELECT SUM(credit_notes.amount) FROM credit_notes WHERE credit_notes.invoice = invoices.id) as credit_price')
            ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) FROM invoice_packages
             LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoice_packages.tax) > 0
             WHERE invoice_packages.invoice_id = invoices.id) as total_tax')
            ->leftJoin('invoice_packages', 'invoice_packages.invoice_id', 'invoices.id')
            ->where('issue_date', '>=', $start)->where('issue_date', '<=', $current)
            ->where('invoices.created_by', \Auth::user()->creatorId())
            ->groupBy('invoice')
            ->get()
            ->keyBy('invoice')
            ->toArray();


        $invoicepayment = Invoice::select('invoices.invoice_id as invoice')
            ->selectRaw('sum((invoice_payments.amount)) as pay_price')
            ->leftJoin('invoice_payments', 'invoice_payments.invoice_id', 'invoices.id')
            ->where('issue_date', '>=', $start)->where('issue_date', '<=', $current)
            ->where('invoices.created_by', \Auth::user()->creatorId())
            ->groupBy('invoice')
            ->get()
            ->keyBy('invoice')
            ->toArray();

        $mergedArray = [];

        foreach ($InvoiceProducts as $key => $value) {
            if (isset($invoicepayment[$key])) {
                $mergedArray[$key] = array_merge($value, $invoicepayment[$key]);
            }
        }

        $invoiceTotal = 0;
        $invoicePaid = 0;
        $invoiceDue = 0;
        $invoiceData = [];

        foreach ($mergedArray as $invoice) {
            $invoiceTotal += $invoice['price'] + $invoice['total_tax'];
            $invoicePaid += $invoice['pay_price'] + $invoice['credit_price'];
            $invoiceDue += ($invoice['price'] + $invoice['total_tax']) - $invoice['credit_price'] - $invoice['pay_price'];
        }

        $invoiceData = [
            "invoiceTotal" => $invoiceTotal,
            "invoicePaid" => $invoicePaid,
            'invoiceDue' => $invoiceDue,
        ];

        return $invoiceData;
    }

    public function expenseData($start, $current)
    {
        $billProducts = Bill::select('bills.bill_id as bill')
            ->selectRaw('sum((bill_products.price * bill_products.quantity) - bill_products.discount) as price')
            ->selectRaw('(SELECT SUM(debit_notes.amount) FROM debit_notes
             WHERE debit_notes.bill = bills.id) as debit_price')
            ->selectRaw('(SELECT SUM(bill_accounts.price) FROM bill_accounts
             WHERE bill_accounts.ref_id = bills.id) as acc_price')
            ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) FROM bill_products
             LEFT JOIN taxes ON FIND_IN_SET(taxes.id, bill_products.tax) > 0
             WHERE bill_products.bill_id = bills.id) as total_tax')
            ->leftJoin('bill_products', 'bill_products.bill_id', 'bills.id')
            ->where('bill_date', '>=', $start)->where('bill_date', '<=', $current)
            ->where('bills.created_by', \Auth::user()->creatorId())
            ->where('bills.type', 'Expense')
            ->groupBy('bill')
            ->get()
            ->keyBy('bill')
            ->toArray();


        $billPayment = Bill::select('bills.bill_id as bill')
            ->selectRaw('sum((bill_payments.amount)) as pay_price')
            ->leftJoin('bill_payments', 'bill_payments.bill_id', 'bills.id')
            ->where('bill_date', '>=', $start)->where('bill_date', '<=', $current)
            ->where('bills.created_by', \Auth::user()->creatorId())
            ->where('bills.type', 'Expense')
            ->groupBy('bill')
            ->get()
            ->keyBy('bill')
            ->toArray();

        $mergedArray = [];

        foreach ($billProducts as $key => $value) {
            if (isset($billPayment[$key])) {
                $mergedArray[$key] = array_merge($value, $billPayment[$key]);
            } else {
                $mergedArray[$key] = ($value);
            }
        }

        $billTotal = 0;
        $billPaid = 0;
        $billDue = 0;
        $billData = [];

        foreach ($mergedArray as $bill) {
            $billTotal += $bill['price'] + $bill['total_tax'] + $bill['acc_price'];
            $billPaid += $bill['pay_price'] + $bill['debit_price'];
            $billDue += ($bill['price'] + $bill['total_tax'] + $bill['acc_price']) - $bill['pay_price'] - $bill['debit_price'];
        }

        $billData = [
            "billTotal" => $billTotal,
            "billPaid" => $billPaid,
            'billDue' => $billDue,
        ];

        return $billData;
    }
    public function weeklyInvoice()
    {
        $staticstart = date('Y-m-d', strtotime('last Week'));
        $currentDate = date('Y-m-d');
        $invoices = self::invoicesData($staticstart, $currentDate);

        $invoiceDetail['invoiceTotal'] = $invoices['invoiceTotal'];
        $invoiceDetail['invoicePaid'] = $invoices['invoicePaid'];
        $invoiceDetail['invoiceDue'] = $invoices['invoiceDue'];

        return $invoiceDetail;
    }

    public function monthlyInvoice()
    {
        $staticstart = date('Y-m-d', strtotime('last Month'));
        $currentDate = date('Y-m-d');
        $invoices = self::invoicesData($staticstart, $currentDate);

        $invoiceDetail['invoiceTotal'] = $invoices['invoiceTotal'];
        $invoiceDetail['invoicePaid'] = $invoices['invoicePaid'];
        $invoiceDetail['invoiceDue'] = $invoices['invoiceDue'];

        return $invoiceDetail;
    }

    public function weeklyBill()
    {
        $staticstart = date('Y-m-d', strtotime('last Week'));
        $currentDate = date('Y-m-d');
        $bills = self::billsData($staticstart, $currentDate);
        $expense = self::expenseData($staticstart, $currentDate);

        $billDetail['billTotal'] = $bills['billTotal'] + $expense['billTotal'];
        $billDetail['billPaid'] = $bills['billPaid'] + $expense['billPaid'];
        $billDetail['billDue'] = $bills['billDue'] + $expense['billDue'];
        return $billDetail;
    }

    public function monthlyBill()
    {
        $staticstart = date('Y-m-d', strtotime('last Month'));
        $currentDate = date('Y-m-d');
        $bills = self::billsData($staticstart, $currentDate);
        $expense = self::expenseData($staticstart, $currentDate);

        $billDetail['billTotal'] = $bills['billTotal'] + $expense['billTotal'];
        $billDetail['billPaid'] = $bills['billPaid'] + $expense['billPaid'];
        $billDetail['billDue'] = $bills['billDue'] + $expense['billDue'];
        return $billDetail;
    }

    public function deals()
    {
        return $this->belongsToMany('App\Models\Deal', 'user_deals', 'user_id', 'deal_id');
    }

    public function leads()
    {
        return $this->belongsToMany('App\Models\Lead', 'user_leads', 'user_id', 'lead_id');
    }
    // Make new attribute for directly get image
    public function getImgImageAttribute()
    {
        $userDetail = Employee::where('user_id', $this->id)->first();
        if (!empty($userDetail)) {
            if (!empty($userDetail->avatar)) {
                return asset(\Storage::url($userDetail->avatar));
            } else {
                return asset(\Storage::url('avatar.png'));
            }
        } else {
            return asset(\Storage::url('avatar.png'));
        }
    }

    // Get User's Contact
    public function contacts()
    {
        return $this->hasMany('App\Models\UserContact', 'parent_id', 'id');
    }

    public function total_lead()
    {
        if (\Auth::user()->type == 'company') {
            return Lead::where('created_by', '=', $this->creatorId())->count();
        } elseif (\Auth::user()->type == 'client') {
            return Lead::where('client', '=', $this->authId())->count();
        } else {
            return Lead::where('owner', '=', $this->authId())->count();
        }
    }


    public function show_dashboard()
    {
        $user_type = \Auth::user()->type;

        if ($user_type == 'company' || $user_type == 'super admin') {
            $user = Auth::user();
        } else {
            $user = User::where('id', \Auth::user()->created_by)->first();
        }

        return $user->plan;
        // return !empty($user->plan)?Plan::find($user->plan)->crm:'';
    }


    public static function show_account()
    {
        $user_type = \Auth::user()->type;
        if ($user_type == 'company' || $user_type == 'super admin') {
            $user = User::where('id', \Auth::user()->id)->first();
        } else {
            $user = User::where('id', \Auth::user()->created_by)->first();
        }

        return !empty($user->plan) ? Plan::find($user->plan)->account : '';
    }


    public function isUser()
    {

        return $this->type === 'user' ? 1 : 0;
    }

    public function isClient()
    {
        return $this->type == 'client' ? 1 : 0;
    }



    public function extraKeyword()
    {
        $keyArr = [
            __('Sun'),
            __('Mon'),
            __('Tue'),
            __('Wed'),
            __('Thu'),
            __('Fri'),
            __('Last 7 Days'),
            __('In Progress'),
            __('Complete'),
            __('Canceled'),
            __('Lead User Name'),
            __('Old Stage Name'),
            __('New Stage Name'),
            __('Support User Name'),
            __('Company Policy Name'),
            __('Invoice Issue Date'),
            __('Invoice Due Date'),
            __('Budget Name'),
            __('Budget Year'),
            __('Revenue Amount'),
            __('Revenue Date'),
            __('Payment Price'),
            __('New User'),
            __('Lifetime'),
            __('Coupon'),
            __('Cashflow'),

        ];
    }

    public function barcodeFormat()
    {
        $settings = Utility::settings();
        return isset($settings['barcode_format']) ? $settings['barcode_format'] : 'code128';
    }

    public function barcodeType()
    {
        $settings = Utility::settings();
        return isset($settings['barcode_type']) ? $settings['barcode_type'] : 'css';
    }

    //user log details
    public static function userCurrentLocation()
    {
        $company_id = Auth::User()->Company_ID();
        // dd($company_id);
        if (Auth::user()->user_type == 'company') {
            $location = location::where(['id' => Auth::User()->current_location, 'company_id' => $company_id, 'is_active' => 1])->first();

            if (!is_null($location)) {
                return $location->id;
            } else {
                return 0;
            }

        } elseif (Auth::user()->user_type != 'company' && Auth::user()->user_type != 'super admin') {

            if (Auth::user()->current_location == 0) {
                Auth::user()->current_location = Auth::user()->location_id;
            }

            $location = location::where('id', Auth::user()->current_location)->where('company_id', $company_id)->first();
            return $location->id;
        }
    }
    public function userDefaultDataRegister($user_id)
    {
        try{
            DB::table('smsalerts')->insert([
                ['created_by' => $user_id, 'is_system' => 1, 'type' => 'Admin_Login', 'status' => 1, 'template' => 'Dear Admin {username}, a new login attempt into your account at {datetime} on {company}.'],
                ['created_by' => $user_id, 'is_system' => 1, 'type' => 'User_Login', 'status' => 1, 'template' => 'Dear User {username}, a new login attempt into your account at {datetime} on {company}.'],
                ['created_by' => $user_id, 'is_system' => 1, 'type' => 'Service-Interruption', 'status' => 1, 'template' => 'Dear {fullname}, We have fiber cut affecting section of our network. Our team is working to resolve this as soon as possible. Thank you for your patience. {company} | Support: {support}.'],
                ['created_by' => $user_id, 'is_system' => 1, 'type' => 'New_User', 'status' => 1, 'template' => 'Welcome {fullname}, Thank you for choosing {company}. Your Account number is: {account}. Payment Mode: Paybill:  Account: {contact} Amount: Ksh {amount}'],
                ['created_by' => $user_id, 'is_system' => 1, 'type' => 'Deposit-Balance', 'status' => 1, 'template' => 'Dear User {username}, {currency}{amount} has been deposited into your account.'],
                ['created_by' => $user_id, 'is_system' => 1, 'type' => 'User_Enable/Disable', 'status' => 1, 'template' => 'Dear User {username}, your account status has been changed to {status}.'],
                ['created_by' => $user_id, 'is_system' => 1, 'type' => 'User_Expired', 'status' => 1, 'template' => 'Dear {fullname}, your internet connection has been disconnected at {expiry}. Please, pay your subion fee for reconnection; Paybill: Account: {contact} Ignore if already paid.'],
                ['created_by' => $user_id, 'is_system' => 1, 'type' => 'User_Activated', 'status' => 1, 'template' => 'Dear {fullname}, your internet connection has been renewed and is valid till {expiry}.'],
                ['created_by' => $user_id, 'is_system' => 1, 'type' => 'User_Notice', 'status' => 1, 'template' => 'Dear {fullname}, your internet connection will be terminated on {expiry}. Please, pay your subion fee before disconnection. Paybill: Account: {contact} Ignore if already paid.'],
                ['created_by' => $user_id, 'is_system' => 1, 'type' => 'Login_OTP', 'status' => 1, 'template' => '{otp} is your OTP Code For Login From {company}'],
                ['created_by' => $user_id, 'is_system' => 1, 'type' => 'Register_OTP', 'status' => 1, 'template' => '{otp} is your OTP Code For Registration From {company}'],
                ['created_by' => $user_id, 'is_system' => 1, 'type' => 'Password_Reset_OTP', 'status' => 1, 'template' => '{otp} is your OTP Code For Password Reset From {company}'],
                ['created_by' => $user_id, 'is_system' => 1, 'type' => 'Mobile_Number_Reset_OTP', 'status' => 1, 'template' => '{otp} is your OTP Code For Mobile Number Reset From {company}'],
                ['created_by' => $user_id, 'is_system' => 1, 'type' => 'Ticket_SMS_Notification', 'status' => 1, 'template' => 'A New Ticket Has Been Created {title} {company}'],
                ['created_by' => $user_id, 'is_system' => 1, 'type' => 'Notice_SMS_Notification', 'status' => 1, 'template' => 'A New Notice Has Been Created {title} {company}'],
                ['created_by' => $user_id, 'is_system' => 1, 'type' => 'Mpesa-Payment', 'status' => 1, 'template' => 'Dear {fullname}, Ksh {amount} has been deposited into your account.'],
            ]);
            return true;
        } catch (\Exception $e) {
            \Log::error('Error in userDefaultDataRegister: ' . $e->getMessage());
            throw $e;
        }
    }
}
