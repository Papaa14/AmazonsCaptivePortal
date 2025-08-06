<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use App\Models\Order;
use App\Models\Utility;
use App\Helpers\CustomHelper;
use Iankumu\Mpesa\Facades\Mpesa;
// use File;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlanController extends Controller
{
    public function index()
    {
        if(\Auth::user()->can('manage plan'))
        {
            $currentUser = \Auth::user();
            $currentPlan = null;

            if($currentUser->type == 'super admin')
            {
                $plans = Plan::get();
            }
            else
            {
                $plans = Plan::paginate(6);
                if($currentUser->type == 'company' && $currentUser->plan) {
                    $currentPlan = Plan::find($currentUser->plan);
                }
            }
            $arrDuration = [
                'Lifetime' => __('Lifetime'),
                'Customer' => __('Per Customer'),
                'Month' => __('Per Month'),
                'Year' => __('Per Year'),
            ];

            $admin_payment_setting = Utility::getAdminPaymentSetting();

            $companyOrders = Order::where('user_id','=', \Auth::user()->id)->paginate(5, ['*'], 'orders_page');
            $companyPlans = Plan::whereRaw("LOWER(duration) != 'lifetime'")->get();

            return view('plan.index', compact('plans', 'admin_payment_setting', 'currentPlan', 'currentUser', 'arrDuration', 'companyOrders', 'companyPlans'));
        }
        else
        {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }


    public function create()
    {
        if(\Auth::user()->can('create plan'))
        {
            $arrDuration = [
                'Lifetime' => __('Lifetime'),
                'Customer' => __('Per Customer'),
                'Month' => __('Per Month'),
                'Year' => __('Per Year'),
            ];

            return view('plan.create', compact('arrDuration'));
        } else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function store(Request $request)
    {
        log::info($request->all());
        if (\Auth::user()->can('create plan')) {

            $request->validate([
                'name' => 'required|unique:plans',
                'price' => 'required|numeric|min:0',
                'duration' => 'required',
                'max_customers' => 'required|numeric',
                'is_visible' => 'required',
            ]);

            $post = $request->only(['name', 'price', 'duration', 'max_customers', 'is_visible']);
            $post['created_by'] = \Auth::id();

            if (Plan::create($post)) {
                ToastMagic::success('Plan Successfully created.');
                return redirect()->back();
            } else {
                ToastMagic::error('Something went wrong.');
                return redirect()->back();
            }
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function edit($plan_id)
    {
        if(\Auth::user()->can('edit plan'))
        {
            $arrDuration = Plan::$arrDuration;
            $plan        = Plan::find($plan_id);

            return view('plan.edit', compact('plan', 'arrDuration'));
        }
        else
        {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }


    public function update(Request $request, $plan_id)
    {
        if(\Auth::user()->can('edit plan'))
        {
                $plan = Plan::find($plan_id);
                if(!empty($plan))
                {
                    $validator = \Validator::make(
                        $request->all(),
                        [
                            'name' => 'required|unique:plans,name,' . $plan_id,
                            'duration' => function ($attribute, $value, $fail) use ($plan_id) {
                                if ($plan_id != 1 && empty($value)) {
                                    $fail($attribute.' is required.');
                                }
                            },
                            'max_customers' => 'required|numeric',
                            'is_visible' => 'required',
                        ]
                    );

                    if ($validator->fails()) {
                        $messages = $validator->getMessageBag();
                        ToastMagic::error($messages->first());
                        return redirect()->back();
                    }

                    $post = $request->all();

                    if($plan->update($post))
                    {
                        ToastMagic::success('Plan successfully updated.');
                        return redirect()->back();
                    }
                    else
                    {
                        ToastMagic::error('Something is wrong.');
                        return redirect()->back();
                    }
                }
                else
                {
                    ToastMagic::error('Plan not found.');
                    return redirect()->back();
                }
            }

        else
        {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function destroy(Request $request, $id)
    {
        $userPlan = User::where('plan' , $id)->first();
        if($userPlan != null)
        {
            ToastMagic::error('The company has subscribed to this plan, so it cannot be deleted.');
            return redirect()->back();
        }
        $plan = Plan::find($id);
        if($plan->id == $id)
        {
            $plan->delete();
            ToastMagic::success('Plan deleted successfully');
            return redirect()->back();
        }
        else
        {
            ToastMagic::error('Something went wrong');
            return redirect()->back();
        }
    }

    public function show($id)
    {
        return redirect()->route('plans.index');
    }

    public function userPlan(Request $request)
    {
        $objUser = \Auth::user();
        $planID  = \Illuminate\Support\Facades\Crypt::decrypt($request->code);
        $plan    = Plan::find($planID);
        if($plan)
        {
            if($plan->price <= 0)
            {
                $objUser->assignPlan($plan->id);
                ToastMagic::success('Plan successfully activated.');
                return redirect()->route('plans.index');
            }
            else
            {
                ToastMagic::error('Something is wrong.');
                return redirect()->back();
            }
        }
        else
        {
            ToastMagic::error('Plan not found.');
            return redirect()->back();
        }
    }

    public function extraClientsMpesa(Request $request)
    {
        try {
            Log::info($request->all());
            if (preg_match('/^(07|01)(\d{8})$/',  $request->phone, $matches)) {
                $request->merge([
                    'phone' => '254' . substr($matches[0], 1),
                ]);
            }

            $validator = \Validator::make($request->all(), [
                'phone' => ['required', 'regex:/^254(7|1)[0-9]{8}$/'],
                'num_clients' => 'required|integer|min:1',
                'plan_id' => 'required|exists:plans,id',
                'amount' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ]);
            }

            $plan = Plan::find($request->plan_id);
            $user = \Auth::user();
            $customers = $request->num_clients;

            $phone = $request->phone;
            $cID = $user->id;

            // Initialize Mpesa STK Push


            $payment_setting = Utility::getAdminPaymentSetting();

            // Step 2: Get access token with direct cURL
            $consumerKey = $payment_setting['mpesa_key'];
            $consumerSecret = $payment_setting['mpesa_secret'];
            $passkey = $payment_setting['mpesa_passkey'];
            $shortcode = $payment_setting['mpesa_shortcode'];
            $shortcode_type = $payment_setting['mpesa_shortcode_type'];
            $paybill = 5630138;
            $account = $user->company_id;
            if($shortcode_type == "till"){
                $TransType = 'CustomerBuyGoodsOnline';
            }else{
                $TransType = 'CustomerPayBillOnline';
            }

            $access_token = self::getAccessToken($consumerKey, $consumerSecret);

            if (!$access_token) {
                Log::error("Invalid access token response", ['response' => $response]);
                return ['success' => false, 'message' => 'Invalid access token response.'];
            }

            // Step 3: Prepare STK push request
            $stk_url = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
            $PartyA = $phone;
            $PartyB = $paybill;
            $AccountReference = $account;
            $TransactionDesc = 'Payment';
            $Amount = (int)$request->amount;
            $BusinessShortCode = $shortcode;
            $Passkey = $passkey;
            $Timestamp = date("YmdHis", time());
            $Password = base64_encode($BusinessShortCode.$Passkey.$Timestamp);
            $CallBackURL = 'https://app.ekinpay.com/api/sys/system-callback';

            $curl_post_data = [
                'BusinessShortCode' => $BusinessShortCode,
                'Password' => $Password,
                'Timestamp' => $Timestamp,
                'TransactionType' => $TransType,
                'Amount' => $Amount,
                'PartyA' => $PartyA,
                'PartyB' => $PartyB,
                'PhoneNumber' => $PartyA,
                'CallBackURL' => $CallBackURL,
                'AccountReference' => $AccountReference,
                'TransactionDesc' => $TransactionDesc
            ];

            $data_string = json_encode($curl_post_data);

            // Step 4: Make API call with direct cURL
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $stk_url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer '.$access_token
            ]);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);

            $curl_response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            curl_close($curl);

            if ($error || $httpCode >= 400) {
                Log::error("STK push failed", [
                    'error' => $error,
                    'http_code' => $httpCode,
                    'response' => $curl_response
                ]);
                return ['success' => false, 'message' => 'STK push request failed.'];
            }

            $response = json_decode($curl_response);
            $checkoutRequestID = $response->CheckoutRequestID;

            $checkoutRequestID = $response->CheckoutRequestID;

            $txnId = null;
            if ($checkoutRequestID) {
                $txnId = \DB::table('system_transactions')->insertGetId([
                    'checkout'       => $checkoutRequestID,
                    'status'         => 0,
                    'payment_method' => 'MPesa',
                    'plan_id'        => $plan->id,
                    'company_id'     => $user->company_id,
                    'created_by'     => $user->id,
                    'amount'         => $request->amount,
                    'description'    => 'Company Package Renewal',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);

                $orderID = 'EP' . strtoupper(substr(str_replace('.', '', uniqid('', true)), -8));

                \DB::table('orders')->insert([
                    'order_id'        => $orderID,
                    'checkout'        => $checkoutRequestID,
                    'txn_id'          => $txnId,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

            } else {
                \Log::error('CheckoutRequestID is invalid or not found', ['response' => $response]);
            }

            if (is_null($response)) {
                return response()->json([
                    'success'  => false,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment request sent',
                'checkoutRequestID' => $checkoutRequestID,
                'cID' => $cID,
                'txn_id' => $txnId,
                'extras' => $customers,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function checkPaymentStatus(Request $request)
    {
        try {
            $rules = [
            'ref'          => 'required',
            'cID'          => 'required',
            'txn_id'          => 'required',
            'extras'          => 'required'
        ];

        $validator = \Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
        }

        $trxId = $request->ref;
        $txnId = $request->txn_id;
        $payment_setting = Utility::getAdminPaymentSetting();

            $user = \Auth::user();
            $plan = Plan::find($request->plan_id);

            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getAccessToken($consumerKey, $consumerSecret){
        // Direct cURL call for access token
        $tokenUrl = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $tokenUrl);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf8']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_USERPWD, $consumerKey . ':' . $consumerSecret);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error || $httpCode != 200) {
            Log::error("Failed to get access token", [
                'error' => $error,
                'http_code' => $httpCode,
                'response' => $response
            ]);
            return ['success' => false, 'message' => 'Failed to get access token.'];
        }

        $tokenData = json_decode($response, true);
        $access_token = $tokenData['access_token'] ?? null;

        return $access_token;
    }


    public function initiateRenewal(Request $request)
    {
        if (preg_match('/^(07|01)(\d{8})$/',  $request->phone, $matches)) {
            $request->merge([
                'phone' => '254' . substr($matches[0], 1),
            ]);
        }

        $rules = [
            'plan'      => 'required',
            'amount'      => 'required',
            'cID'  => 'required',
            'phone' => ['required', 'regex:/^254(7|1)[0-9]{8}$/'],
        ];

        $validator = \Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            ToastMagic::error('All fields are Required');
        }

        // Plan & Payment details
        $plan = Plan::findOrFail($request->plan);
        $user = User::findOrFail($request->cID);

        $payment_setting = Utility::getAdminPaymentSetting();
        // Log::info($payment_setting);

        // Step 2: Get access token with direct cURL
        $consumerKey = $payment_setting['mpesa_key'];
        $consumerSecret = $payment_setting['mpesa_secret'];
        $passkey = $payment_setting['mpesa_passkey'];
        $shortcode = $payment_setting['mpesa_shortcode'];
        $shortcode_type = $payment_setting['mpesa_shortcode_type'];
        $paybill = 5630138;
        $account = $user->company_id;
        if($shortcode_type == "till"){
            $TransType = 'CustomerBuyGoodsOnline';
        }else{
            $TransType = 'CustomerPayBillOnline';
        }

        $access_token = self::getAccessToken($consumerKey, $consumerSecret);

        if (!$access_token) {
            Log::error("Invalid access token response", ['response' => $response]);
            return ['success' => false, 'message' => 'Invalid access token response.'];
        }

        // Step 3: Prepare STK push request
        $stk_url = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
        $PartyA = $request->phone;
        $PartyB = $paybill;
        $AccountReference = $account;
        $TransactionDesc = 'Payment';
        $Amount = (int)$request->amount;
        $BusinessShortCode = $shortcode;
        $Passkey = $passkey;
        $Timestamp = date("YmdHis", time());
        $Password = base64_encode($BusinessShortCode.$Passkey.$Timestamp);
        $CallBackURL = 'https://app.ekinpay.com/api/sys/system-callback';

        $curl_post_data = [
            'BusinessShortCode' => $BusinessShortCode,
            'Password' => $Password,
            'Timestamp' => $Timestamp,
            'TransactionType' => $TransType,
            'Amount' => $Amount,
            'PartyA' => $PartyA,
            'PartyB' => $PartyB,
            'PhoneNumber' => $PartyA,
            'CallBackURL' => $CallBackURL,
            'AccountReference' => $AccountReference,
            'TransactionDesc' => $TransactionDesc
        ];

        $data_string = json_encode($curl_post_data);

        // Step 4: Make API call with direct cURL
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $stk_url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer '.$access_token
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);

        $curl_response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error || $httpCode >= 400) {
            Log::error("STK push failed", [
                'error' => $error,
                'http_code' => $httpCode,
                'response' => $curl_response
            ]);
            return ['success' => false, 'message' => 'STK push request failed.'];
        }

        $response = json_decode($curl_response);
        $checkoutRequestID = $response->CheckoutRequestID;

        $checkoutRequestID = $response->CheckoutRequestID;

        $txnId = null;
        if ($checkoutRequestID) {
            $txnId = \DB::table('system_transactions')->insertGetId([
                'checkout'       => $checkoutRequestID,
                'status'         => 0,
                'payment_method' => 'MPesa',
                'plan_id'        => $plan->id,
                'company_id'     => $user->company_id,
                'created_by'     => $user->id,
                'amount'         => $request->amount,
                'description'    => 'Company Package Renewal',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            $orderID = 'EP' . strtoupper(substr(str_replace('.', '', uniqid('', true)), -8));

            \DB::table('orders')->insert([
                'order_id'        => $orderID,
                'checkout'        => $checkoutRequestID,
                'txn_id'          => $txnId,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

        } else {
            \Log::error('CheckoutRequestID is invalid or not found', ['response' => $response]);
        }

        if (is_null($response)) {
            return response()->json([
                'success'  => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment request sent',
            'checkoutRequestID' => $checkoutRequestID,
            'cID' => $request->cID,
            'txn_id' => $txnId,
        ]);
    }


    public function verifyRenewal(Request $request)
    {
        // Log::info($request->all());
        $rules = [
            'ref'          => 'required',
            'cID'          => 'required',
            'txn_id'          => 'required'
        ];

        $validator = \Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
        }

        $trxId = $request->ref;
        $txnId = $request->txn_id;
        $payment_setting = Utility::getAdminPaymentSetting();
        // Log::info($payment_setting);

        // Step 2: Get access token with direct cURL
        $consumerKey = $payment_setting['mpesa_key'];
        $consumerSecret = $payment_setting['mpesa_secret'];
        $passkey = $payment_setting['mpesa_passkey'];
        $shortcode = $payment_setting['mpesa_shortcode'];
        $timestamp = Carbon::rawParse('now')->format('YmdHis');
        $password  = base64_encode($shortcode . $passkey . $timestamp);

        $access_token = self::getAccessToken($consumerKey, $consumerSecret);

        $stkQueryData = [
            "BusinessShortCode" => $shortcode,
            "Password"          => $password,
            "Timestamp"         => $timestamp,
            "CheckoutRequestID" => $trxId
        ];
        $url = "https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($stkQueryData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode((string) $response);

        Log::info('STK response for system: ' . json_encode($result));
        // Log::info("STK error for system: {$result->errorCode} - {$result->errorMessage}");

        $status = 'pending';
        $entry =  \DB::table('system_transactions')->where('checkout', $trxId)->first();
        $plan = Plan::findOrFail($entry->plan_id);
        $user = \Auth::user();

        if (is_object($result) && isset($result->errorCode) && $result->errorCode === '500.001.1001') {
            $status = 'processing';
        } elseif (isset($result->ResultCode)) {
            switch ((int) $result->ResultCode) {
                case 0:
                    if (isset($result->ResultCode) && $result->ResultCode == '0') {
                        $status = 'confirmed';

                        $assignPlan = $this->updateExpiry($user->id, $plan->id);
                        if (is_array($assignPlan) && ($assignPlan['is_success'] ?? false)) {
                            Order::where('txn_id', $txnId)->update([
                                'email'           => $user->email,
                                'name'            => $user->name,
                                'plan_name'       => $plan->name,
                                'plan_id'         => $plan->id,
                                'price'           => $plan->price,
                                'price_currency'  => $user->planPrice()['currency'] ?? '',
                                'payment_status'  => 'success',
                                'payment_type'    => 'Mpesa',
                                'user_id'         => $user->id,
                                'updated_at'      => now(),
                            ]);
                            \DB::table('system_transactions')
                                ->where('checkout', $trxId)
                                ->update(['status' => 1]);
                        }
                    } else {
                        $status = 'invalid_success_response';
                        \DB::table('system_transactions')->where('checkout', $trxId)->delete();
                        \DB::table('orders')->where('checkout', $trxId)->delete();
                        \Log::warning("ResultCode 0 returned but not a success description", (array)$result);
                    }
                    break;

                case 1032: // cancelled
                    $status = 'cancelled';
                    \DB::table('orders')->where('checkout', $trxId)->delete();
                    \DB::table('system_transactions')->where('checkout', $trxId)->delete();
                    break;

                case 1: // insufficient funds
                    $status = 'insufficient_funds';
                    \DB::table('orders')->where('checkout', $trxId)->delete();
                    \DB::table('system_transactions')->where('checkout', $trxId)->delete();
                    break;

                default: // other errors
                    $status = 'failed';
                    \DB::table('system_transactions')->where('checkout', $trxId)->delete();
                    \DB::table('orders')->where('checkout', $trxId)->delete();
                    break;
            }
        }

        return response()->json([
            'success' => true,
            'status' => $status,
        ]);
    }

    public function updateExpiry($id, $planId)
    {
        try {
            $user = User::findOrFail($id);
            $expiry = Carbon::parse($user->plan_expire_date);

            $newExpiry = $expiry->isPast()
                ? now()->addMonth()
                : $expiry->copy()->addMonth();

            $user->plan_expire_date = $newExpiry;
            $user->plan = $planId;
            $user->save();

            return ['is_success' => true];
        } catch (\Exception $e) {
            \Log::error('Error updating expiry', ['error' => $e->getMessage()]);
            return ['is_success' => false];
        }
    }

}
