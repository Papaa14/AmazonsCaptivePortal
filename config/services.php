<?php

return [

  /*
  |--------------------------------------------------------------------------
  | Third Party Services
  |--------------------------------------------------------------------------
  |
  | This file is for storing the credentials for third party services such
  | as Mailgun, Postmark, AWS and more. This file provides the de facto
  | location for this type of information, allowing packages to have
  | a conventional file to locate the various service credentials.
  |
  */

  'postmark' => [
    'token' => env('POSTMARK_TOKEN'),
  ],

  'ses' => [
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
  ],

  'slack' => [
    'notifications' => [
      'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
      'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
    ],
  ],

  'aggregator' => [
    'base_url' => env('AGGREGATOR_BASE_URL'),
    'key' => env('AGGREGATOR_API_KEY'),
    'secret' => env('AGGREGATOR_API_SECRET'),
    'party_b' => env('AGGREGATOR_PARTY_B'),
    'callback_url' => env('AGGREGATOR_CALLBACK_URL'),
],

'safaricom' => [
    'env' => env('SAFARICOM_ENV', 'sandbox'),
    'key' => env('SAFARICOM_KEY'),
    'secret' => env('SAFARICOM_SECRET'),
    'shortcode' => env('SAFARICOM_SHORTCODE'),
    'passkey' => env('SAFARICOM_PASSKEY'),
    'callback_url' => env('SAFARICOM_CALLBACK_URL'),
    'transaction_type' => env('SAFARICOM_TRANSACTION_TYPE', 'CustomerPayBillOnline'),
],
];