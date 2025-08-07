<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StkPushService
{
    protected $baseUrl;
    protected $consumerKey;
    protected $consumerSecret;

    public function __construct()
    {
        // It's best practice to store these in your .env file
        $this->baseUrl = config('services.mpesa.base_url', 'https://sandbox.safaricom.co.ke');
        $this->consumerKey = config('services.mpesa.key', 'YOUR_MPESA_CONSUMER_KEY');
        $this->consumerSecret = config('services.mpesa.secret', 'YOUR_MPESA_CONSUMER_SECRET');
    }

    /**
     * Get the M-Pesa API access token.
     */
    private function getAccessToken()
    {
        $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
            ->get($this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials');

        return $response->json()['access_token'] ?? null;
    }

    /**
     * Initiate an STK Push request.
     */
    public function initiatePush(float $amount, string $phoneNumber, string $accountReference, string $transactionDesc)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            Log::error('M-Pesa: Failed to get access token.');
            return null;
        }

        $timestamp = now()->format('YmdHis');
        $businessShortCode = config('services.mpesa.shortcode', '174379');
        $passkey = config('services.mpesa.passkey', 'YOUR_MPESA_PASSKEY');
        $password = base64_encode($businessShortCode . $passkey . $timestamp);

        // Ensure the phone number is in the correct format (254...)
        $formattedPhone = preg_replace('/^(0|\\+254)/', '254', $phoneNumber);

        $response = Http::withToken($accessToken)->post($this->baseUrl . '/mpesa/stkpush/v1/processrequest', [
            'BusinessShortCode' => $businessShortCode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => round($amount), // Amount must be an integer
            'PartyA' => $formattedPhone,
            'PartyB' => $businessShortCode,
            'PhoneNumber' => $formattedPhone,
            'CallBackURL' => route('stk.callback'), // Crucial: The URL M-Pesa will post the result to
            'AccountReference' => $accountReference, // e.g., Company Name
            'TransactionDesc' => $transactionDesc,   // e.g., Package Name
        ]);

        Log::info('STK Push initiated.', ['response' => $response->json()]);

        return $response->json();
    }
}