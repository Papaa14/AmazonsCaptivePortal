<?php

namespace App\Http\Controllers\Api\Captive;

use App\Models\HotspotUsers;
use App\Models\HotspotPackage;
use App\Models\User;
use App\Services\OtpService;
use App\Services\StkPushService;
use App\Services\SafaricomStkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
//log



class CaptiveController extends Controller
{
    protected $otpService;

    protected $safaricomStkService;
    protected $stkPushService;

    public function __construct(OtpService $otpService, StkPushService $stkPushService, SafaricomStkService $safaricomStkService)
    {
        $this->otpService = $otpService;
        $this->stkPushService = $stkPushService;
        $this->safaricomStkService = $safaricomStkService;
    }

    /**
     * Generate and send an OTP to the user's phone number.
     */
    public function getUserInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => ['required', 'string', 'regex:/^(254\d{9}|0[17]\d{8})$/'],
        ]);



        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $inputPhone = $request->input('phone_number');

        // Normalize phone number to 254 format
        if (str_starts_with($inputPhone, '0')) {
            $phoneNumber = '254' . substr($inputPhone, 1);
        } else {
            $phoneNumber = $inputPhone;
        }

        $otp = rand(1000, 9999);

        HotspotUsers::create([
            'phone_number' => $phoneNumber,
            'otp' => $otp,
            'expires_at' => Carbon::now()->addMinutes(2),
        ]);

          $redirectUrl = 'https://captive.amazonnetworks.co.ke/otp.html';

        if ($this->otpService->sendOtp($phoneNumber, $otp)) {
            return response()->json(['message' => 'OTP sent successfully.', 'redirect_url' => $redirectUrl,]);
        }

          
        return response()->json(['error' => 'Failed to send OTP.'], 500);
    }


    /**
     * Verify the provided OTP.
     */
    public function verifyDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => ['required', 'string', 'regex:/^(254\d{9}|0[17]\d{8})$/'],
            'otp' => ['required', 'string', 'digits:4'],
        ]);



        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $inputPhone = $request->input('phone_number');

        // Normalize phone number to 254 format
        if (str_starts_with($inputPhone, '0')) {
            $phoneNumber = '254' . substr($inputPhone, 1);
        } else {
            $phoneNumber = $inputPhone;
        }

        $otp = $request->input('otp');

        $verificationData = HotspotUsers::where('phone_number', $phoneNumber)
            ->where('otp', $otp)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($verificationData) {
            $verificationData->delete();

            $redirectUrl = 'https://captive.amazonnetworks.co.ke/offers.html';

            return response()->json([
                'message' => 'OTP verified successfully.',
                'redirect_url' => $redirectUrl,
            ]);
        }

        return response()->json(['error' => 'Invalid or expired OTP.'], 401);
    }

    //Get client Details an intellegent one to save bandwidth that will retrieve plus the active-subscribtion & packages
    public function getClientDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => ['required', 'string', 'regex:/^(254\d{9}|0[17]\d{8})$/'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $phoneNumber = $request->input('phone_number');

        // Find the user or create a new one if they don't exist.      
        $user = User::firstOrCreate(
            ['phone_number' => $phoneNumber],
            ['credit_points' => 0.00, 'net_points' => 0] // Default values for new users
        );

        // 1. Get Active Subscriptions       
        // It fetches the subscriptions and their related package details in just two queries.
        $activeSubscriptions = $user->activeSubscriptions()->with('hotspotPackage')->get();

        // 2. Get All Available Packages for Purchase and filter the free one upon time 
        $currentHour = Carbon::now()->hour;
        $isFreePackageTime = $currentHour >= 6 && $currentHour < 8;

        $availablePackages = HotspotPackage::where('is_active', true)
            ->when(!$isFreePackageTime, function ($query) {
                return $query->where('price', '>', 0);
            })
            ->select('id', 'name', 'price', 'device_limit')
            ->orderBy('price', 'asc')
            ->get();



        //filter the active subscriptions response
        $activeSubscriptions = $activeSubscriptions->map(function ($subscription) {
            return [
                'id' => $subscription->id,
                'package_name' => $subscription->hotspotPackage->name,
                'expires_at' => $subscription->expires_at,
                'voucher_code' => $subscription->voucher_code,
                'usage_bytes' => $subscription->usage_bytes,
            ];
        });


        // 3. Assemble the final response payload
        return response()->json([
            'client' => [
                'phone_number' => $user->phone_number,
                'name' => empty($user->name) ? 'N/A' : $user->name,
                'credit_points' => $user->credit_points,
                'net_points' => $user->net_points,
            ],
            'active_subscriptions' => $activeSubscriptions->isEmpty() ? 'No active subscriptions' : $activeSubscriptions,
            'available_packages' => $availablePackages,
        ]);
    }

    //buy package
    //   public function buyPackage(Request $request)
    //     {
    //         $validator = Validator::make($request->all(), [
    //               'phone_number' => ['required', 'string', 'regex:/^(254\d{9}|0[17]\d{8})$/'],
    //             'package_id' => 'required|integer|exists:hotspot_packages,id',
    //             'mac' => 'nullable|string|regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json(['errors' => $validator->errors()], 422);
    //         }

    //         $user = User::firstOrCreate(['phone_number' => $request->phone_number]);
    //         $package = HotspotPackage::findOrFail($request->package_id);

    //         // 1. Create a pending payment record
    //         $internalRef = Str::uuid()->toString(); // Generate a unique reference
    //         $pendingPayment = DB::table('pending_payments')->insertGetId([
    //             'user_id' => $user->id,
    //             'hotspot_package_id' => $package->id,
    //             'internal_ref' => $internalRef,
    //             'created_at' => now(),
    //             'updated_at' => now(),
    //         ]);

    //         // 2. Initiate the STK push via the service
    //         $response = $this->aggregatorStkService->initiatePush($package->price, $user->phone_number, $internalRef);

    //         // 3. Handle the response from the aggregator
    //         if (
    //             !$response || 
    //             !isset($response['data'][0]['payment_reference'])
    //         ) {
    //             DB::table('pending_payments')->where('id', $pendingPayment)->update(['status' => 'failed']);
    //             return response()->json(['error' => $response['errorMessage'] ?? 'Could not initiate payment.'], 500);
    //         }

    //         // 4. Update our pending record with the aggregator's reference
    //         $paymentReference = $response['data'][0]['payment_reference'];
    //         DB::table('pending_payments')->where('id', $pendingPayment)->update(['payment_reference' => $paymentReference]);

    //         return response()->json([
    //             'message' => 'STK Push sent successfully. Please complete on your phone.',
    //             'payment_reference' => $paymentReference, // For the frontend to check status
    //         ], 201);
    //     }
    public function buyPackage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => ['required', 'string', 'regex:/^(254\d{9}|0[17]\d{8})$/'],
            'package_id' => 'required|integer|exists:hotspot_packages,id',
            'mac_address' => 'required|string|regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Normalize phone number before creating user
        $normalizedPhone = '254' . substr(preg_replace('/[^0-9]/', '', $request->phone_number), -9);
        $user = User::firstOrCreate(['phone_number' => $normalizedPhone]);
        $package = HotspotPackage::findOrFail($request->package_id);

        // 1. Create a pending payment record. This logic remains the same.
        $internalRef = Str::uuid()->toString();
        $pendingPaymentId = DB::table('pending_payments')->insertGetId([
            'user_id' => $user->id,
            'hotspot_package_id' => $package->id,
            'internal_ref' => $internalRef,
            // 'payment_reference' is now the CheckoutRequestID
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Initiate the STK push via the NEW service
        // We use the package name as the AccountReference for clarity on the M-Pesa statement
        $response = $this->safaricomStkService->initiatePush($package->price, $user->phone_number, $package->name);

        // 3. Handle the response from the DIRECT API
        // The success indicator is ResponseCode = 0
        if (!$response || !isset($response['ResponseCode']) || $response['ResponseCode'] != '0') {
            DB::table('pending_payments')->where('id', $pendingPaymentId)->update(['status' => 'failed']);
            return response()->json(['error' => $response['errorMessage'] ?? 'Could not initiate M-Pesa payment.'], 500);
        }

        // 4. Update our pending record with the CheckoutRequestID
        $checkoutRequestID = $response['CheckoutRequestID'];
        DB::table('pending_payments')->where('id', $pendingPaymentId)->update(['payment_reference' => $checkoutRequestID]);

        return response()->json([
            'message' => 'STK Push sent successfully. Please enter your M-Pesa PIN.',           
            'confirmation_code' => $checkoutRequestID,
        ], 201);
    }
    public function reconnectWithVoucher(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'voucher_code' => 'required|string|min:5|max:10',
            'mac_address' => 'required|string|regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $voucherCode = $request->input('voucher_code');
        $macAddress = $request->input('mac_address');

        // 1. Find the subscription and eagerly load related data for efficiency
        $subscription = Subscription::where('voucher_code', $voucherCode)
            ->with(['hotspotPackage', 'activeSessions']) // Eager load to prevent extra queries
            ->first();

        // 2. Check if the subscription exists
        if (!$subscription) {
            return response()->json(['error' => 'Invalid voucher code.'], 404);
        }

        // 3. Check if the subscription is still active
        if (Carbon::now()->isAfter($subscription->expires_at)) {
            return response()->json(['error' => 'This subscription has expired.'], 403);
        }

        // 4. THE DEVICE LIMIT LOGIC
        $deviceLimit = $subscription->hotspotPackage->device_limit;
        $activeMacs = $subscription->activeSessions->pluck('mac_address');

        // Scenario A: The device is already registered (a simple reconnect)
        if ($activeMacs->contains($macAddress)) {
            //grant internet access via router's API
            return response()->json(['message' => 'Device reconnected successfully.']);
        }

        // Scenario B: This is a new device trying to connect
        if ($activeMacs->count() >= $deviceLimit) {
            return response()->json([
                'error' => 'Device limit reached for this subscription.',
                'details' => "This package allows {$deviceLimit} device(s)."
            ], 403); // 403 Forbidden is the correct status code here
        }

        // 5. Grant access to the new device
        $subscription->activeSessions()->create(['mac_address' => $macAddress]);

          $redirectUrl = 'https://captive.amazonnetworks.co.ke/connected.html';

        // Here you would grant internet access via your router's API
        return response()->json([
            'message' => 'New device connected successfully.',
            'redirect_url'=>$redirectUrl,
            'devices_used' => $activeMacs->count() + 1,
            'device_limit' => $deviceLimit
        ], 200);
    }
}
