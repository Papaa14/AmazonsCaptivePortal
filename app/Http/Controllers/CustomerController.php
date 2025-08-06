<?php

namespace App\Http\Controllers;

use App\Exports\CustomerExport;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Customer;
use App\Models\CustomField;
use App\Models\MpesaTransaction;
use App\Models\Transaction;
use App\Models\Package;
use App\Models\Bandwidth;
use App\Models\Router;
use App\Models\Utility;
use Auth;
use Illuminate\Support\Str;
use App\Helpers\CustomHelper;
use App\Helpers\NetworkHelper;
use App\Models\User;
use App\Models\SmsAlert;
use App\Models\Plan;
use App\Models\Invoice;
use App\Jobs\RefreshCustomerRadiusRecordsJob;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Devrabiul\ToastMagic\Facades\ToastMagic;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        if (!\Auth::user()->can('show customer')) {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }

        $creatorId = \Auth::user()->creatorId();

        // Base query
        $baseQuery = DB::table('customers as c')
            ->leftJoin(
                DB::raw("(SELECT username COLLATE utf8mb4_unicode_ci AS username FROM radacct WHERE acctstoptime IS NULL GROUP BY username) AS radacct_online"),
                'c.username',
                '=',
                'radacct_online.username'
            )
            ->select('c.*', DB::raw('IF(radacct_online.username IS NULL, 0, 1) as is_online'))
            ->where('c.service', 'PPPoE')
            ->where('c.created_by', $creatorId);


            if ($request->filled('search')) {
            $search = $request->search;
            $baseQuery->where(function ($q) use ($search) {
                $q->where('c.fullname', 'like', "%{$search}%")
                ->orWhere('c.username', 'like', "%{$search}%")
                ->orWhere('c.contact', 'like', "%{$search}%");
            });
        }
        if ($request->filled('site')) {
            $baseQuery->where('site', $request->site);
        }
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $baseQuery->where('status', 'on');
            } elseif ($request->status === 'expired') {
                $baseQuery->where('status', 'off');
            } elseif ($request->status === 'disabled') {
                $baseQuery->where('is_active', 0);
            } elseif ($request->status === 'paused') {
                $baseQuery->where('is_suspended', 1);
            }
        }
        if ($request->filled('connection')) {
            if ($request->connection === 'online') {
                $baseQuery->having('is_online', 1);
            } elseif ($request->connection === 'offline') {
                $baseQuery->having('is_online', 0);
            }
        }

        if ($request->filled('package')) {
            $baseQuery->where('package_id', $request->package);
        }

        $customersC = $baseQuery->get();

        // $userId = auth()->user()->creatorId();

        $pppoeCustomers = (clone $baseQuery)
            ->where('c.service', 'PPPoE')
            ->whereNull('parent_id')
            ->get();
            // ->paginate(20, ['*'], 'pppoe_page');

        // $hotspotCustomers = (clone $baseQuery)
        //     ->where('c.service', 'Hotspot')
        //     ->whereNotNull('c.expiry')
        //     ->paginate(20, ['*'], 'hotspot_page');

        // Aggregates
        $actcustomers = $customersC->where('status', 'on');
        $suscustomers = $customersC->where('is_active', 0);
        $expcustomers = $customersC->where('status', 'off')->where('service', 'PPPoE');

        // Lists
        $sites = Router::where('created_by', $creatorId)->get();
        $packages = Package::where('created_by', $creatorId)->where('type', 'PPPoE')->get();

        $arrType = [
            'PPPoE' => __('PPPoE'),
        ];

        $arrPackage = Package::where('created_by', \Auth::user()->creatorId())
        ->where('type', 'PPPoE')
        ->get();

       $creatorId = \Auth::user()->creatorId();

        if ($creatorId == 9) {
            $customerN = self::generateNextAccountNumber('KN', 5, 99999);
        } elseif ($creatorId == 13) {
            $customerN = self::generateNextAccountNumber('LN', 6, 999999);
        } else {
            $customerN = self::generateNextAccountNumber('', 7, 999999);
        }

        $secret = strtoupper(Str::random(8));
        $email = strtolower($customerN) . '@isp.net';

        return view('customer.index', compact(
            'pppoeCustomers',
            'actcustomers',
            'suscustomers',
            'expcustomers',
            'customersC',
            'sites',
            'packages', 'customerN', 'arrType', 'arrPackage', 'secret', 'email'
        ));
    }

    public function hotspot(Request $request)
    {
        if (!\Auth::user()->can('show customer')) {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }

        $creatorId = \Auth::user()->creatorId();

        // Base query
        $baseQuery = DB::table('customers as c')
            ->leftJoin(
                DB::raw("(SELECT username COLLATE utf8mb4_unicode_ci AS username FROM radacct WHERE acctstoptime IS NULL GROUP BY username) AS radacct_online"),
                'c.username',
                '=',
                'radacct_online.username'
            )
            ->select('c.*', DB::raw('IF(radacct_online.username IS NULL, 0, 1) as is_online'))
            ->where('c.service', 'Hotspot')
            ->whereNotNull('c.expiry')
            ->where('c.created_by', $creatorId);


            if ($request->filled('search')) {
            $search = $request->search;
            $baseQuery->where(function ($q) use ($search) {
                $q->where('c.fullname', 'like', "%{$search}%")
                ->orWhere('c.username', 'like', "%{$search}%")
                ->orWhere('c.contact', 'like', "%{$search}%");
            });
        }
        if ($request->filled('site')) {
            $baseQuery->where('site', $request->site);
        }
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $baseQuery->where('status', 'on');
            } elseif ($request->status === 'expired') {
                $baseQuery->where('status', 'off');
            } elseif ($request->status === 'disabled') {
                $baseQuery->where('is_active', 0);
            } elseif ($request->status === 'paused') {
                $baseQuery->where('is_suspended', 1);
            }
        }
        if ($request->filled('connection')) {
            if ($request->connection === 'online') {
                $baseQuery->having('is_online', 1);
            } elseif ($request->connection === 'offline') {
                $baseQuery->having('is_online', 0);
            }
        }

        if ($request->filled('package')) {
            $baseQuery->where('package_id', $request->package);
        }

        $customersC = $baseQuery->get();

        $hotspotCustomers = (clone $baseQuery)
            ->where('c.service', 'Hotspot')
            ->whereNotNull('c.expiry')
            ->get();

        // Aggregates
        $actcustomers = $customersC->where('status', 'on');
        $suscustomers = $customersC->where('is_active', 0);
        $expcustomers = $customersC->where('status', 'off')->where('service', 'Hotspot');

        // Lists
        $sites = Router::where('created_by', $creatorId)->get();
        $packages = Package::where('created_by', $creatorId)->where('type', 'Hotspot')->get();

        $arrType = [
            'Hotspot' => __('Hotspot'),
        ];

        $arrPackage = Package::where('created_by', \Auth::user()->creatorId())
        ->where('type', 'Hotspot')
        ->get();
       $creatorId = \Auth::user()->creatorId();

        if ($creatorId == 9) {
            $customerN = self::generateNextAccountNumber('KN', 5, 99999);
        } elseif ($creatorId == 13) {
            $customerN = self::generateNextAccountNumber('LN', 6, 999999);
        } else {
            $customerN = self::generateNextAccountNumber('', 7, 999999);
        }

        $secret = strtoupper(Str::random(8));
        $email = strtolower($customerN) . '@isp.net';

        return view('customer.hotspot', compact(
            'hotspotCustomers',
            'actcustomers',
            'suscustomers',
            'expcustomers',
            'customersC',
            'sites',
            'packages', 'customerN', 'arrType', 'arrPackage', 'secret', 'email'
        ));
    }

    public function create()
    {
        if(\Auth::user()->can('create customer'))
        {

            $arrType = [
                'PPPoE' => __('PPPoE'),
            ];

            $arrPackage = Package::where('created_by', \Auth::user()->creatorId())
                ->where('type', 'PPPoE')
                ->pluck('name_plan', 'id')
                ->toArray();

            $creatorId = \Auth::user()->creatorId();

            if ($creatorId == 9) {
                $customerN = self::generateNextAccountNumber('KN', 5, 99999);
            } elseif ($creatorId == 13) {
                $customerN = self::generateNextAccountNumber('LN', 6, 999999);
            } else {
                $customerN = self::generateNextAccountNumber('', 7, 999999);
            }

            $secret = strtoupper(Str::random(8));
            $email = strtolower($customerN) . '@isp.net';
            return view('customer.create', compact( 'customerN', 'arrType', 'arrPackage', 'secret', 'email'));
        } else
        {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function generateNextAccountNumber($prefix, $lengthLimit, $maxValue, $excludeAccount = null)
    {
        $query = Customer::where('created_by', \Auth::user()->creatorId());

        if ($excludeAccount) {
            $query->where('account', '!=', $excludeAccount);
        }

        $query->where('account', 'LIKE', $prefix . '%')
            ->whereRaw('LENGTH(SUBSTRING(account, 3)) <= ?', [$lengthLimit])
            ->orderByRaw("LPAD(SUBSTRING(account, 3), {$lengthLimit}, '0') DESC");

        $latestAccount = $query->first();

        if (!$latestAccount) {
            $nextNumber = 1;
        } else {
            preg_match('/\d+$/', $latestAccount->account, $matches);
            $nextNumber = isset($matches[0]) ? (int)ltrim($matches[0], '0') + 1 : 1;
        }

        if ($nextNumber > $maxValue) {
            throw new \Exception("New account limit reached.");
        }

        return \Auth::user()->customerNumberFormat($nextNumber);
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create customer'))
        {
            // Convert 07xxxxxxxx or 01xxxxxxxx to 2547xxxxxxxx / 2541xxxxxxxx
            if (preg_match('/^(07|01)(\d{8})$/', $request->contact, $matches)) {
                $request->merge([
                    'contact' => '254' . substr($matches[0], 1),
                ]);
            }

            // Ensure username is the same as account if it's null
            $request->merge([
                'username' => $request->username ?? $request->account,
            ]);
            $rules = [
                'fullname'  => 'required|string|max:255',
                'username'  => 'nullable|string|max:255',
                'account'   => 'nullable|string|max:255',
                'package'   => 'required|numeric',
                'email'     => [
                    'required',
                    'email',
                    Rule::unique('customers')->where(function ($query) {
                        return $query->whereRaw('LOWER(email) = LOWER(?)', [request('email')])
                                    ->where('created_by', \Auth::user()->id);
                    })
                ],
                'contact'   => ['required', 'regex:/^254[17][0-9]{8}$/'],
                'service'   => 'nullable|string|max:255',
                'mac_address' => 'nullable|string|max:255|unique:customers,mac_address',
                'charges'   => 'nullable|string|max:255',
                'expiry'      => 'nullable|date',
            ];

            $validator = \Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return redirect()->route('customer.index')->with('error', $validator->errors()->first());
            }

            // $defaultLanguage = DB::table('settings')->where('name', 'default_language')->value('value');
            $expiration = Carbon::now()->addMinutes(30);

            $customer = new Customer();
            $customer->customer_id  = $this->customerNumber();
            $customer->fullname     = $request->fullname;
            $customer->username     = $request->username ?: $request->account;
            $customer->account      = $request->account;
            $customer->password     = $request->password;
            $customer->email        = $request->email;
            $customer->contact      = $request->contact;
            $customer->created_by   = \Auth::user()->creatorId();
            $customer->service      = $request->service;
            $customer->auto_renewal = 1;
            $customer->is_active    = 1;
            $customer->mac_address  = $request->mac_address;
            $customer->maclock      = 1;
            $customer->charges      = $request->charges;

            // Get package by name
            $package = Package::where('created_by', \Auth::user()->creatorId())
                ->find($request->package);

            if ($package) {
                $customer->package = $package->name_plan;
                $customer->package_id = $package->id;
            }

            $customer->apartment    = $request->apartment;
            $customer->location     = $request->location;
            $customer->housenumber  = $request->housenumber;
            $customer->expiry       = $expiration;
            $customer->status       = 'on';
            $customer->lang         = !empty($defaultLanguage) ? $defaultLanguage : 'en';
            $customer->balance      = 0.00;
            $customer->save();

            // Use the package we already looked up
            $bandwidth = Bandwidth::where('package_id', $package->id)->first();
            $group_name = 'package_' . $package->id;
            $down = $this->convertBandwidth($bandwidth->rate_down, $bandwidth->rate_down_unit);
            $up = $this->convertBandwidth($bandwidth->rate_up, $bandwidth->rate_up_unit);
            $MikroRate = "{$bandwidth->rate_down}{$bandwidth->rate_down_unit}/{$bandwidth->rate_up}{$bandwidth->rate_up_unit}";
            $createdBy = Auth::user()->creatorId();
            $shared = $package->shared_users;

            DB::table('radcheck')->insert([
                ['username' => $customer->username, 'attribute' => 'Cleartext-Password', 'op' => ':=', 'value' => $request->password, 'created_by' => $createdBy]
            ]);
            DB::table('radreply')->insert([
                ['username' => $customer->username, 'attribute' => 'Mikrotik-Rate-Limit', 'op' => ':=', 'value' => $MikroRate, 'created_by' => $createdBy]
            ]);
            // Assign the Expired_Plan
            DB::table('radusergroup')->insert([
                'username'  => $customer->username,
                'groupname' => $group_name,
                'priority'  => 1,
                'created_by' => $createdBy,
            ]);

            // Custom Field Handling
            if ($request->has('customField')) {
                CustomField::saveData($customer, $request->customField);
            }

            // Notification Handling - Send Welcome SMS to New User
            $settings = Utility::settings(\Auth::user()->creatorId());
            // $settings = Utility::settings($customer->created_by); // Use customer's creator ID directly

            $phone = $customer->contact;
            $amount = (int)$customer->charges + (int)$package->price;
            $smsTemplate = SmsAlert::where('type', 'New-User')
                ->where('created_by', $createdBy)
                ->first();

            $templateText = $smsTemplate->template ?? 'Welcome {fullname}, Thank you for choosing {company}. Your Account number is: {username}. Payment Mode: Paybill: Account: {account} Amount: {amount}';

            // Replace placeholders with actual data
            $txt = str_replace(
                ['{username}', '{amount}', '{account}', '{fullname}', '{company}'],
                [$customer->username, $amount, $customer->account, $customer->fullname, $settings['company_name'] ?? 'Our Company'],
                $templateText
            );

            CustomHelper::sendAutoSMS($phone, $txt, $createdBy);

            return redirect()->route('customer.show', ['customer' => encrypt($customer->id)])->with('success', __('Customer successfully created.'));
        }
        else
        {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }


    public function show($ids)
    {
        try {
            $id = Crypt::decrypt($ids);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('Customer Not Found.'));
        }

        $customer = Customer::with(['children', 'parent'])->findOrFail($id);
        // $balance = $customer->balance - abs($customer->charges);
        $balance = $customer->balance - abs((int) $customer->charges);

        $rawMonthlyData = DB::table('monthly_usages')
            ->where('customer_id', $customer->id)
            ->where('year', date('Y'))
            ->select(
                'month',
                DB::raw('ROUND((upload + download) / 1024 / 1024, 2) as total')
            )
            ->pluck('total', 'month');

        $monthlyTotals = collect(range(1, 12))->map(function ($month) use ($rawMonthlyData) {
            return $rawMonthlyData->get($month, 0);
        });


        $monthData = DB::table('monthly_usages')
            ->where('customer_id', $customer->id)
            ->where('year', date('Y'))
            ->where('month', date('n'))
            ->select(
                'month',
                DB::raw('upload'),
                DB::raw('download')
            )
            ->first();

        $arrType = [
            'PPPoE' => __('PPPoE'),
        ];

        $arrPackage = Package::where('created_by', \Auth::user()->creatorId())
            ->where('type', 'PPPoE')
            // ->pluck('name_plan')
            ->get();

        $expiryDate = $customer->extension_expiry ?? $customer->expiry;
        $currentDate = Carbon::now();
        $expiryStatus = 'No Expiry Set';

        if ($expiryDate) {
            $expiryDate = Carbon::parse($expiryDate);
            $diff = $currentDate->diff($expiryDate);

            if ($expiryDate->isFuture()) {
                // Format with days, hours, and minutes
                if ($diff->d > 0) {
                    $expiryStatus = sprintf("%dd %02dh %02dm", $diff->d, $diff->h, $diff->i);
                } else {
                    $expiryStatus = sprintf("%02dh %02dm", $diff->h, $diff->i);
                }
            } elseif ($expiryDate->isPast()) {
                // For expired accounts, show how long ago it expired
                $expiryStatus = sprintf("Expired %dd %02dh %02dm ago", $diff->d, $diff->h, $diff->i);
            } else {
                $expiryStatus = "Expires today";
            }
        }
        // If expiry_extended is used, mark it as extended
        if ($customer->is_extended == 1) {
            $diffExtended = $currentDate->diff($expiryDate);
            if ($diffExtended->d > 0) {
                $expiryStatus = sprintf("Extended for %dd %02dh %02dm", $diffExtended->d, $diffExtended->h, $diffExtended->i);
            } else {
                $expiryStatus = sprintf("Extended for %02dh %02dm", $diffExtended->h, $diffExtended->i);
            }
        }

        CustomHelper::lockMac($customer);


        $nasIps = Router::where('created_by', \Auth::user()->creatorId())->pluck('ip_address')->toArray();

        // Check if the user is online only if the NAS IP matches the ISP's NAS
        $online = DB::table('radacct')
            ->whereNull('acctstoptime')
            ->where('username', $customer->username)
            ->where('created_by', $customer->created_by)
            ->whereIn('nasipaddress', $nasIps)
            ->exists();

        $session = DB::table('radacct')
            ->whereNull('acctstoptime')
            ->where('username', $customer->username)
            ->where('created_by', $customer->created_by)
            ->whereIn('nasipaddress', $nasIps)
            ->select('framedipaddress as ip', 'acctsessiontime as uptime')
            ->first();

        $now = now();
        $displayUptime = 'Unknown';
        $radacct = DB::table('radacct')
            ->whereNull('acctstoptime')
            ->where('username', $customer->username)
            ->where('created_by', $customer->created_by)
            ->whereIn('nasipaddress', $nasIps)
            ->orderByDesc('acctstarttime')
            ->first();

        if ($radacct) {
            // Online: calculate uptime from acctstarttime to now
            $start = Carbon::parse($radacct->acctstarttime);
            $uptimeSeconds = $start->diffInSeconds($now);
            $days = floor($uptimeSeconds / 86400);
            $hours = floor(($uptimeSeconds % 86400) / 3600);
            $minutes = floor(($uptimeSeconds % 3600) / 60);
            $seconds = $uptimeSeconds % 60;

            $displayUptime = $days > 0
                ? sprintf("%dd %02d:%02d:%02d", $days, $hours, $minutes, $seconds)
                : sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);

            $status = 'online';
        } else {
            // Offline: fallback to customers.last_seen
            if ($customer->last_seen) {
                $stopTime = Carbon::parse($customer->last_seen);
                $downtimeSeconds = $stopTime->diffInSeconds($now);
                $days = floor($downtimeSeconds / 86400);
                $hours = floor(($downtimeSeconds % 86400) / 3600);
                $minutes = floor(($downtimeSeconds % 3600) / 60);
                $seconds = $downtimeSeconds % 60;

                $displayUptime = $days > 0
                    ? sprintf("%dd %02d:%02d:%02d", $days, $hours, $minutes, $seconds)
                    : sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);

                $status = 'offline';
            } else {
                $displayUptime = 'No session history';
                $status = 'offline';
            }
        }

        // Fetch data usage only for sessions matching the NAS
        $dataUsage = DB::table('radacct')
            ->where('username', $customer->username)
            ->where('created_by', $customer->created_by)
            ->whereIn('nasipaddress', $nasIps)
            ->selectRaw('COALESCE(SUM(acctoutputoctets), 0) as download, COALESCE(SUM(acctinputoctets), 0) as upload')
            ->first();

        $activeUsage = DB::table('radacct')
            ->where('username', $customer->username)
            ->where('created_by', $customer->created_by)
            ->whereNull('acctstoptime')
            // ->whereIn('nasipaddress', $nasIps)
            ->selectRaw('COALESCE(SUM(acctoutputoctets), 0) as download, COALESCE(SUM(acctinputoctets), 0) as upload')
            ->first();

        $downloadMB = round($activeUsage->download / 1048576, 2);
        $uploadMB = round($activeUsage->upload / 1048576, 2);

        $transactions = Transaction::where('user_id', $id)->where('user_type', 'Customer')->get();
        $invoices = Invoice::where('customer_id', $id)->get();
 
        $authLogs = DB::table('radpostauth')
            ->whereRaw('LOWER(username) = ?', [strtolower($customer->username)])
            ->where('created_by', $customer->created_by)
            // ->where('reply', 'like', 'Access-Reject%')
            ->whereIn('nasipaddress', $nasIps)
            ->orderBy('authdate', 'desc')
            ->limit(5)
            ->get();

        $deviceVendor = $customer->mac_address ? CustomHelper::getMacVendor($customer->mac_address) : 'N/A';

        return view('customer.show', compact(
            'customer', 'expiryStatus', 'online', 'session', 'arrType', 'displayUptime', 'arrPackage', 'dataUsage', 'downloadMB', 'uploadMB', 'transactions', 'invoices', 'authLogs', 'deviceVendor', 'monthlyTotals', 'monthData', 'balance'
        ));
    }

    public function edit($id)
    {
        if(\Auth::user()->can('edit customer'))
        {
            $customer              = Customer::find($id);
            $customer->customField = CustomField::getData($customer, 'customer');

            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'customer')->get();

            return view('customer.edit', compact('customer', 'customFields'));
        }
        else
        {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function initiateStk(Request $request, $id){
        if(\Auth::user()->can('edit customer'))
        {
            // Convert 07xxxxxxxx or 01xxxxxxxx to 2547xxxxxxxx / 2541xxxxxxxx
            if (preg_match('/^(07|01)(\d{8})$/', $request->phone, $matches)) {
                $request->merge([
                    'phone' => '254' . substr($matches[0], 1),
                ]);
            }

            $rules = [
                'phone'   => ['required', 'regex:/^254[17][0-9]{8}$/'],
                'amount'   => 'required|string|max:255',
            ];

            $validator = \Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                ToastMagic::error($validator->errors()->first());
                return redirect()->back();
            }

            $customer = Customer::where('created_by', \Auth::user()->creatorId())->find($id);
            $phone = $request->phone;
            $amount = $request->amount;
            $isp = \Auth::user()->creatorId();
            $account = $customer->account;

            $mpesaResponse = CustomHelper::fastInitiateSTKPush($account, $phone, $amount, $isp, 'PPPoE');

            $mpesaResponse = (array) $mpesaResponse;
            $checkoutRequestID = $mpesaResponse['CheckoutRequestID'] ?? null;

            if ($checkoutRequestID) {
                \DB::table('transactions')->insert([
                    'checkout_id'  => $checkoutRequestID,
                    'status'       => 0,
                    'gateway'      => 'MPesa',
                    'package_id'   => $customer->package_id,
                ]);
            }
            else {
                \Log::error('CheckoutRequestID is invalid or not found');
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment request sent',
                'checkoutRequestID' => $checkoutRequestID,
                'cID' => $cID
            ]);
        }else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }
    public function update(Request $request, Customer $customer)
    {
        if(\Auth::user()->can('edit customer'))
        {
            // Convert contact numbers from 07xxxxxxxx or 01xxxxxxxx to 2547xxxxxxxx / 2541xxxxxxxx
            if (preg_match('/^(07|01)(\d{8})$/', $request->contact, $matches)) {
                $request->merge([
                    'contact' => '254' . substr($matches[0], 1),
                ]);
            }

            // Ensure username is the same as account if it's null
            $request->merge([
                'username' => $request->username ?? $request->account,
            ]);

            $rules = [
                'fullname'  => 'required|string|max:255',
                'username'  => 'nullable|string|max:255',
                'account'   => 'nullable|string|max:255',
                'email'     => [
                    'required',
                    'email',
                    Rule::unique('customers')->ignore($customer->id)->where(function ($query) {
                        return $query->whereRaw('LOWER(email) = LOWER(?)', [request('email')])
                                    ->where('created_by', \Auth::user()->id);
                    })
                ],
                'contact'   => ['required', 'regex:/^254[17][0-9]{8}$/'],
                'service'   => 'nullable|string|max:255',
                // 'static_ip'   => 'nullable|ip',
            ];

            $validator = \Validator::make($request->all(), $rules);
            if($validator->fails()){
                \Log::error('Validation failed', $validator->errors()->toArray());
                return redirect()->route('customer.show', ['customer' => encrypt($customer->id)])
                                ->with('error', $validator->errors()->first());
            }

            // Assign updated fields
            $customer->fullname    = $request->fullname;
            $customer->username    = $request->username;
            $customer->account     = $request->account;
            $customer->password    = $request->password;
            $customer->email       = $request->email;
            $customer->contact     = $request->contact;
            $customer->created_by  = \Auth::user()->creatorId();
            $customer->service     = $request->service;
            $customer->mac_address = $request->mac_address;
            $customer->charges     = $request->charges;

            // Get package by name
            $package = Package::where('created_by', \Auth::user()->creatorId())
                ->find($request->package);

            if ($package) {
            $customer->package = $package->name_plan;
            $customer->package_id = $package->id;
            }

            $customer->apartment   = $request->apartment;
            $customer->location    = $request->location;
            $customer->housenumber = $request->housenumber;

            if(!$customer->save()){
                ToastMagic::error('Failed to save customer', ['customer_id' => $customer->id]);
                return redirect()->back();
            }

            CustomField::saveData($customer, $request->customField);
            ToastMagic::success('Customer successfully updated.');
            return redirect()->route('customer.show', ['customer' => encrypt($customer->id)]);
        }
        else
        {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }

    }
    public function updateExpiry(Request $request, $id)
    {
        $request->validate([
            'expiry' => 'required',
        ]);

        $customer = Customer::findOrFail($id);
        $customer->expiry = $request->expiry;
        $customer->extension_start = null;
        $customer->extension_expiry = null;
        $customer->is_extended = 0;
        $customer->save();
        CustomHelper::editExpiry($customer->id);
        
        // Update child accounts that inherit expiry
        self::updateChildrenExpiry($customer);

        return redirect()->route('customer.show', ['customer' => encrypt($id)])->with('success', __('Expiry date updated successfully.'));
    }

    public function updateExtend(Request $request, $id)
    {
        $request->validate([
            'extend_expiry' => 'required|date|after:now',
        ]);

        $customer = Customer::findOrFail($id);

        $now = Carbon::now();
        $extensionExpiry = Carbon::parse($request->extend_expiry);

        $customer->extension_start = $now;
        $customer->extension_expiry = $extensionExpiry;
        $customer->is_extended = 1;

        // No need to calculate or store extended_days anymore
        $customer->save();

        CustomHelper::editExpiry($customer->id);

        self::updateChildrenExpiry($customer);

        return redirect()
            ->route('customer.show', ['customer' => encrypt($id)])
            ->with('success', __('Expiry extended successfully.'));
    }

    
    // Helper method to update child accounts' expiry
    public static function updateChildrenExpiry($parent)
    {
        // Find all children with inherit_expiry set to true
        $children = Customer::where('parent_id', $parent->id)
                          ->where('inherit_expiry', true)
                          ->get();
                          
        foreach ($children as $child) {
            // Copy parent's expiry settings to child
            $child->expiry = $parent->expiry;
            $child->extension_start = $parent->extension_start;
            $child->extension_expiry = $parent->extension_expiry;
            $child->is_extended = $parent->extension_status;
            $child->status = $parent->status;
            $child->save();
            
            // Update RADIUS settings for the child
            CustomHelper::editExpiry($child->id);
        }
    }

    public function depositCash(Request $request, $id)
    {
        $request->validate([
            'balance' => 'required|numeric',
        ]);

        $customer = Customer::findOrFail($id);
        $package = $customer->package_id ? 
            Package::with('bandwidth')->find($customer->package_id) : 
            Package::with('bandwidth')->where('name_plan', $customer->package)->where('created_by', \Auth::user()->id)->where('type', 'PPPoE')->first();
            
        $amount = $request->balance;
        $transactionDate = Carbon::now();
        $TransID = "DEP-" . strtoupper(Str::random(6));

        $isp = \Auth::user()->id;
        $type = 'DEPOSIT';
        CustomHelper::handlePayments($amount, $package, $customer, $transactionDate, $TransID, $isp, $type);
        
        // $customer->save();

        return redirect()->route('customer.show', ['customer' => encrypt($id)])->with('success', __('Balance updated successfully.'));
    }
    
    public function refreshAccount(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        $isp = Auth::user()->creatorId();
        DB::table('radusergroup')->where('username', $customer->username)->where('created_by', $isp)->delete();
        DB::table('radcheck')->where('username', $customer->username)->where('created_by', $isp)->delete();
        DB::table('radreply')->where('username', $customer->username)->where('created_by', $isp)->delete();
        
        $package = $customer->package_id ? 
            Package::find($customer->package_id) : 
            Package::where('name_plan', $customer->package)->where('created_by', $isp)->first();
            
        $bandwidth = Bandwidth::where('package_id', $package->id)->first();

        if ($customer->is_override) {
            $MikroRate = "{$customer->override_download}{$customer->override_download_unit}/{$customer->override_upload}{$customer->override_upload_unit}";
        } else {
            $MikroRate = "{$bandwidth->rate_down}{$bandwidth->rate_down_unit}/{$bandwidth->rate_up}{$bandwidth->rate_up_unit}";
        }
        
        DB::table('radcheck')->insert([
            'username' => $customer->username, 'attribute' => 'Cleartext-Password', 'op' => ':=', 'value' => $customer->password, 'created_by' => $isp,
        ]);

        DB::table('radreply')->insert([
            ['username' => $customer->username, 'attribute' => 'Mikrotik-Rate-Limit', 'op' => ':=', 'value' => $MikroRate, 'created_by' => $isp],
        ]);

        if ($customer->status == 'off') {
            $group_name = 'Expired_Plan';
        } elseif ($customer->is_suspended == 1) {
            $group_name = 'Disabled_Plan';
        } elseif ($customer->is_active == 0) {
            $group_name = 'Disabled_Plan';
        } else {
            $group_name = 'package_' . $package->id;
        }
        DB::table('radusergroup')->insert([
            'username' => $customer->username, 'groupname' => $group_name, 'priority' => 1, 'created_by' => $isp,
        ]);

        // $result = CustomHelper::refreshCustomerInRadius($customer); 
        
        $activeSession = DB::table('radacct')
            ->where('username', $customer->username)
            ->where('created_by', $customer->created_by)
            ->whereNull('acctstoptime')
            ->orderBy('acctstarttime', 'desc')
            ->first();

        if ($activeSession) {
            $nasObj = DB::table('nas')->where('nasname', $activeSession->nasipaddress)->first();

            if ($nasObj) {
                $attributes = [
                    'acctSessionID'   => $activeSession->acctsessionid,
                    'framedIPAddress' => $activeSession->framedipaddress,
                ];
              $result = CustomHelper::kickOutUsersByRadius($nasObj, $customer, $attributes);
            } else {
                Log::warning("NAS not found for IP: {$activeSession->nasipaddress}");
            }
        }

        // if ($result['status'] === 'success') {
        return redirect()->route('customer.show', ['customer' => encrypt($id)]);
        // } else {
        //     return redirect()->route('customer.show', ['customer' => encrypt($id)])
        //         ->with('error', __($result['message']));
        // }
    }
 
    public function asCorporate(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        
        $customer->corporate = $customer->corporate == 0 ? 1 : 0;
        $customer->save();

        return redirect()->route('customer.show', ['customer' => encrypt($id)])->with('success', __('Corporate added successfully.'));
    }

        public function changePlan(Request $request, $id)
    {
        $request->validate([
            'package' => 'required|exists:packages,id',
        ]);

        $customer = Customer::findOrFail($id);

        $package = Package::where('created_by', \Auth::user()->creatorId())
            ->findOrFail($request->package);


        // Update both package_id and package name
        $customer->package_id = $package->id;
        $customer->package = $package->name_plan;
        $customer->save();

        CustomHelper::updatePlan($customer);

        return redirect()->route('customer.show', ['customer' => encrypt($id)])
            ->with('success', __('Package updated successfully.'));
    }
    
    public function deactivate(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        // Toggle activation status
        $customer->is_active = !$customer->is_active;
        $customer->save();

        if (!$customer->is_active) {
            CustomHelper::handleDeactivation($customer);
            $message = __('Customer deactivated and moved to expired plan.');
        } else {
            CustomHelper::activateCustomer($customer);
            $message = __('Customer activated successfully.');
        }

        return redirect()->route('customer.show', ['customer' => encrypt($id)])
            ->with('success', $message);
    }

    // public function clearMac($id)
    // {
    //     \Log::info("called");
    //     $customer = Customer::findOrFail($id);

    //     // Clear MAC address
    //     $customer->mac_address = null;
    //     $customer->save();
    //     CustomHelper::unlockMac($customer);

    //     \Log::info('MAC address cleared successfully');

    //     return redirect()->route('customer.show', ['customer' => encrypt($id)])
    //         ->with('success', __('MAC address cleared successfully.'));
    // }
    public function clearMac($id)
    {
        \Log::info("clearMac called for customer ID: $id");

        $customer = Customer::findOrFail($id);

        // Unlock MAC before clearing it in the controller
        CustomHelper::unlockMac($customer);

        // Force clear again just in case
        $customer->mac_address = null;
        $customer->save();

        \Log::info("MAC address cleared for customer ID: $id");

        return redirect()->route('customer.show', ['customer' => encrypt($customer->id)])
            ->with('success', __('MAC address cleared successfully.'));
    }
    
    public function suspend($id)
    {
        $customer = Customer::findOrFail($id);

        if (!$customer->is_suspended) {
            $customer->is_suspended = 1;
            $customer->suspended_at = now();
            $customer->save();

            CustomHelper::handleDeactivation($customer);

            return back()->with('success', 'Customer suspended and moved to Disabled_Plan.');
        }

        return back()->with('warning', 'Customer is already suspended.');
    }

    public function unsuspend($id)
    {
        $customer = Customer::findOrFail($id);

        if ($customer->is_suspended && $customer->suspended_at) {
            $suspendedAt = Carbon::parse($customer->suspended_at);
            $now = Carbon::now();

            if ($suspendedAt->lessThan($now)) {
                $suspendedSeconds = $suspendedAt->diffInSeconds($now);
        
                if ($suspendedSeconds >= 60) { 
                    if ($customer->expiry) {
                        $customer->expiry = Carbon::parse($customer->expiry)->addSeconds($suspendedSeconds);
                    }
                }
        
                // Clear suspension
                $customer->is_suspended = 0;
                $customer->suspended_at = null;
                $customer->save();
                CustomHelper::activateCustomer($customer);
        
                return back()->with('success', 'Customer unsuspended' . ($suspendedSeconds >= 60 ? ' and expiry extended.' : '.'));
            } else {
                return back()->with('warning', 'Invalid suspension timestamp.');
            }
        }        
    }


    public function destroy(Customer $customer)
    {
        if(\Auth::user()->can('delete customer'))
        {
            if($customer->created_by == \Auth::user()->creatorId())
            {
                $customer->delete();

                return redirect()->route('customer.index')->with('success', __('Customer successfully deleted.'));
            }
            else
            {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    function customerNumber()
    {
        $latest = Customer::where('created_by', '=', \Auth::user()->creatorId())->latest()->first();
        if(!$latest)
        {
            return 1;
        }
        return $latest->customer_id + 1;
    }

    public function customerLogout(Request $request)
    {
        \Auth::guard('customer')->logout();

        $request->session()->invalidate();

        return redirect()->route('customer.login');
    }

    public function payment(Request $request)
    {

        if(\Auth::user()->can('manage customer payment'))
        {
            $category = [
                'Invoice' => 'Invoice',
                'Deposit' => 'Deposit',
                'Sales' => 'Sales',
            ];

            $query = Transaction::where('user_id', \Auth::user()->id)->where('user_type', 'Customer')->where('type', 'Payment');
            if(!empty($request->date))
            {
                $date_range = explode(' - ', $request->date);
                $query->whereBetween('date', $date_range);
            }

            if(!empty($request->category))
            {
                $query->where('category', '=', $request->category);
            }
            $payments = $query->get();

            return view('customer.payment', compact('payments', 'category'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function transaction(Request $request)
    {
        if(\Auth::user()->can('manage customer payment'))
        {
            $category = [
                'Invoice' => 'Invoice',
                'Deposit' => 'Deposit',
                'Sales' => 'Sales',
            ];

            $query = Transaction::where('user_id', \Auth::user()->id)->where('user_type', 'Customer');

            if(!empty($request->date))
            {
                $date_range = explode(' - ', $request->date);
                $query->whereBetween('date', $date_range);
            }

            if(!empty($request->category))
            {
                $query->where('category', '=', $request->category);
            }
            $transactions = $query->get();

            return view('customer.transaction', compact('transactions', 'category'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function transactionData(Request $request)
    {
        if(!\Auth::user()->can('manage customer payment')) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $query = Transaction::where('user_id', \Auth::user()->id)
                          ->where('user_type', 'Customer');

        if(!empty($request->date)) {
            $date_range = explode(' - ', $request->date);
            $query->whereBetween('date', $date_range);
        }

        if(!empty($request->category)) {
            $query->where('category', '=', $request->category);
        }

        return DataTables::of($query)
            ->editColumn('date', function ($transaction) {
                return \Auth::user()->dateFormat($transaction->date);
            })
            ->editColumn('amount', function ($transaction) {
                return \Auth::user()->priceFormat($transaction->amount);
            })
            ->editColumn('account', function ($transaction) {
                return !empty($transaction->bankAccount()) 
                    ? $transaction->bankAccount()->bank_name . ' ' . $transaction->bankAccount()->holder_name 
                    : '';
            })
            ->rawColumns(['date', 'amount', 'account'])
            ->make(true);
    }

    public function profile()
    {
        $userDetail              = \Auth::user();
        $userDetail->customField = CustomField::getData($userDetail, 'customer');
        $customFields            = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'customer')->get();

        return view('customer.profile', compact('userDetail', 'customFields'));
    }

    public function editprofile(Request $request)
    {
        $userDetail = \Auth::user();
        $user       = Customer::findOrFail($userDetail['id']);

        $this->validate(
            $request, [
                        'name' => 'required|max:120',
                        'contact' => 'required',
                        'email' => 'required|email|unique:users,email,' . $userDetail['id'],
                    ]
        );

        if($request->hasFile('profile'))
        {
            $filenameWithExt = $request->file('profile')->getClientOriginalName();
            $filename        = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $extension       = $request->file('profile')->getClientOriginalExtension();
            $fileNameToStore = $filename . '_' . time() . '.' . $extension;

            $dir        = storage_path('uploads/avatar/');
            $image_path = $dir . $userDetail['avatar'];

            if(File::exists($image_path))
            {
                File::delete($image_path);
            }

            if(!file_exists($dir))
            {
                mkdir($dir, 0777, true);
            }

            $path = $request->file('profile')->storeAs('uploads/avatar/', $fileNameToStore);

        }

        if(!empty($request->profile))
        {
            $user['avatar'] = $fileNameToStore;
        }
        $user['name']    = $request['name'];
        $user['email']   = $request['email'];
        $user['contact'] = $request['contact'];
        $user->save();
        CustomField::saveData($user, $request->customField);

        return redirect()->back()->with(
            'success', 'Profile successfully updated.'
        );
    }


    public function export()
    {
        try {
            $name = 'customer_' . date('Y-m-d_H-i-s');
            
            ob_start();
            
            $data = Excel::download(new CustomerExport(), $name . '.xlsx');
            
            ob_clean();
            
            return $data;
        } catch (\Exception $e) {
            \Log::error('Customer export error: ' . $e->getMessage());
            
            ob_end_clean();
            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    public function importFile()
    {
        return view('customer.import');
    }


    public function directCustomerImport(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|mimes:csv,txt|max:2048'
            ]);

            $file = $request->file('file');
            $filePath = $file->getRealPath();

            $handle = fopen($filePath, "r");
            if (!$handle) {
                return response()->json(['error' => 'Unable to open CSV file'], 400);
            }

            $headers = fgetcsv($handle);
            if (!$headers) {
                return response()->json(['error' => 'CSV file is empty or missing headers'], 400);
            }

            $rows = [];
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) == count($headers)) {
                    $rows[] = array_combine($headers, $row);
                }
            }
            fclose($handle);

            foreach ($rows as $data) {
                $email = isset($data['username']) ? strtolower($data['username']) . '@isp.net' : null;

                $latest = Customer::where('created_by', '=', \Auth::user()->creatorId())->latest()->first();

                if (!$latest || empty($latest->account)) {
                    $customerN = Auth::user()->customerNumberFormat(1); // Start from 1 if no existing account
                } else {
                    // Extract the numeric part of the account and increment it
                    preg_match('/\d+$/', $latest->account, $matches);
                    $nextNumber = isset($matches[0]) ? (int)$matches[0] + 1 : 1;

                    $customerN = Auth::user()->customerNumberFormat($nextNumber);
                }

                // Set expiry date (default: 7 days from now)
                $expiryDate =  $data['expiry'] ?? null;

                if ($expiryDate) {
                    try {
                        $cleanDate = Carbon::parse($expiryDate)->format('Y-m-d H:i:s');
                    } catch (Exception $e) {
                        $cleanDate = null; // or handle error
                    }
                }
                
                $service = $data['service'] ?? null;

                if ($service === "PPPOE" || $service === "pppoe") {
                    $service = "PPPoE";
                } elseif ($service === "HOTSPOT" || $service === "hotspot") {
                    $service = "Hotspot";
                }

                // $expiryDate = now()->addDays(7)->toDateString();
                $fullname = $data['fullname'] ?? ($data['firstname'] . ' ' . $data['lastname']);

                $customer = new Customer();
                $customer->customer_id = $this->customerNumber();
                $customer->fullname = $fullname ?? null;
                $customer->password = $data['password'] ?? null;
                $customer->username = $data['username'] ?? null;
                $customer->account = $data['account'] ?? null;
                $customer->email = $email;
                $customer->contact = $data['contact'] ?? null;
                $customer->service = $data['service'] ?? null;
                $customer->package = $data['package'] ?? null;
                $customer->apartment = $data['apartment'] ?? null;
                $customer->location = $data['location'] ?? null;
                $customer->housenumber = $data['housenumber'] ?? null;
                $customer->expiry = $cleanDate;
                $customer->status = 'on';
                $customer->lang = $data['lang'] ?? 'en';
                $customer->balance = $data['balance'] ?? '0.00';
                $customer->mac_address = $data['mac_address'] ?? null;
                // $customer->static_ip = $data['static_ip'] ?? null;
                // $customer->sms_group = $data['sms_group'] ?? null;
                $customer->charges = $data['charges'] ?? null;
                $customer->avatar = $data['avatar'] ?? '';
                $customer->auto_renewal = $data['auto_renewal'] ?? 1;
                $customer->created_by = Auth::user()->creatorId();
                $customer->is_active = 1;
                $customer->save();

                Log::info("Customer added successfully: " . $customer->username);

                // --- Add Customer to FreeRADIUS ---
                $radiusUsername = $customer->username ?? $customer->account;
                $radiusPassword = $customer->password;
                $importPackage = $customer->package;
                $createdBy = Auth::user()->creatorId();
                $radiusGroup = 'Expired_Plan';

                if ($importPackage) {
                    $package = $customer->package_id ? 
                        Package::find($customer->package_id) : 
                        Package::where('name_plan', $importPackage)->first();
                        
                    if ($package) {
                        $radiusGroup = 'package_' . $package->id;
                        // Set the package_id on the customer after lookup
                        $customer->package_id = $package->id;
                        $customer->save();
                        
                        $bandwidth = Bandwidth::where('package_id', $package->id)->first();
                        $MikroRate = "{$bandwidth->rate_down}{$bandwidth->rate_down_unit}/{$bandwidth->rate_up}{$bandwidth->rate_up_unit}";
                    }
                }

                if (strtotime($expiryDate) < strtotime(now())) {
                    $radiusGroup = 'Expired_Plan';
                }

                DB::table('radcheck')->insert([
                    'username' => $radiusUsername,
                    'attribute' => 'Cleartext-Password',
                    'op' => ':=',
                    'value' => $radiusPassword,
                    'created_by' => $createdBy,
                ]);
                DB::table('radreply')->insert([
                    ['username' => $radiusUsername, 'attribute' => 'Mikrotik-Rate-Limit', 'op' => ':=', 'value' => $MikroRate, 'created_by' => $createdBy],
                ]);
                DB::table('radusergroup')->insert([
                    'username' => $radiusUsername,
                    'groupname' => $radiusGroup,
                    'priority' => 1,
                    'created_by' => $createdBy,
                ]);

                Log::info("FreeRADIUS user added: " . $radiusUsername);
            }

            return redirect()->back()->with('success', __('Customers imported successfully'));

        } catch (\Exception $e) {
            Log::error("Error in directCustomerImport: " . $e->getMessage());
            return redirect()->back()->with('error', __('Something went wrong. Check logs.'));
        }
    }
    
    public function searchCustomers(Request $request)
    {
        if (\Illuminate\Support\Facades\Auth::user()->can('manage customer')) {
            $customers = [];
            $search    = $request->search;
            if ($request->ajax() && isset($search) && !empty($search)) {
                $customers = Customer::select('id as value', 'name as label', 'email')->where('is_active', '=', 1)->where('created_by', '=', Auth::user()->getCreatedBy())->Where('name', 'LIKE', '%' . $search . '%')->orWhere('email', 'LIKE', '%' . $search . '%')->get();

                return json_encode($customers);
            }

            return $customers;
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function getLiveUsage($username)
    {
        try {
            $customer = Customer::where('username', $username)->first();
            if (!$customer) {
                return response()->json([
                    'download' => 0,
                    'upload' => 0,
                    'timestamp' => now()->format('H:i:s')
                ]);
            }

            $router = Router::where('ip_address', $customer->site)
                          ->where('created_by', $customer->created_by)
                          ->first();

            if (!$router) {
                return response()->json([
                    'download' => 0,
                    'upload' => 0,
                    'timestamp' => now()->format('H:i:s')
                ]);
            }

            $trafficData = \App\Helpers\NetworkHelper::getLiveTrafficSpeed(
                $router->ip_address,
                $router->ip_address, // Use IP as username
                $router->secret,
                $router->api_port ?? 8728,
                $username,
                $customer->service,
                1
            );

            if (!$trafficData) {
                return response()->json([
                    'download' => 0,
                    'upload' => 0,
                    'timestamp' => now()->format('H:i:s')
                ]);
            }

            return response()->json([
                'download' => $trafficData['download_kbps'] / 1000, // Convert to Mbps
                'upload' => $trafficData['upload_kbps'] / 1000, // Convert to Mbps
                'timestamp' => now()->format('H:i:s')
            ]);

        } catch (\Exception $e) {
            \Log::error("Error in getLiveUsage for {$username}: " . $e->getMessage());
            return response()->json([
                'download' => 0,
                'upload' => 0,
                'timestamp' => now()->format('H:i:s')
            ]);
        }
    }
    
   
    public function useBalance(Request $request, $customerId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'type' => 'required|in:installation,package',
        ]);

        $customer = Customer::findOrFail($customerId);

        if ($customer->balance < $request->amount) {
            return back()->with('error', 'Insufficient balance');
        }

        // Update user balance
        Utility::updateUserBalance('customer', $customer->id, $request->amount, 'credit');

        return back()->with('success', 'Transaction completed successfully!');
    }
    
    public function refreshCustomerRadiusRecords(Request $request)
    {
        if (!\Auth::user()->can('create customer')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $createdBy = \Auth::user()->creatorId();
        RefreshCustomerRadiusRecordsJob::dispatch($createdBy);

        return redirect()->back()->with('success', __('Radius records refresh started. It will complete in the background.'));
    }
    
    private function convertBandwidth($rate, $unit)
    {
        $multipliers = ['K' => 1000, 'M' => 1000000, 'G' => 1000000000];
        return isset($multipliers[$unit]) ? ($rate * $multipliers[$unit]) : $rate;
    }

    private function convertDataLimit($data, $unit)
    {
        $multipliers = [
            'MB' => 1048576,
            'GB' => 1073741824,
            'TB' => 1099511627776
        ];
        return isset($multipliers[$unit]) ? ($data * $multipliers[$unit]) : 0;
    }

    public function sendSMS(Request $request, $id)
    {
        $request->validate([
            'sender' => 'required|string',
            'message' => 'required|string',
        ]);

        $customer = Customer::findOrFail($id);
        $settings = Utility::settings(\Auth::user()->creatorId());
        $createdBy = \Auth::user()->creatorId();

        $phone = $customer->contact;

        $templateText = $request->message;

        $txt = str_replace(
            ['{username}', '{account}', '{fullname}', '{company}', '{balance}', '{support}', '{package}', '{expiry}'],
            [$customer->username, $customer->account, $customer->fullname, $settings['company_name'] ?? 'Our Company', $customer->balance, $settings['company_telephone'], $customer->package, $customer->expiry],
            $templateText
        );
        
        $gateway = $request->sender;
        if ($gateway == 'sms'){
            CustomHelper::sendSMS($phone, $txt, $createdBy);
        } elseif ($gateway == 'whatsapp'){
            CustomHelper::sendWhatsapp($phone, $txt, $createdBy);
        } elseif ($gateway == 'both'){
            CustomHelper::sendSMS($phone, $txt, $createdBy);
            CustomHelper::sendWhatsapp($phone, $txt, $createdBy);
        }
        return redirect()->back()->with('success', _('SMS sent successfully.'));
    }

    public function resolvePayment(Request $request, $id)
    {
        $request->validate([
            'mpesacode' => 'required|string',
        ]);

        $customer = Customer::findOrFail($id);
        $TransID = $request->mpesacode;
        $createdBy = \Auth::user()->creatorId();

        $transaction = MpesaTransaction::where('TransID', $TransID)->where('created_by', $createdBy)->first();

        $isp = \Auth::user()->creatorId();
        $package = $customer->package_id ? 
            Package::with('bandwidth')->find($customer->package_id) : 
            Package::with('bandwidth')->where('name_plan', $customer->package)->where('created_by', \Auth::user()->id)->where('type', 'PPPoE')->first();
            
        $depositAmount = (int)$transaction->TransAmount;
        $packagePrice = $package->price;
        $prevBalance = (int)$customer->balance;
        $Charges = (int)$customer->charges;
        $transactionDate = Carbon::now();
        $BillRefNumber = $customer->account;
        // $TransID = "D-" . strtoupper(Str::random(6));

        $isp = \Auth::user()->id;
        $settings = Utility::settings($isp);

        $phone = $customer->contact;
        $amount = $depositAmount;
        $smsTemplate = SmsAlert::where('type', 'Deposit-Balance')->where('created_by', $customer->created_by)->first();
        $templateText = $smsTemplate->template ?? 'Dear {username}, Ksh {amount} has been deposited into your account.';

        $txt = str_replace(
            ['{username}', '{amount}', '{account}', '{fullname}', '{company}', '{balance}', '{support}', '{package}', '{expiry}'],
            [$customer->username, $amount, $customer->account, $customer->fullname, $settings['company_name'] ?? 'Our Company', $customer->balance, $settings['company_telephone'], $customer->package, $customer->expiry],
            $templateText
        );
        $type = "MPESA";
        CustomHelper::handlePayments($amount, $package, $customer, $transactionDate, $TransID, $isp, $type);

        $transaction->status = true;
        $transaction->customer = $customer->account;
        $transaction->save();

        return redirect()->back()->with('success', _('SMS sent successfully.'));
    }

    public function addChildAccount(Request $request, $id)
    {
        try {
            $parent = Customer::findOrFail($id);
            
            // Get the last child account number suffix
            $lastChild = Customer::where('parent_id', $parent->id)
                ->where('account', 'like', $parent->account . '-%')
                ->orderByRaw('LENGTH(account) DESC, account DESC')
                ->first();
                
            $suffix = '01';
            if ($lastChild) {
                // Extract the suffix from the account
                $lastSuffix = substr($lastChild->account, strrpos($lastChild->account, '-') + 1);
                if (is_numeric($lastSuffix)) {
                    $suffix = sprintf('%02d', intval($lastSuffix) + 1);
                }
            }
            
            $childAccount = $parent->account . '-' . $suffix;
            $childUsername = $childAccount;
            
            // Generate a random password
            $password = strtoupper(Str::random(8));
            
            // Create the child account
            $child = new Customer();
            $child->customer_id = $this->customerNumber();
            $child->fullname = $parent->fullname;
            $child->username = $childUsername;
            $child->account = $childAccount;
            $child->password = $password;
            $child->email = strtolower($childAccount) . '@isp.net';
            $child->contact = $parent->contact;
            $child->created_by = $parent->created_by;
            $child->service = $parent->service;
            $child->auto_renewal = $parent->auto_renewal;
            $child->is_active = 1;
            $child->maclock = 0;
            $child->package = $parent->package;
            $child->package_id = $parent->package_id;
            $child->apartment = $parent->apartment;
            $child->location = $parent->location;
            $child->housenumber = $parent->housenumber;
            $child->lang = $parent->lang;
            $child->balance = 0.00;
            $child->parent_id = $parent->id;
            $child->inherit_expiry = true;
            
            // Set expiry based on parent's expiry
            if ($parent->expiry) {
                $child->expiry = $parent->expiry;
                $child->status = $parent->status;
            } else {
                // Set default expiry to 7 days from now if parent has no expiry
                $child->expiry = Carbon::now()->addDays(7);
                $child->status = 'on';
            }
            
            $child->save();
            
            // Add to RADIUS
            $createdBy = $parent->created_by;
            
            // Get bandwidth settings from package
            $package = Package::find($parent->package_id);
            if (!$package) {
                $package = Package::where('name_plan', $parent->package)
                    ->where('created_by', $createdBy)
                    ->first();
            }
            
            if ($package) {
                // Use the package we already looked up
                $bandwidth = Bandwidth::where('package_id', $package->id)->first();
                $group_name = 'package_' . $package->id;
                $down = $this->convertBandwidth($bandwidth->rate_down, $bandwidth->rate_down_unit);
                $up = $this->convertBandwidth($bandwidth->rate_up, $bandwidth->rate_up_unit);
                // $MikroRate = "{$bandwidth->rate_down}{$bandwidth->rate_down_unit}/{$bandwidth->rate_up}{$bandwidth->rate_up_unit}";
                if ($customer->is_override) {
                    $MikroRate = "{$customer->override_download}{$customer->override_download_unit}/{$customer->override_upload}{$customer->override_upload_unit}";
                } else {
                    $MikroRate = "{$bandwidth->rate_down}{$bandwidth->rate_down_unit}/{$bandwidth->rate_up}{$bandwidth->rate_up_unit}";
                }
                $createdBy = Auth::user()->creatorId();
                $shared = $package->shared_users;

                DB::table('radcheck')->insert([
                    ['username' => $child->username, 'attribute' => 'Cleartext-Password', 'op' => ':=', 'value' => $password, 'created_by' => $createdBy]
                ]);
                DB::table('radreply')->insert([
                    ['username' => $child->username, 'attribute' => 'Mikrotik-Rate-Limit', 'op' => ':=', 'value' => $MikroRate, 'created_by' => $createdBy]
                ]);
                // Assign the package group
                DB::table('radusergroup')->insert([
                    'username'  => $child->username,
                    'groupname' => $group_name,
                    'priority'  => 1,
                    'created_by' => $createdBy,
                ]);
            }
            
            return redirect()->route('customer.show', ['customer' => encrypt($parent->id)])
                ->with('success', "Child account created: Username: $childUsername, Password: $password");
            
        } catch (\Exception $e) {
            \Log::error('Error creating child account: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error creating child account: ' . $e->getMessage());
        }
    }

    public function overridePackage(Request $request, $id)
    {
        $id = \Crypt::decrypt($id); // Decrypt the encrypted ID
        $request->validate([
            'override_download' => 'required_if:is_override,on|nullable|integer|min:1',
            'override_download_unit' => 'required_if:is_override,on|nullable|in:K,M,G',
            'override_upload' => 'required_if:is_override,on|nullable|integer|min:1',
            'override_upload_unit' => 'required_if:is_override,on|nullable|in:K,M,G',
            'is_override' => 'nullable',
        ]);

        $customer = Customer::findOrFail($id);
        $createdBy = Auth::user()->creatorId();
        
        // Update the override settings
        $customer->override_download = $request->override_download;
        $customer->override_download_unit = $request->override_download_unit;
        $customer->override_upload = $request->override_upload;
        $customer->override_upload_unit = $request->override_upload_unit;
        $customer->is_override = $request->has('is_override');
        
        $customer->save();

        // Get the username to use (fallback to account if username is null)
        $radiusUsername = $customer->username ?? $customer->account;

        // If override is enabled, update RADIUS settings
        if ($customer->is_override) {
            $MikroRate = "{$customer->override_download}{$customer->override_download_unit}/{$customer->override_upload}{$customer->override_upload_unit}";
            
            // Update radcheck table
            DB::table('radcheck')
                ->where('username', $radiusUsername)
                ->where('created_by', $createdBy)
                ->where('attribute', 'Cleartext-Password')
                ->delete();

            DB::table('radcheck')->insert([
                'username' => $radiusUsername,
                'attribute' => 'Cleartext-Password',
                'op' => ':=',
                'value' => $customer->password,
                'created_by' => $createdBy
            ]);
            
            // First, delete existing entries
            DB::table('radreply')
                ->where('username', $radiusUsername)
                ->where('created_by', $createdBy)
                ->where('attribute', 'Mikrotik-Rate-Limit')
                ->delete();
            
            // Insert new rate limit
            DB::table('radreply')->insert([
                'username' => $radiusUsername,
                'attribute' => 'Mikrotik-Rate-Limit',
                'op' => ':=',
                'value' => $MikroRate,
                'created_by' => $createdBy
            ]);

            // Update radusergroup table
            // $group_name = $customer->status === 'off' ? 'Expired_Plan' : 'package_' . $customer->package_id;
            // DB::table('radusergroup')
            //     ->where('username', $radiusUsername)
            //     ->where('created_by', $createdBy)
            //     ->delete();

            // DB::table('radusergroup')->insert([
            //     'username' => $radiusUsername,
            //     'groupname' => $group_name,
            //     'priority' => 1,
            //     'created_by' => $createdBy
            // ]);

            // Check if user is online and send CoA request
            $activeSession = DB::table('radacct')
                ->where('username', $radiusUsername)
                ->where('created_by', $createdBy)
                ->whereNull('acctstoptime')
                ->orderBy('acctstarttime', 'desc')
                ->first();

            if ($activeSession) {
                $nasObj = DB::table('nas')
                    ->where('nasname', $activeSession->nasipaddress)
                    ->first();

                if ($nasObj) {
                    $attributes = [
                        'acctSessionID' => $activeSession->acctsessionid,
                        'framedIPAddress' => $activeSession->framedipaddress,
                    ];

                    CustomHelper::sendCoA($nasObj, $customer, $attributes, $MikroRate);
                }
            }
        } else {
            // If override is disabled, restore original package settings
            $package = Package::find($customer->package_id);
            if ($package) {
                $bandwidth = Bandwidth::where('package_id', $package->id)->first();
                if ($bandwidth) {
                    $MikroRate = "{$bandwidth->rate_down}{$bandwidth->rate_down_unit}/{$bandwidth->rate_up}{$bandwidth->rate_up_unit}";
                    $group_name = $customer->status === 'off' ? 'Expired_Plan' : 'package_' . $package->id;
                    
                    // Update radcheck table
                    DB::table('radcheck')
                        ->where('username', $radiusUsername)
                        ->where('created_by', $createdBy)
                        ->where('attribute', 'Cleartext-Password')
                        ->delete();

                    DB::table('radcheck')->insert([
                        'username' => $radiusUsername,
                        'attribute' => 'Cleartext-Password',
                        'op' => ':=',
                        'value' => $customer->password,
                        'created_by' => $createdBy
                    ]);
                    
                    // Delete existing entries
                    DB::table('radreply')
                        ->where('username', $radiusUsername)
                        ->where('created_by', $createdBy)
                        ->where('attribute', 'Mikrotik-Rate-Limit')
                        ->delete();
                    
                    // Insert new rate limit
                    DB::table('radreply')->insert([
                        'username' => $radiusUsername,
                        'attribute' => 'Mikrotik-Rate-Limit',
                        'op' => ':=',
                        'value' => $MikroRate,
                        'created_by' => $createdBy
                    ]);

                    // Update radusergroup table
                    DB::table('radusergroup')
                        ->where('username', $radiusUsername)
                        ->where('created_by', $createdBy)
                        ->delete();

                    DB::table('radusergroup')->insert([
                        'username' => $radiusUsername,
                        'groupname' => $group_name,
                        'priority' => 1,
                        'created_by' => $createdBy
                    ]);

                    // Check if user is online and send CoA request
                    $activeSession = DB::table('radacct')
                        ->where('username', $radiusUsername)
                        ->where('created_by', $createdBy)
                        ->whereNull('acctstoptime')
                        ->orderBy('acctstarttime', 'desc')
                        ->first();

                    if ($activeSession) {
                        $nasObj = DB::table('nas')
                            ->where('nasname', $activeSession->nasipaddress)
                            ->first();

                        if ($nasObj) {
                            $attributes = [
                                'acctSessionID' => $activeSession->acctsessionid,
                                'framedIPAddress' => $activeSession->framedipaddress,
                            ];

                            CustomHelper::sendCoA($nasObj, $customer, $attributes, $MikroRate);
                        }
                    }
                }
            }
        }

        return redirect()->route('customer.show', ['customer' => encrypt($id)])
            ->with('success', __('Package override settings updated successfully.'));
    }
}
