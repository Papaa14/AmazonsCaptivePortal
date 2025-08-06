<?php
namespace App\Http\Controllers;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Utility;
use App\Models\MpesaTransaction;
use Auth;
use App\Helpers\CustomHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SmsAlert;
use Illuminate\Support\Facades\Http;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Devrabiul\ToastMagic\Facades\ToastMagic;

class MpesaController extends Controller
{

    public function mpesaCallback(Request $request)
    {
        $callbackJSONData = file_get_contents('php://input');

        $callbackData = json_decode($callbackJSONData);

        if (!$callbackData || !isset($callbackData->Body->stkCallback)) {
            return response()->json(['error' => 'Invalid callback data'], 400);
        }

        $resultCode = $callbackData->Body->stkCallback->ResultCode;
        $resultDesc = $callbackData->Body->stkCallback->ResultDesc;
        $merchantRequestID = $callbackData->Body->stkCallback->MerchantRequestID;
        $checkoutRequestID = $callbackData->Body->stkCallback->CheckoutRequestID;

        if ($resultCode != 0) {
            return response()->json(['error' => $resultDesc], 400);
        }

        $metadata = $callbackData->Body->stkCallback->CallbackMetadata->Item;

        $amount = null;
        $mpesaReceiptNumber = null;
        $balance = null;
        $transactionDate = null;
        $phoneNumber = null;

        foreach ($metadata as $item) {
            if (isset($item->Value)) {
                switch ($item->Name) {
                    case 'Amount':
                        $amount = $item->Value;
                        break;
                    case 'MpesaReceiptNumber':
                        $mpesaReceiptNumber = $item->Value;
                        break;
                    case 'Balance':
                        $balance = $item->Value;
                        break;
                    case 'TransactionDate':
                        $transactionDateValue = $item->Value;
                        $transactionDate = Carbon::createFromFormat('YmdHis', $transactionDateValue);
                        if ($transactionDate === false) {
                            // \Log::error("Invalid TransactionDate format: $transactionDateValue");
                        } else {
                            $transactionDate = $transactionDate->format('Y-m-d H:i:s');
                        }$transactionDate = $item->Value;
                        break;
                    case 'PhoneNumber':
                        $phoneNumber = $item->Value;
                        break;
                }
            } else {
                \Log::warning("CallbackMetadata item without Value: " . json_encode($item));
            }
        }

        if ($mpesaReceiptNumber === null) {
            // ToastMagic::error('M-Pesa Receipt Number not found');
            return response()->json([]);
        }

        DB::table('transactions')
            ->where('checkout_id', $checkoutRequestID)
            ->update([
                'status' => 1,
                'phone' => $phoneNumber,
                'date' => $transactionDate,
                'mpesa_code' => $mpesaReceiptNumber,
            ]);
    }
    
    public static function getPaymentCreds()
    {

        $companySettings =  Utility::getCompanyPaymentSetting(\Auth::user()->creatorId());

        $paymentDetails = [
            'key'       => $companySettings['mpesa_key'],
            'secret'    => $companySettings['mpesa_secret'],
            'shortcode' => $companySettings['mpesa_shortcode'],
            'passkey'   => $companySettings['mpesa_passkey']
        ];

        return $paymentDetails;
    }
public static function getMpesaAccessToken($consumerKey, $consumerSecret)
    {
    
        $response = Http::withBasicAuth($consumerKey, $consumerSecret)
            ->withHeaders([
                'Content-Type' => 'application/json; charset=utf8',
            ])
            ->withoutVerifying()
            ->timeout(60)
            ->get('https://api.safaricom.co.ke/oauth/v1/generate', [
                'grant_type' => 'client_credentials',
            ]);

        if ($response->successful()) {
            $result = $response->json();
            return $result['access_token'] ?? null;
        } else {
            throw new \Exception('Failed to retrieve access token: ' . $response->body());
        }
    }

