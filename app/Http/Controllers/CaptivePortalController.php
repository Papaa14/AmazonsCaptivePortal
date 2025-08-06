<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Package;
use App\Models\Bandwidth;
use App\Models\Voucher;
use App\Models\Utility;
use App\Models\User;
use App\Models\Nas;
use App\Models\Router;
use App\Models\RouterPackage;
use App\Models\Plan;
use App\Models\Customer;
use App\Models\Transaction;
use Auth;
use Carbon\Carbon;
use App\Helpers\CustomHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class CaptivePortalController extends Controller
{

    public function showLogin(Request $request, $nas_ip, $mac = null)
    {
        // Log::info('Incoming Request:', $request->all());

        if (!$nas_ip) {
            return abort(400, 'NAS IP is required');
        }

        $router = Router::where('ip_address', $nas_ip)->first();
        if (!$router) {
            return abort(404, 'Router not found');
        }
        $createdby = $router->created_by;

        $packageIds = RouterPackage::where('router_id', $router->id)->pluck('package_id');
        $packages = Package::with('bandwidth')
            ->whereIn('id', $packageIds)
            ->where('created_by', $createdby)
            ->where('type', 'Hotspot')
            ->get();

        $randomBytes = random_bytes(5);
        $cookieValue = strtoupper(bin2hex($randomBytes));
        $minutesIn30Days = 30 * 24 * 60;
        $cookie = cookie('unique_cookie', $cookieValue, $minutesIn30Days);

        $company = User::find($createdby);
        $settings = DB::table('settings')
            ->where('created_by', $createdby)
            ->pluck('value', 'name')
            ->toArray();

        $CustomerCare = $settings['company_telephone'] ?? null;
            //  Store values from the request OR session (NO FALLBACK)
            if ($request->query('loginLink')) {
                session(['hotspot_login.loginLink' => $request->query('loginLink')]);
            }
            $loginLink = session('hotspot_login.loginLink');

    if (!$loginLink) {
        return abort(400, 'Missing loginLink');
    }
        $chapID = $request->query('chapID') ?? session('hotspot_login.chapID');
        $chapChallenge = $request->query('chapChallenge') ?? session('hotspot_login.chapChallenge');

        // Ensure loginLink is present, otherwise abort
        if (!$loginLink) {
            // Log::error('Missing loginLink');
            return abort(400, 'Missing loginLink');
        }
        $mac = $mac ?? $request->query('mac') ?? $request->input('mac');
        // Log::info('Resolved MAC Address:', ['mac' => $mac]);

        $invalidMacs = ['$(mac)', null, '', 'undefined'];
        if (in_array($mac, $invalidMacs, true)) {
            // Log::error('Invalid MAC address detected', ['mac' => $mac]);
            return abort(403, 'Invalid MAC address. Please try again.');
        }

        if ($request->getQueryString()) {
            return redirect()->route('captive.showLogin', ['nas_ip' => $nas_ip, 'mac' => $mac]);
        }


        session([
            'hotspot_login' => [
                'nas_ip' => $nas_ip,
                'mac' => $mac,
                'cookie' => $cookie,
                'ip' => $request->query('ip'),
                'loginLink' => $loginLink,
                'chapID' => $chapID,
                'chapChallenge' => $chapChallenge,
            ],
            'packages' => $packages,
            'company' => $company,
            'CustomerCare' => $CustomerCare
        ]);
        return view('captive.login', compact('nas_ip', 'cookieValue', 'packages', 'company', 'mac', 'loginLink', 'chapID', 'chapChallenge', 'CustomerCare'));
    }

    public function processCustomer(Request $request)
    {
        $rules = [
            'nas_ip'      => 'required',
            'cookie'      => 'required',
            'package_id'  => 'required',
            'phone_number'=> 'required',
            'mac_address' => 'required'
        ];

        $validator = \Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
        }

        $router = Router::where('ip_address', $request->nas_ip)->first();
        if (!$router) {
            return response()->json(['success' => false, 'message' => 'NAS not found']);
        }
        // Log::info('Session after setting MAC' . $request->cookie);
        $package = Package::with('bandwidth')
            ->where('id', $request->package_id)
            ->where('created_by', $router->created_by)
            ->where('type', 'Hotspot')
            ->first();

        $total_data = null;
        if ((int)$package->is_limited === 1 && $package->data_limit && $package->data_unit) {
            $total_data = $this->convertDataLimit($package->data_limit, $package->data_unit);
        }

        if (!$package) {
            return response()->json(['success' => false, 'message' => 'Package not found']);
        }

        $user = User::find($router->created_by);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'ISP not found']);
        }

        $creator = User::find($user->creatorId());
        $plan = Plan::find($user->plan);

        Log::info('Max Customer Allowed:' . $plan->max_customers);
        
        if ($plan->max_customers != -1) {
            $activeSession = DB::table('radacct')
                ->where('created_by', $creator)
                ->whereNull('acctstoptime')
                ->count();

            if ($activeSession >= $plan->max_customers) {
                return response()->json(['success' => false, 'message' => 'Your ISP Customer Limit Reached']);
            }
        }

        // Log::info('Max Customer Allowed Not Reached:' . $plan->max_customers . 'online Customers' . $activeSession );

        $phone= $request->phone_number;
        $phone = (substr($phone, 0,1) == '+') ? str_replace('+', '', $phone) : $phone;
        $phone = (substr($phone, 0,1) == '0') ? preg_replace('/^0/', '254', $phone) : $phone;
        $phone = (substr($phone, 0,1) == '7') ? preg_replace('/^7/', '2547', $phone) : $phone;
        $phone = (substr($phone, 0,1) == '1') ? preg_replace('/^1/', '2541', $phone) : $phone;
        $phone = (substr($phone, 0,1) == '0') ? preg_replace('/^01/', '2541', $phone) : $phone;
        $phone = (substr($phone, 0,1) == '0') ? preg_replace('/^07/', '2547', $phone) : $phone;

        $customer = Customer::where('username', $request->mac_address)
            ->where('created_by', $router->created_by)
            ->first();


        if ($customer) {
            $customer->fullname     = $phone ?? $customer->fullname;
            $customer->contact      = $phone;
            $customer->package      = $package->name_plan;
            $customer->package_id   = $package->id;
            $customer->used_data    = 0;
            $customer->total_data   = $total_data;
            $customer->is_active    = 1 ?? $customer->is_active;
            $customer->auto_renewal = 1 ?? $customer->auto_renewal;
            $customer->account      = $request->cookie ?? $customer->account;
            // $customer->expiry       = $newExpiry->toDateTimeString();
            $customer->save();
        } else {
            $customer = new Customer();
            $customer->mac_address  = $request->mac_address;
            $customer->fullname     = $phone;
            $customer->username     = $request->mac_address;
            $customer->account      = $request->cookie;
            $customer->password     = $request->mac_address;
            $customer->contact      = $phone;
            $customer->created_by   = $router->created_by;
            $customer->service      = 'Hotspot';
            $customer->auto_renewal = 1;
            $customer->is_active    = 1;
            $customer->package      = $package->name_plan;
            $customer->package_id   = $package->id;
            $customer->used_data    = 0;
            $customer->total_data   = $total_data;
            // $customer->expiry       = $newExpiry->toDateTimeString();
            $customer->save();
        }

        $cID = $customer->id;

        $mpesaResponse = CustomHelper::fastInitiateSTKPush(
            $request->mac_address,
            $phone,
            $package->price,
            $router->created_by,
            'Hotspot'
        );

        $mpesaResponse = (array) $mpesaResponse;
        $checkoutRequestID = $mpesaResponse['CheckoutRequestID'] ?? null;

        if ($checkoutRequestID) {
            \DB::table('transactions')->insert([
                'user_id' => $cID,
                'site' => $router->ip_address,
                'checkout_id'  => $checkoutRequestID,
                'status'       => 0,
                'gateway'      => 'MPesa',
                'package_id'      => $package->id,
            ]);
        } else {
            \Log::error('CheckoutRequestID is invalid or not found');
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment request sent',
            'checkoutRequestID' => $checkoutRequestID,
            'cID' => $cID
        ]);


    }

    private function convertDataLimit($data, $unit)
    {
        $multipliers = [
            'mb' => 1048576,
            'gb' => 1073741824,
            'tb' => 1099511627776
        ];

        $unit = strtolower($unit);
        return isset($multipliers[$unit]) ? ($data * $multipliers[$unit]) : 0;
    }

    public function processQueryMpesa(Request $request)
    {
        $rules = [
            'ref'          => 'required',
            'nas_ip'       => 'required',
            'package_id'   => 'required',
            'phone_number' => 'required',
            'mac_address'  => 'required',
            'cID'          => 'required'
        ];

        $validator = \Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
        }

        $router = Router::where('ip_address', $request->nas_ip)->first();
        if (!$router) {
            return response()->json(['success' => false, 'message' => 'NAS not found']);
        }

        $ref = $request->ref;

        $phone = $request->phone_number;
        $phone = ltrim($phone, '+');
        $phone = preg_replace('/^0/', '254', $phone);

        $customer = Customer::find($request->cID);
        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Customer not found']);
        }

        // $mpesastatus = CustomHelper::QueryMpesa($ref, $router->created_by, 'Hotspot');
        // $mpesastatus = CustomHelper::fastMpesaQuery($ref, $router->created_by, 'Hotspot');
        // $mpesastatus = (array) $mpesastatus;
        // Log::info("Response for STK in captive:", (array)  $mpesastatus);

        // $ResultCode = $mpesastatus['ResultCode'] ?? null;
        // $ResultDesc = $mpesastatus['ResultDesc'] ?? null;

        // if ($ResultCode !== "0") {
        //     return response()->json(['success' => false, 'message' => 'Payment failed', 'ResultCode' => $ResultCode, 'ResultDesc' => $ResultDesc]);
        // }

        // CustomHelper::rechargeUser($customer, $request->package_id, $router->created_by, $ref);
        $mpesastatus = CustomHelper::fastMpesaQuery($ref, $router->created_by, 'Hotspot');
        $mpesastatus = (array) $mpesastatus;

        Log::info("[MPESA Check] Response for STK on captive portal:", $mpesastatus);

        $ResultCode = $mpesastatus['ResultCode'] ?? null;
        $ResultDesc = $mpesastatus['ResultDesc'] ?? null;

        if ($ResultCode !== "0") {
            Log::error("[MPESA Error] Payment failed: Code={$ResultCode}, Desc={$ResultDesc}, Ref={$ref}");
            return response()->json(['success' => false, 'message' => 'Payment failed', 'ResultCode' => $ResultCode, 'ResultDesc' => $ResultDesc]);
        }

        Log::info("[MPESA Success] Payment confirmed, proceeding to recharge. Ref={$ref}");
        CustomHelper::rechargeUser($customer, $request->package_id, $router->created_by, $ref);


        return response()->json([
            'success' => true,
            'message' => 'Payment successful',
            'ResultCode' => $ResultCode,
            'ResultDesc' => $ResultDesc
        ]);
    }

    public function checkPaid(Request $request)
    {
        $rules = [
            'nas_ip'      => 'required',
            'cookie'      => 'required',
            'mac_address' => 'required'
        ];

        $validator = \Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'code' => '0'
            ]);
        }

        $nasIp = $request->nas_ip;
        $cookie = $request->cookie;
        $macAddress = $request->mac_address;

        $router = Router::where('ip_address', $nasIp)->first();
        if (!$router) {
            return response()->json([
                'success' => false,
                'message' => 'Router not found',
                'code' => '0'
            ]);
        }
        $createdBy = $router->created_by;

        $customer = Customer::where('account', $cookie)
            ->where('status', 'on')
            ->where('created_by', $createdBy)
            ->whereNotNull('expiry')
            ->where('service', 'Hotspot')
            ->first();

        if (!$customer) {
            // Log::info("No User Account Found");
            return response()->json([
                'success' => false,
                'message' => 'No User Account Found',
                'code' => '0'
            ]);
        }

        if ($customer->used_data >= $customer->total_data) {
            $customer->expiry = Carbon::now();
            $customer->used_data= 0;
            $customer->save();
            Log::info("Data Limit reached, buy new package");

            return response()->json([
                'success' => false,
                'message' => 'Data Limit reached, buy new package',
                'code' => '0'
            ]);
        }

        $package = Package::with('bandwidth')
            ->where('name_plan', $customer->package)
            ->where('created_by', $router->created_by)
            ->where('type', 'Hotspot')
            ->first();

        $activeSession = DB::table('radacct')
            ->where('username', $customer->username)
            ->where('created_by', $customer->created_by)
            ->where('nasipaddress', $nasIp)
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
                CustomHelper::kickOutUsersByRadius($nasObj, $customer, $attributes);
            } else {
                Log::warning("NAS not found for IP: {$activeSession->nasipaddress}");
            }
        }
        // Prepare bandwidth and other details
        $radiusGroup = 'package_' . $package->id;
        $bandwidth = $package->bandwidth;
        $shared = $package->shared_users;
        $down = $this->convertBandwidth($bandwidth->rate_down, $bandwidth->rate_down_unit);
        $up = $this->convertBandwidth($bandwidth->rate_up, $bandwidth->rate_up_unit);
        if ($customer->is_override) {
            $MikroRate = "{$customer->override_download}{$customer->override_download_unit}/{$customer->override_upload}{$customer->override_upload_unit}";
        } else {
            $MikroRate = "{$bandwidth->rate_down}{$bandwidth->rate_down_unit}/{$bandwidth->rate_up}{$bandwidth->rate_up_unit}";
        }
        $createdBy = $router->created_by;

        if ($customer->mac_address == $macAddress) {
            DB::table('radcheck')->where('username', $customer->username)->where('created_by', $createdBy)->delete();
            DB::table('radreply')->where('username', $customer->username)->where('created_by', $createdBy)->delete();
            DB::table('radusergroup')->where('username', $customer->username)->where('created_by', $createdBy)->delete();
            DB::beginTransaction();
            try {

                DB::table('radcheck')->insert([
                    ['username' => $customer->username, 'attribute' => 'Cleartext-Password', 'op' => ':=', 'value' => $customer->password, 'created_by' => $createdBy],
                ]);

                DB::table('radreply')->insert([
                    ['username' => $customer->username, 'attribute' => 'Mikrotik-Rate-Limit', 'op' => ':=', 'value' => $MikroRate, 'created_by' => $createdBy],
                ]);

                DB::table('radusergroup')->insert([
                    'username'   => $customer->username,
                    'groupname'  => $radiusGroup,
                    'priority'   => 1,
                    'created_by' => $createdBy,
                ]);

                CustomHelper::refreshCustomerInRadius($customer);

                DB::commit();
                // Log::info('Active User Account Found. Syncing Mac - Same MAC');
                return response()->json([
                    'success' => true,
                    'message' => 'Active User Account Found. Syncing Mac',
                    'code' => '1'
                ]);
            } catch (\Exception $e) {
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Error syncing MAC address: ' . $e->getMessage(),
                    'code' => '0'
                ]);
            }

        }


        $customer->mac_address  = $request->mac_address;
        $customer->username     = $request->mac_address;
        $customer->password     = $request->mac_address;
        $customer->save();
        DB::beginTransaction();
        try {
            DB::table('radcheck')->where('username', $customer->username)->where('created_by', $createdBy)->delete();
            DB::table('radreply')->where('username', $customer->username)->where('created_by', $createdBy)->delete();
            DB::table('radusergroup')->where('username', $customer->username)->where('created_by', $createdBy)->delete();

            DB::table('radcheck')->insert([
                ['username' => $customer->username, 'attribute' => 'Cleartext-Password', 'op' => ':=', 'value' => $customer->password, 'created_by' => $createdBy],
            ]);

            DB::table('radreply')->insert([
                ['username' => $customer->username, 'attribute' => 'Mikrotik-Rate-Limit', 'op' => ':=', 'value' => $MikroRate, 'created_by' => $createdBy],
            ]);

            DB::table('radusergroup')->insert([
                'username'   => $customer->username,
                'groupname'  => $radiusGroup,
                'priority'   => 1,
                'created_by' => $createdBy,
            ]);

            CustomHelper::refreshCustomerInRadius($customer);

            DB::commit();
            // Log::info('Active User Account Found. Syncing Mac - Different MAC');
            return response()->json([
                'success' => true,
                'message' => 'Active User Account Found. Syncing Mac',
                'code' => '2'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error syncing MAC address: ' . $e->getMessage(),
                'code' => '0'
            ]);
        }
    }

    private function convertBandwidth($rate, $unit)
    {
        $multipliers = ['K' => 1000, 'M' => 1000000, 'G' => 1000000000];
        return isset($multipliers[$unit]) ? ($rate * $multipliers[$unit]) : $rate;
    }



    public function VerifyMpesa($nas_ip = null){

    }

    public function addDevice(Request $request)
    {
        $rules = [
            'nas_ip'      => 'required|ip',
            'code'        => 'required|string',
            'cookie'      => 'required|string',
            'mac_address' => ['required', 'regex:/^([0-9A-Fa-f]{2}[:]){5}([0-9A-Fa-f]{2})$/'],
        ];

        $validator = \Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        DB::beginTransaction();

        try {
            // Retrieve router
            $router = Router::where('ip_address', $request->nas_ip)->first();
            if (!$router) {
                return response()->json(['success' => false, 'message' => 'NAS not found']);
            }

            // Case-insensitive mpesa code match
            $code = strtoupper(trim($request->code));
            $payment = Transaction::whereRaw('UPPER(mpesa_code) = ?', [$code])
                ->where('created_by', $router->created_by)
                ->first();

            if (!$payment) {
                return response()->json(['success' => false, 'message' => 'M-Pesa code not found']);
            }

            $package = Package::find($payment->package_id);
            if (!$package) {
                return response()->json(['success' => false, 'message' => 'Package not found']);
            }

            if ($package->shared_users <= 1) {
                return response()->json(['success' => false, 'message' => 'Package only supports one device']);
            }

            $parent = Customer::find($payment->user_id);
            if (!$parent) {
                return response()->json(['success' => false, 'message' => 'Parent account not found. Please connect first before adding device.']);
            }
            
            if ($parent->expiry <= now()) {
                return response()->json(['success' => false, 'message' => 'Parent account is Already Expired.']);
            }

            $activeChildrenCount = Customer::where('parent_id', $parent->id)
                ->where('status', 'on')
                ->count();

            if ($activeChildrenCount >= ($package->shared_users - 1)) {
                return response()->json(['success' => false, 'message' => 'Device limit reached for this package']);
            }

            $child = new Customer();
            $child->parent_id    = $parent->id;
            $child->mac_address  = $request->mac_address;
            $child->fullname     = $parent->fullname;
            $child->username     = $request->mac_address;
            $child->account      = $request->cookie;
            $child->password     = $request->mac_address;
            $child->contact      = $parent->contact;
            $child->created_by   = $parent->created_by;
            $child->service      = $parent->service;
            $child->auto_renewal = 1;
            $child->is_active    = 1;
            $child->package      = $parent->package;
            $child->package_id   = $parent->package_id;
            $child->total_data   = $parent->total_data;
            $child->expiry       = $parent->expiry;
            $child->status       = $parent->status;
            $child->save();

            // RADIUS Sync
            $bandwidth = Bandwidth::where('package_id', $package->id)->first();
            if (!$bandwidth) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Bandwidth profile not found for this package']);
            }

            $MikroRate = $child->is_override
                ? "{$child->override_download}{$child->override_download_unit}/{$child->override_upload}{$child->override_upload_unit}"
                : "{$bandwidth->rate_down}{$bandwidth->rate_down_unit}/{$bandwidth->rate_up}{$bandwidth->rate_up_unit}";

            $groupName = 'package_' . $package->id;

            // Clean old RADIUS entries
            DB::table('radcheck')->where('username', $child->username)->where('created_by', $router->created_by)->delete();
            DB::table('radreply')->where('username', $child->username)->where('created_by', $router->created_by)->delete();
            DB::table('radusergroup')->where('username', $child->username)->where('created_by', $router->created_by)->delete();

            DB::table('radcheck')->insert([
                'username'    => $child->username,
                'attribute'   => 'Cleartext-Password',
                'op'          => ':=',
                'value'       => $child->password,
                'created_by'  => $router->created_by,
            ]);

            DB::table('radreply')->insert([
                'username'    => $child->username,
                'attribute'   => 'Mikrotik-Rate-Limit',
                'op'          => ':=',
                'value'       => $MikroRate,
                'created_by'  => $router->created_by,
            ]);

            DB::table('radusergroup')->insert([
                'username'    => $child->username,
                'groupname'   => $groupName,
                'priority'    => 1,
                'created_by'  => $router->created_by,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Device added successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ]);
        }
    }
    
    public function reedemVoucher(Request $request)
    {
        // Validate input
        $rules = [
            'nas_ip'      => 'required',
            'phone_number'=> 'required',
            'code'        => 'required',
            'cookie'      => 'required',
            'mac_address' => 'required'
        ];
        $validator = \Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        // Retrieve router
        $router = Router::where('ip_address', $request->nas_ip)->first();
        if (!$router) {
            return response()->json(['success' => false, 'message' => 'NAS not found']);
        }

        // Retrieve voucher by code and ensure it was created by the same ISP
        $voucher = Voucher::where('code', $request->code)
            ->where('created_by', $router->created_by)
            ->first();
        if (!$voucher) {
            return response()->json(['success' => false, 'message' => 'Code not found']);
        }

        // Retrieve package associated with the voucher
        $package = Package::find($voucher->package_id);
        // Log::info('Package ID:', ['package_id' => $voucher->package_id]);
        if (!$package) {
            return response()->json(['success' => false, 'message' => 'Package not found']);
        }

        $total_data = null;
        if ((int)$package->is_limited === 1 && $package->data_limit && $package->data_unit) {
            $total_data = $this->convertDataLimit($package->data_limit, $package->data_unit);
        }

        // Retrieve ISP (User) using router->created_by
        $user = User::find($router->created_by);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'ISP not found']);
        }

        // Retrieve plan details from the creator and check customer limit
        // $creator = User::find($user->creatorId());
        // $plan = Plan::find($creator->plan);
        // if ($plan && $plan->max_customers != -1 && $user->countCustomers() >= $plan->max_customers) {
        //     return response()->json(['success' => false, 'message' => 'Customer limit reached']);
        // }

        // Normalize phone number (this could be extracted to a helper method)
        $phone = $request->phone_number;
        $phone = ltrim($phone, '+');
        if (substr($phone, 0, 1) === '0') {
            $phone = preg_replace('/^0/', '254', $phone);
        }
        if (substr($phone, 0, 1) === '7') {
            $phone = preg_replace('/^7/', '2547', $phone);
        }
        if (substr($phone, 0, 1) === '1') {
            $phone = preg_replace('/^1/', '2541', $phone);
        }
        // You can add further formatting rules here if necessary

        // Retrieve existing customer or create a new one
        $customer = Customer::where('fullname', $phone)
            ->where('mac_address', $request->mac_address)
            ->first();

        if ($customer) {
            $customer->fullname     = $phone ?? $customer->fullname;
            $customer->contact      = $phone;
            $customer->package      = $package->name_plan;
            $customer->is_active    = 1 ?? $customer->is_active;
            $customer->auto_renewal = 1 ?? $customer->auto_renewal;
            $customer->account      = $request->cookie ?? $customer->account;
            $customer->used_data    = 0;
            $customer->total_data   = $total_data;
            $customer->save();
        } else {
            $customer = new Customer();
            $customer->fullname     = $phone;
            $customer->username     = $request->mac_address;
            $customer->account      = $request->cookie;
            $customer->password     = $request->mac_address;
            $customer->contact      = $phone;
            $customer->created_by   = $router->created_by;
            $customer->service      = 'Hotspot';
            $customer->auto_renewal = 1;
            $customer->is_active    = 1;
            $customer->used_data    = 0;
            $customer->total_data   = $total_data;
            $customer->mac_address  = $request->mac_address;
            $customer->package      = $package->name_plan;
            $customer->save();
        }

        // If voucher is already used, return an error
        if ($voucher->status === true) {
            return response()->json(['success' => false, 'message' => 'Voucher already used']);
        }

        // Retrieve detailed package with bandwidth; ensure it belongs to the router's creator and is a Hotspot package
        $package = Package::with('bandwidth')
            ->where('id', $voucher->package_id)
            ->where('created_by', $router->created_by)
            ->where('type', 'Hotspot')
            ->first();
        if (!$package) {
            return response()->json(['success' => false, 'message' => 'Package not found']);
        }

        // Convert package validity to seconds
        $timeLimit = match ($package->validity_unit) {
            'Minutes' => $package->validity * 60,
            'Hours'   => $package->validity * 3600,
            'Days'    => $package->validity * 86400,
            'Months'  => $package->validity * 2592000,
            default   => 0,
        };

        // Set customer's expiry based on validity
        $expiry = Carbon::now()->addSeconds($timeLimit);
        $customer->expiry = $expiry->toDateTimeString();
        $customer->status = 'on';
        $customer->save();
        $groupName = 'package_' . $package->id;
        $bandwidth = Bandwidth::where('package_id', $package->id)->first();
        if ($customer->is_override) {
            $MikroRate = "{$customer->override_download}{$customer->override_download_unit}/{$customer->override_upload}{$customer->override_upload_unit}";
        } else {
            $MikroRate = "{$bandwidth->rate_down}{$bandwidth->rate_down_unit}/{$bandwidth->rate_up}{$bandwidth->rate_up_unit}";
        }
        // Ensure no customer's radcheck entry exists to avoid duplicates
        DB::table('radcheck')->where('username', $customer->username)->where('created_by', $router->created_by)->delete();
        DB::table('radreply')->where('username', $customer->username)->where('created_by', $router->created_by)->delete();
        DB::table('radusergroup')->where('username', $customer->username)->where('created_by', $router->created_by)->delete();


        DB::table('radcheck')->insert([
            'username' => $customer->username, 'attribute' => 'Cleartext-Password', 'op' => ':=', 'value' => $customer->password, 'created_by' => $router->created_by,
        ]);
        DB::table('radreply')->insert([
            ['username' => $customer->username, 'attribute' => 'Mikrotik-Rate-Limit', 'op' => ':=', 'value' => $MikroRate, 'created_by' => $router->created_by],
        ]);
        DB::table('radusergroup')->insert([
            'username' => $customer->username, 'groupname' => $groupName, 'priority' => 1, 'created_by' => $router->created_by,
        ]);

        // Calculate new expiry date considering customer's current status and package validity
        $baseTime = Carbon::now();
        if ($package->validity_unit === 'Months') {
            $newExpiry = ($customer->status === 'off' || Carbon::now()->gt($baseTime))
                ? Carbon::now()->addMonths($package->validity)
                : $baseTime->copy()->addMonths($package->validity);
        } else {
            $baseTime = ($customer->status === 'off' || Carbon::now()->gt($baseTime))
                ? Carbon::now()
                : $baseTime;
            $newExpiry = $baseTime->copy()->addSeconds($timeLimit);
        }
        $customer->expiry = $newExpiry->toDateTimeString();
        $customer->status = 'on';
        $customer->save();

        // Mark the voucher as used
        $voucher->status = true;
        $voucher->used_by = $request->mac_address;
        $voucher->save();

        return response()->json([
            'success' => true,
            'message' => 'Voucher Redeemed successful',
        ]);
    }

}
