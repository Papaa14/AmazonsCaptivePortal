<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OtpService
{
    public function sendOtp(string $phone, string $otp)
    {
        $url = 'https://isms.celcomafrica.com/api/services/sendsms/';
        $staffMessage = "Your AMAZONS verification code is: " . $otp;

        $response = Http::post($url, [
            'partnerID' => '470',
            'apikey' => '0ea66bdc4eef2c1f8bf5a6bc5e319fd1',
            'mobile' => $phone,
            'message' => $staffMessage,
            'shortcode' => 'LENS_AMAZON',
            'pass_type' => 'plain',
        ]);

        // You can add logging here to check the response from the API
        Log::info($response->json());

        return $response->successful();
    }
}