    public function RegisterUrl()
    {
        $settings = self::getPaymentCreds();
        $consumerKey =  $settings['key'];
        $consumerSecret = $settings['secret'];

        $access_token = self::getMpesaAccessToken($consumerKey, $consumerSecret);
        $isp = Auth::user()->creatorId();

        if ($access_token == null) {
            Log::warning("Failed to generate access token");
            exit;
        } else {
            $confirmationUrl = 'https://app.ekinpay.com/api/' . $isp . '/hs/confirmation';
            $validationUrl = 'https://app.ekinpay.com/api/' . $isp . '/hs/validation';
            $BusinessShortCode = $settings['shortcode'];
            $registerurl = 'https://api.safaricom.co.ke/mpesa/c2b/v2/registerurl';

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $registerurl);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type:application/json',
                'Authorization:Bearer ' . $access_token
            ));
            $data = array(
                'ShortCode' => $BusinessShortCode,
                'ResponseType' => 'Completed',
                'ConfirmationURL' => $confirmationUrl,
                'ValidationURL' => $validationUrl
            );
            $data_string = json_encode($data);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
            $curl_response = curl_exec($curl);
            $data = json_decode($curl_response);

            if (isset($data->ResponseCode) && $data->ResponseCode == 0) {
                return redirect()->back()->with('success', "M-Pesa C2B URL registered successfully");
            } else {
                return redirect()->back()->with('error', "M-Pesa C2B URL registration failed");
            }
        }
    }

    public function handleConfirmation(Request $request, $isp)
    {
        Log::info('M-Pesa Confirmation Callback started', $request->all());
        // Save the raw response to a log file
        $mpesaResponse = file_get_contents('php://input');

        $content = json_decode($mpesaResponse);

        // Extract M-Pesa transaction details
        $TransactionType = $content->TransactionType;
        $TransID = $content->TransID;
        $TransTime = $content->TransTime;
        $TransAmount = $content->TransAmount;
        $BusinessShortCode = $content->BusinessShortCode;
        $BillRefNo = $content->BillRefNumber;
        $OrgAccountBalance = $content->OrgAccountBalance;
        $MSISDN = $content->MSISDN;
        $FirstName = $content->FirstName;

        $BillRefNumber = strtoupper($BillRefNo);
        $normalizedBillRefNumber = $BillRefNumber;

        if (preg_match('/^(?:254|0)(7\d{8}|1\d{8})$/', $BillRefNumber, $matches)) {
            // Remove leading 0 if present and prepend 254
            $numberPart = $matches[1];
            $normalizedBillRefNumber = '254' . $numberPart;
        }

        // Store the transaction
        $this->storeTransaction($TransactionType, $TransID, $TransTime, $TransAmount, $BusinessShortCode, $normalizedBillRefNumber, $OrgAccountBalance, $MSISDN, $FirstName, $isp);

        DB::beginTransaction();
        try {
            $isp = (int) $isp;
            $customer = Customer::where(function ($query) use ($normalizedBillRefNumber) {
                $query->where('account', $normalizedBillRefNumber)
                    ->orWhere('contact', $normalizedBillRefNumber);
            })
            ->where('service', 'PPPoE')
            ->where('created_by', $isp)
            ->first();

            if ($customer) {
                Log::info('User found', ['user' => $customer->toArray()]);
                $this->processCustomerPayment($customer, $TransAmount, $isp, $normalizedBillRefNumber, $TransID, $TransTime);
                DB::commit();
            } else {
                Log::info('User not found');
                DB::rollBack();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing payment', ['error' => $e->getMessage()]);
        }
        return response()->json(['status' => 'success']);
    }

    protected function processCustomerPayment($customer, $payAmount, $isp, $normalizedBillRefNumber, $TransID,  $TransTime)
    {
        $transactionDate = Carbon::createFromFormat('YmdHis', $TransTime);
        $isp = (int) $isp;
        $package = $customer->package_id ? 
            Package::with('bandwidth')->find($customer->package_id) : 
            Package::with('bandwidth')
                ->where('name_plan', $customer->package)
                ->where('created_by', $isp)
                ->where('type', 'PPPoE')
                ->firstOrFail();

        $type = "MPESA";
        $amount = $payAmount;
        CustomHelper::handlePayments($amount, $package, $customer, $transactionDate, $TransID, $isp, $type);

        $transaction = MpesaTransaction::where('TransID', $TransID)
            ->where('created_by', $isp)
            ->first();

        $transaction->status = true;
        $transaction->customer = $customer->account;
        $transaction->save();
    }

    public function handleValidation(Request $request, $isp)
    {
        header("Content-Type: application/json");
        $mpesaResponse = file_get_contents('php://input');
        $logFile = "C2bValidationResponse.txt";
        $log = fopen($logFile, "a");
        fwrite($log, $mpesaResponse);
        fclose($log);
    }

    public function storeTransaction($TransactionType, $TransID, $TransTime, $TransAmount, $BusinessShortCode, $normalizedBillRefNumber, $OrgAccountBalance, $MSISDN, $FirstName, $isp)
    {
        $transactionDate = Carbon::createFromFormat('YmdHis', $TransTime);
        DB::beginTransaction();
        try {
            $transaction = new MpesaTransaction();
            $transaction->created_by = $isp;
            $transaction->TransID = $TransID;
            $transaction->TransactionType = $TransactionType;
            $transaction->TransTime = $transactionDate;
            $transaction->TransAmount = $TransAmount;
            $transaction->BusinessShortCode = $BusinessShortCode;
            $transaction->BillRefNumber = $normalizedBillRefNumber;
            $transaction->OrgAccountBalance = $OrgAccountBalance;
            $transaction->MSISDN = $MSISDN;
            $transaction->FirstName = $FirstName;
            $transaction->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to add M-Pesa records: " . $e->getMessage());
        }
    }
    public function systemCallback(Request $request)
    {
        $callbackJSONData = file_get_contents('php://input');

        $callbackData = json_decode($callbackJSONData);

        if (!$callbackData || !isset($callbackData->Body->stkCallback)) {
            return response()->json(['error' => 'Invalid callback data'], 400);
        }

        $resultCode = $callbackData->Body->stkCallback->ResultCode;
        $resultDesc = $callbackData->Body->stkCallback->ResultDesc;
        $merchantRequestID = $callbackData->Body->stkCallback->MerchantRequestID;
        $checkoutRequestID = $callbackData->Body->stkCallback->CheckoutRequestID;

        if ($resultCode != 0) {
            return response()->json(['error' => $resultDesc], 400);
        }

        $metadata = $callbackData->Body->stkCallback->CallbackMetadata->Item;

        $amount = null;
        $mpesaReceiptNumber = null;
        $balance = null;
        $transactionDate = null;
        $phoneNumber = null;

        foreach ($metadata as $item) {
            if (isset($item->Value)) {
                switch ($item->Name) {
                    case 'Amount':
                        $amount = $item->Value;
                        break;
                    case 'MpesaReceiptNumber':
                        $mpesaReceiptNumber = $item->Value;
                        break;
                    case 'Balance':
                        $balance = $item->Value;
                        break;
                    case 'TransactionDate':
                        $transactionDateValue = $item->Value;
                        $transactionDate = Carbon::createFromFormat('YmdHis', $transactionDateValue);
                        if ($transactionDate === false) {
                            // \Log::error("Invalid TransactionDate format: $transactionDateValue");
                        } else {
                            $transactionDate = $transactionDate->format('Y-m-d H:i:s');
                        }$transactionDate = $item->Value;
                        break;
                    case 'PhoneNumber':
                        $phoneNumber = $item->Value;
                        break;
                }
            } else {
                // \Log::warning("CallbackMetadata item without Value: " . json_encode($item));
            }
        }

        if ($mpesaReceiptNumber === null) {
            // \Log::error('M-Pesa Receipt Number not found in callback data');
            return response()->json(['error' => 'M-Pesa Receipt Number not found'], 400);
        }

        DB::table('system_transactions')
            ->where('checkout', $checkoutRequestID)
            ->update([
                'status' => 1,
                'phone' => $phoneNumber,
                'paid_at' => $transactionDate,
                'reference' => $mpesaReceiptNumber,
        ]);
        DB::table('orders')
            ->where('checkout', $checkoutRequestID)
            ->update([
                'receipt' => $mpesaReceiptNumber,
        ]);
    }
}
