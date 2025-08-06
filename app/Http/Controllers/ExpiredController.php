<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Package;
use App\Models\User;
use App\Models\Nas;
use App\Models\Router;
use App\Models\RouterPackage;
use App\Models\Customer;
use App\Models\MpesaTransaction;
use App\Models\SmsAlert;
use App\Models\Utility;
use Auth;
use Carbon\Carbon;
use App\Helpers\CustomHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExpiredController extends Controller
{
    
    public function expiredPppoePage(Request $request, $nas_ip)
    {
        $xff = $request->header('X-Forwarded-For');
        $clientIp = trim(explode(',', $xff)[0]);
        $radacct = DB::table('radacct')->where('framedipaddress', $clientIp)->where('nasipaddress', $nas_ip)->orderByDesc('acctstarttime')->first();
        $nas = Nas::where('nasname', $nas_ip)->firstOrFail();
        $router = Router::where('ip_address', $nas_ip)->first();
        $createdby = $nas->created_by;
        $company = User::find($createdby);
        
        if ($radacct){
            $customer = Customer::where('username', $radacct->username)->where('created_by', $nas->created_by)->first();
            // $currentPackage = $customer->package_id ? 
            //     Package::find($customer->package_id) : 
            //     Package::where('name_plan', $customer->package)->where('created_by', $nas->created_by)->first();
            if ($customer) {
                $currentPackage = $customer->package_id 
                    ? Package::find($customer->package_id) 
                    : Package::where('name_plan', $customer->package)
                        ->where('created_by', $nas->created_by)
                        ->first();
            } else {
                // handle missing customer (e.g. redirect, return error, or log)
                Log::warning("Customer not found for NAS: {$nas->id}");
                $currentPackage = null;
            }

            $packageIds = RouterPackage::where('router_id', $router->id)->pluck('package_id');
            $packages = Package::with('bandwidth')->whereIn('id', $packageIds)->where('type', 'PPPoE')->where('created_by', $router->created_by)->where('price', '>', $currentPackage->price ?? 0)->get();
            $dataUsage = DB::table('radacct')
            ->where('username', $customer->username)
            ->where('created_by', $customer->created_by)
            ->where('nasipaddress', $nas_ip)
            ->selectRaw('COALESCE(SUM(acctoutputoctets), 0) as download, COALESCE(SUM(acctinputoctets), 0) as upload')
            ->first();

            return view('expired.index', ['customer' => $customer, 'packages' => $packages, 'nas' => $nas, 'dataUsage' => $dataUsage, 'company' => $company, 'cpackage'=>$currentPackage, 'current_package_price' => $currentPackage->price ?? 0]);
        }else{
            $customer = "";
            return view('expired.index',  ['customer' => $customer, 'company' => $company]);
        }
    }

    public function renewPackage(Request $request){
        $rules = [
            'nas_ip'      => 'required',
            'package_id'  => 'required',
            'phone_number'=> 'required',
            'account' => 'required',
            'amount' => 'required'
        ];
    
        $validator = \Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
        }

        $router = Router::where('ip_address', $request->nas_ip)->first();
        if (!$router) {
            return response()->json(['success' => false, 'message' => 'NAS not found']);
        }

        $package = Package::with('bandwidth')->where('id', $request->package_id)->where('created_by', $router->created_by)->where('type', 'PPPoE')->first();
        if (!$package) {
            return response()->json(['success' => false, 'message' => 'Package not found']);
        }

        $user = User::find($router->created_by);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'ISP not found']);
        }

        $phone= $request->phone_number;
        $phone = (substr($phone, 0,1) == '+') ? str_replace('+', '', $phone) : $phone;
        $phone = (substr($phone, 0,1) == '0') ? preg_replace('/^0/', '254', $phone) : $phone;
        $phone = (substr($phone, 0,1) == '7') ? preg_replace('/^7/', '2547', $phone) : $phone;
        $phone = (substr($phone, 0,1) == '1') ? preg_replace('/^1/', '2541', $phone) : $phone;
        $phone = (substr($phone, 0,1) == '0') ? preg_replace('/^01/', '2541', $phone) : $phone;
        $phone = (substr($phone, 0,1) == '0') ? preg_replace('/^07/', '2547', $phone) : $phone;
    
        $customer = Customer::where('account', $request->account)->where('created_by', $router->created_by)->first();
        $cID = $customer->id;

        $mpesaResponse = CustomHelper::fastInitiateSTKPush(
            $request->account, 
            $phone, 
            $request->amount, 
            $router->created_by, 
            'PPPoE'
        );

        $mpesaResponse = (array) $mpesaResponse; 
        $checkoutRequestID = $mpesaResponse['CheckoutRequestID'] ?? null;

        if ($checkoutRequestID) {
            \DB::table('transactions')->insert([
                'checkout_id'  => $checkoutRequestID,
                'status'       => 0,
                'gateway'      => 'MPesa',
                'package_id'      => $package->id,
            ]);
        }
        else {
            \Log::error('CheckoutRequestID is invalid or not found');
        }

        \Log::info("Response for Checkout:", ['CheckoutRequestID' => $checkoutRequestID]);
        return response()->json([
            'success' => true,
            'message' => 'Payment request sent',
            'checkoutRequestID' => $checkoutRequestID,
            'cID' => $cID
        ]);
    }

    public function QueryMpesa(Request $request)
    {
        \Log::info('Incoming request:', $request->all());

        $rules = [
            'ref'        => 'required',
            'nas_ip'     => 'required',
            'package_id' => 'required',
            'account'    => 'required',
            'cID'        => 'required',
            'amount'     => 'required',
        ];

        $validator = \Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            \Log::warning('Validation failed:', $validator->errors()->toArray());
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
        }

        $router = Router::where('ip_address', $request->nas_ip)->first();
        if (!$router) {
            return response()->json(['success' => false, 'message' => 'Router not found']);
        }

        $customer = Customer::find($request->cID);
        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Customer not found']);
        }

        $paymentType = strtolower(trim($customer->service)) === 'hotspot' ? 'Hotspot' : 'PPPoE';

        $result = (array) CustomHelper::fastMpesaQuery($request->ref, $router->created_by, $paymentType);
        \Log::info("STK Query Response:", $result);

        $status = 'unknown';

        // Your original logic: Handle processing state
        if (isset($result['errorCode']) && $result['errorCode'] === '500.001.1001') {
            $errorMessage = strtolower($result['errorMessage'] ?? '');

            if (str_contains($errorMessage, 'still under processing')) {
                $status = 'processing';
            } elseif (str_contains($errorMessage, 'pressed cancel') || str_contains($errorMessage, 'network failure')) {
                $status = 'cancelled';
            } else {
                $status = 'unknown_failure';
            }

            return response()->json([
                'success' => false,
                'status' => $status,
                'message' => $result['errorMessage'] ?? 'Unknown error',
                'errorCode' => $result['errorCode']
            ]);
        } elseif (isset($result['ResultCode'])) {
            switch ((int) $result['ResultCode']) {
                case 0:
                    if (isset($result['ResultCode']) && $result['ResultCode'] == '0') {
                        $status = 'confirmed';

                        $package = Package::with('bandwidth')
                            ->where('id', $request->package_id)
                            ->where('created_by', $router->created_by)
                            ->where('type', $paymentType)
                            ->first();

                        if (!$package) {
                            return response()->json(['success' => false, 'message' => 'Package not found']);
                        }

                        $amount = $request->amount;
                        $isp = (int) $router->created_by;
                        $TransID = "REN-" . strtoupper(Str::random(6));
                        $transactionDate = Carbon::now()->toDateTimeString();
                        $type = "MPESA";

                        register_shutdown_function(function () use (
                            $amount, $package, $customer, $transactionDate, $TransID, $isp, $type
                        ) {
                            CustomHelper::handlePayments($amount, $package, $customer, $transactionDate, $TransID, $isp, $type);
                        });

                    } else {
                        $status = 'invalid_success_response';
                        \Log::warning("ResultCode 0 returned but not a success description", (array)$result);
                    }
                    break;

                case 1032:
                    $status = 'cancelled';
                    break;

                case 1:
                    $status = 'insufficient_funds';
                    break;

                default:
                    $status = 'failed';
                    break;
            }
        }

        return response()->json([
            'success' => $status === 'confirmed',
            'status' => $status,
            'ResultCode' => $result['ResultCode'] ?? null,
            'ResultDesc' => $result['ResultDesc'] ?? $result['errorMessage'] ?? 'Unknown'
        ]);
    }

    public function handleDebt($customer, $payAmount, $package, $packagePrice)
    {
        $owed = abs($customer->balance);
        $payAfterDebt = $payAmount - $owed;

        if ($payAfterDebt < 0) {
            // Only partial debt paid
            $customer->balance += $payAmount;
            $customer->charges -= $payAmount;
            $customer->save();
            return 'Insufficient payment to clear debt.';
        } elseif ($payAfterDebt < $packagePrice && $customer->status == 'off') {
            // Debt cleared, but not enough for package
            $customer->balance = $payAfterDebt;
            $customer->charges = 0;
            $customer->save();
            return 'Debt cleared, but not enough balance to renew package.';
        } elseif ($payAfterDebt >= $packagePrice && $customer->status == 'off') {
            // Debt cleared and customer expired — renew package
            $customer->charges = 0;
            CustomHelper::activateWithDeposit($customer->id);
            $customer->save();
            return 'Debt cleared and package renewed.';
        } else {
            // Debt cleared and customer already active
            $customer->balance = $payAfterDebt;
            $customer->charges = 0;
            $customer->save();
            return 'Debt cleared fully.';
        }
    }

    public function renewPPPoEPackage($customer, $payAmount, $package, $packagePrice)
    {
        if ($payAmount < $packagePrice) {
            $message = 'Insufficient payment for package renewal.';
            return $message;
        }

        $customer->balance -= $packagePrice;
        CustomHelper::activateWithDeposit($customer->id);
        $customer->save();

        return 'Package renewed successfully.';
    }
}
