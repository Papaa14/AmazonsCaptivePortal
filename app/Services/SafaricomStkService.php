<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SafaricomStkService
{
    protected string $baseUrl;
    protected string $consumerKey;
    protected string $consumerSecret;

    public function __construct()
    {
        $this->consumerKey = config('services.safaricom.key');
        $this->consumerSecret = config('services.safaricom.secret');
        $this->baseUrl = (config('services.safaricom.env') === 'sandbox')
            ? 'https://sandbox.safaricom.co.ke'
            : 'https://api.safaricom.co.ke';
    }

    /**
     * Get a temporary OAuth access token from Safaricom.
     * The token is cached to improve performance.
     */
    private function getAccessToken(): ?string
    {
        // Cache the token for 55 minutes to avoid requesting it on every transaction.
        return Cache::remember('safaricom_access_token', 3300, function () {
            $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
                ->get($this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials');

            if ($response->failed()) {
                Log::error('Failed to get Safaricom access token', ['response' => $response->body()]);
                return null;
            }
            
            return $response->json('access_token');
        });
    }

    /**
     * Initiate an STK Push using the direct Safaricom API.
     */
    public function initiatePush(float $amount, string $phone, string $accountReference): ?array
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return null; // The error is already logged in getAccessToken()
        }

        $shortcode = config('services.safaricom.shortcode');
        $passkey = config('services.safaricom.passkey');
        $callbackUrl = config('services.safaricom.callback_url');
        $transactionType = config('services.safaricom.transaction_type');
        $timestamp = now()->format('YmdHis');
        $password = base64_encode($shortcode . $passkey . $timestamp);

        // Normalize phone number to Safaricom's required format
        $formattedPhone = '254' . substr(preg_replace('/[^0-9]/', '', $phone), -9);

        $payload = [
            'BusinessShortCode' => $shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => $transactionType,
            'Amount' => round($amount),
            'PartyA' => $formattedPhone,
            'PartyB' => $shortcode,
            'PhoneNumber' => $formattedPhone,
            'CallBackURL' => $callbackUrl,
            'AccountReference' => $accountReference,
            'TransactionDesc' => 'Payment for ' . $accountReference,
        ];

        $response = Http::withToken($accessToken)
            ->post($this->baseUrl . '/mpesa/stkpush/v1/processrequest', $payload);

        Log::info('Direct Safaricom STK push response: ', $response->json());
        
        return $response->json();
    }
}