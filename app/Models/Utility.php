<?php

namespace App\Models;

use App\Mail\CommonEmailTemplate;
use App\Models\TransactionLines;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\GoogleCalendar\Event as GoogleEvent;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Twilio\Rest\Client;
use App\Models\ReferralTransaction;
use App\Models\ReferralSetting;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
// use Google\Service\ServiceControl\Auth;



class Utility extends Model
{
    // private static $settings = NULL;
    private static $getsettings = null;
    private static $getsettingsid = null;
    private static $taxsData = null;
    private static $taxRateData = null;
    private static $taxData = null;
    private static $taxes = null;

    // public static function settings(){
    //     if(self::$settings == null){
    //         self::$settings = self::fetchSetting();
    //     }
    //     return self::$settings;
    // }

    private static $languageSetting = null;

    public static function getSetting()
    {
        if (self::$getsettings == null) {
            $data = DB::table('settings');
            $data = $data->where('created_by', '=', 1)->get();
            if (count($data) == 0) {
                $data = DB::table('settings')->where('created_by', '=', 1)->get();
            }
            self::$getsettings = $data;
        }
        return self::$getsettings;
    }

    public static function getSettingById($id)
    {
        if (self::$getsettingsid == null) {
            $data = DB::table('settings');
            $data = $data->where('created_by', '=', $id)->get();
            if (count($data) == 0) {
                $data = DB::table('settings')->where('created_by', '=', 1)->get();
            }
            self::$getsettingsid = $data;
        }
        return self::$getsettingsid;
    }

    // public static function getSettingByISP($id)
    // {
    //     // if (self::$getsettingsid == null) {
    //         $data = DB::table('settings');
    //         $data = $data->where('created_by', '=', $id)->get();
    //     //     if (count($data) == 0) {
    //     //         $data = DB::table('settings')->where('created_by', '=', 1)->get();
    //     //     }
    //     //     self::$getsettingsid = $data;
    //     // }
    //     return self::$getsettingsid;
    // }

    public static function settings()
    {
        if (\Auth::check()) {
            $data = Utility::getSettingById(\Auth::user()->creatorId());

            //        $data=$data->where('created_by','=',\Auth::user()->creatorId())->get();
            if (count($data) == 0) {
                $data = Utility::getSetting();
            }
        } else {
            $data = Utility::getSetting();
        }

        $settings = [
            "site_currency" => "KES",
            "site_currency_symbol" => "Ksh",
            "site_currency_symbol_position" => "pre",
            "site_date_format" => "M j, Y",
            "site_time_format" => "g:i A",
            "timezone" => 'Africa/Nairobi',
            "company_name" => "",
            "company_address" => "",
            "company_city" => "",
            "company_state" => "",
            "company_zipcode" => "",
            "company_country" => "",
            "company_telephone" => "",
            "invoice_prefix" => "INV",
            "invoice_color" => "ffffff",
            "expense_prefix" => "EXP",
            "customer_prefix" => "CUST",
            "footer_title" => "",
            "footer_notes" => "",
            "invoice_template" => "template1",
            "qr_display" => 'on',
            "invoice_qr_display" => 'on',
            "registration_number" => "",
            "vat_number" => "",
            "default_language" => "en",
            "decimal_number" => "2",
            "tax_type" => "",
            "display_landing_page" => "",
            'title_text' => 'TheFuture Saas',
            'footer_text' => '',
            "slack_webhook" => "",
            "telegram_accestoken" => "",
            "telegram_chatid" => "",
            "enable_signup" => "on",
            "email_verification" => "",
            'cookie_text' => 'We use cookies to ensure that we give you the best experience on our website. If you continue to use this site we will assume that you are happy with it.',
            "company_logo_light" => "logo-light.png",
            "company_logo_dark" => "logo-dark.png",
            "company_favicon" => "favicon.png",
            "cust_theme_bg" => "on",
            "cust_darklayout" => "off",
            "color" => "",
            "SITE_RTL" => "off",
            "storage_setting" => "local",
            "local_storage_validation" => "jpg,jpeg,png,xlsx,xls,csv,pdf",
            "local_storage_max_upload_size" => "2048000",
            "s3_key" => "",
            "s3_secret" => "",
            "s3_region" => "",
            "s3_bucket" => "",
            "s3_url" => "",
            "s3_endpoint" => "",
            "s3_max_upload_size" => "",
            "s3_storage_validation" => "",
            "wasabi_key" => "",
            "wasabi_secret" => "",
            "wasabi_region" => "",
            "wasabi_bucket" => "",
            "wasabi_url" => "",
            "wasabi_root" => "",
            "wasabi_max_upload_size" => "",
            "wasabi_storage_validation" => "",
            "invoice_logo" => "",
            'new_user' => '1',
            'new_client' => '1',
            'new_support_ticket' => '1',
            'lead_assigned' => '1',
            'deal_assigned' => '1',
            'new_award' => '1',
            'customer_invoice_sent' => '1',
            'new_invoice_payment' => '1',
            'new_payment_reminder' => '1',
            'meta_title' => '',
            'meta_desc' => '',
            'meta_image' => '',

            'enable_cookie' => 'on',
            'necessary_cookies' => 'on',
            'cookie_logging' => 'on',
            'cookie_title' => 'We use cookies!',
            'cookie_description' => 'Hi, this website uses essential cookies to ensure its proper operation and tracking cookies to understand how you interact with it',
            'strictly_cookie_title' => 'Strictly necessary cookies',
            'strictly_cookie_description' => 'These cookies are essential for the proper functioning of my website. Without these cookies, the website would not work properly',
            'more_information_description' => 'For any queries in relation to our policy on cookies and your choices, please contact us',
            'contactus_url' => '#',
            "ip_restrict" => "off",

            'mail_driver' => '',
            'mail_host' => '',
            'mail_port' => '',
            'mail_username' => '',
            'mail_password' => '',
            'mail_encryption' => '',
            'mail_from_address' => '',
            'mail_from_name' => '',

            'recaptcha_module' => '',
            'google_recaptcha_key' => '',
            'google_recaptcha_secret' => '',
            'google_recaptcha_version' => '',

            'pusher_app_id' => '',
            'pusher_app_key' => '',
            'pusher_app_secret' => '',
            'pusher_app_cluster' => '',

            'color_flag'=>'false',

            'currency_space'=>'withspace',
            'decimal_separator'=>'dot',
            'thousand_separator'=>'comma',
            'currency_symbol' => 'withcurrencysymbol',
            'float_number' => 'dot',

            'username' => '',
            'user_password' => '',
            'auth_token' => ''
        ];

        foreach ($data as $row) {
            $settings[$row->name] = $row->value;
        }

        config(
            [
                'captcha.secret' => $settings['google_recaptcha_secret'],
                'captcha.sitekey' => $settings['google_recaptcha_key'],
                'options' => [
                    'timeout' => 30,
                ],
            ]
        );

        return $settings;
    }

    public static function settingsById($user_id)
    {
        $data = DB::table('settings')->where('created_by', '=', $user_id)->get();
        // $settings = [];
        $settings = [
            "site_currency" => "KES",
            "site_currency_symbol" => "Ksh",
            "site_currency_symbol_position" => "pre",
            "site_date_format" => "M j, Y",
            "site_time_format" => "g:i A",
            "company_name" => "",
            "company_address" => "",
            "company_city" => "",
            "company_state" => "",
            "company_zipcode" => "",
            "company_country" => "",
            "company_telephone" => "",
            "timezone" => 'Africa/Nairobi',
            "invoice_prefix" => "INV",
            "invoice_color" => "ffffff",
            "expense_prefix" => "EXP",
            "customer_prefix" => "CUST",
            "footer_title" => "",
            "footer_notes" => "",
            "invoice_template" => "template1",
            "qr_display" => 'on',
            "invoice_qr_display" => 'on',
            "registration_number" => "",
            "vat_number" => "",
            "default_language" => "en",
            "decimal_number" => "2",
            "tax_type" => "",
            "display_landing_page" => "",
            'title_text' => 'NexPay',
            'footer_text' => '',
            "slack_webhook" => "",
            "telegram_accestoken" => "",
            "telegram_chatid" => "",
            "enable_signup" => "on",
            "email_verification" => "",
            'cookie_text' => 'We use cookies to ensure that we give you the best experience on our website. If you continue to use this site we will assume that you are happy with it.',
            "company_logo_light" => "logo-light.png",
            "company_logo_dark" => "logo-dark.png",
            "company_favicon" => "favicon.png",
            "cust_theme_bg" => "on",
            "cust_darklayout" => "off",
            "color" => "",
            "SITE_RTL" => "off",
            "invoice_logo" => "",
            "storage_setting" => "local",
            "local_storage_validation" => "jpg,jpeg,png,xlsx,xls,csv,pdf",
            "local_storage_max_upload_size" => "2048000",
            "s3_key" => "",
            "s3_secret" => "",
            "s3_region" => "",
            "s3_bucket" => "",
            "s3_url" => "",
            "s3_endpoint" => "",
            "s3_max_upload_size" => "",
            "s3_storage_validation" => "",
            "wasabi_key" => "",
            "wasabi_secret" => "",
            "wasabi_region" => "",
            "wasabi_bucket" => "",
            "wasabi_url" => "",
            "wasabi_root" => "",
            "wasabi_max_upload_size" => "",
            "wasabi_storage_validation" => "",
            'new_user' => '1',
            'new_client' => '1',
            'new_support_ticket' => '1',
            'lead_assigned' => '1',
            'deal_assigned' => '1',
            'new_award' => '1',
            'customer_invoice_sent' => '1',
            'new_invoice_payment' => '1',
            'new_payment_reminder' => '1',

            'vat_gst_number_switch' => 'off',

            'meta_title' => '',
            'meta_desc' => '',
            'meta_image' => '',

            'enable_cookie' => 'on',
            'necessary_cookies' => 'on',
            'cookie_logging' => 'on',
            'cookie_title' => 'We use cookies!',
            'cookie_description' => 'Hi, this website uses essential cookies to ensure its proper operation and tracking cookies to understand how you interact with it',
            'strictly_cookie_title' => 'Strictly necessary cookies',
            'strictly_cookie_description' => 'These cookies are essential for the proper functioning of my website. Without these cookies, the website would not work properly',
            'more_information_description' => 'For any queries in relation to our policy on cookies and your choices, please contact us',
            'contactus_url' => '#',

            "ip_restrict" => "off",
            "timezone" => '',

            'pusher_app_id' => '',
            'pusher_app_key' => '',
            'pusher_app_secret' => '',
            'pusher_app_cluster' => '',

            'mail_driver' => '',
            'mail_host' => '',
            'mail_port' => '',
            'mail_username' => '',
            'mail_password' => '',
            'mail_encryption' => '',
            'mail_from_address' => '',
            'mail_from_name' => '',

            'currency_space'=>'withspace',
            'decimal_separator'=>'dot',
            'thousand_separator'=>'comma',
            'currency_symbol' => 'withcurrencysymbol',
        ];

        foreach ($data as $row) {
            $settings[$row->name] = $row->value;
        }

        return $settings;
    }


    public static function languages()
    {
        if (self::$languageSetting == null) {
            $languages = Utility::langList();


            if (\Schema::hasTable('languages')) {
                $settings = Utility::settings();
                if (!empty($settings['disable_lang'])) {
                    $disabledlang = explode(',', $settings['disable_lang']);
                    $languages = Language::whereNotIn('code', $disabledlang)->pluck('full_name', 'code');
                } else {
                    $languages = Language::pluck('full_name', 'code');
                }
                self::$languageSetting = $languages;
            }
        }

        return self::$languageSetting;
    }

    public static function getValByName($key)
    {

        $setting = Utility::settings();

        if (!isset($setting[$key]) || empty($setting[$key])) {
            $setting[$key] = '';
        }
        return $setting[$key];
    }

    public static function setEnvironmentValue(array $values)
    {
        $envFile = app()->environmentFilePath();
        $str = file_get_contents($envFile);
        if (count($values) > 0) {
            foreach ($values as $envKey => $envValue) {
                $keyPosition = strpos($str, "{$envKey}=");
                $endOfLinePosition = strpos($str, "\n", $keyPosition);
                $oldLine = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);
                // If key does not exist, add it
                if (!$keyPosition || !$endOfLinePosition || !$oldLine) {
                    $str .= "{$envKey}='{$envValue}'\n";
                } else {
                    $str = str_replace($oldLine, "{$envKey}='{$envValue}'", $str);
                }
            }
        }
        $str = substr($str, 0, -1);
        $str .= "\n";
        if (!file_put_contents($envFile, $str)) {
            return false;
        }

        return true;
    }

    public static function templateData()
    {
        $arr = [];
        $arr['colors'] = [
            '003580',
            '666666',
            '6676ef',
            'f50102',
            'f9b034',
            'fbdd03',
            'c1d82f',
            '37a4e4',
            '8a7966',
            '6a737b',
            '050f2c',
            '0e3666',
            '3baeff',
            '3368e6',
            'b84592',
            'f64f81',
            'f66c5f',
            'fac168',
            '46de98',
            '40c7d0',
            'be0028',
            '2f9f45',
            '371676',
            '52325d',
            '511378',
            '0f3866',
            '48c0b6',
            '297cc0',
            'ffffff',
            '000',
        ];
        $arr['templates'] = [
            "template1" => "New York",
            "template2" => "Toronto",
            "template3" => "Rio",
            "template4" => "London",
            "template5" => "Istanbul",
            "template6" => "Mumbai",
            "template7" => "Hong Kong",
            "template8" => "Tokyo",
            "template9" => "Sydney",
            "template10" => "Paris",
        ];

        return $arr;
    }

    public static function priceFormat($settings, $price)
    {
        $number = explode('.', $price);
        $length = strlen(trim($number[0]));
        $float_number = isset($settings['float_number']) && $settings['float_number'] == 'dot' ? '.' : ',';
        if($length > 3)
        {
            $decimal_separator = $settings['decimal_separator'] == 'dot' ? ',' : ',';
            $thousand_separator = $settings['thousand_separator'] == 'dot' ? '.' : ',';
        }
        else
        {
            $decimal_separator = $settings['decimal_separator'] == 'dot' ? '.' : ',';
            $thousand_separator = $settings['thousand_separator'] == 'dot' ? '.' : ',';
        }
        $currency = $settings['currency_symbol'] == 'withcurrencysymbol' ? $settings['site_currency_symbol']: $settings['site_currency'];
        $decimal_number = $settings['decimal_number'] ? $settings['decimal_number'] : 0;
        $currency_space = $settings['currency_space'];
        $price = number_format($price, $decimal_number, $decimal_separator, $thousand_separator);

        if ($float_number == 'dot') {
            $price = preg_replace('/' . preg_quote($thousand_separator, '/') . '([^' . preg_quote($thousand_separator, '/') . ']*)$/', $float_number . '$1', $price);
        } else {
            $price = preg_replace('/' . preg_quote($decimal_separator, '/') . '([^' . preg_quote($decimal_separator, '/') . ']*)$/', $float_number . '$1', $price);
        }

        return (($settings['site_currency_symbol_position'] == "pre") ? $currency : '') . ($currency_space == 'withspace' ? ' ' : '') . $price . ($currency_space == 'withspace' ? ' ' : '') . (($settings['site_currency_symbol_position'] == "post") ? $currency : '');

    }

    public static function currencySymbol($settings)
    {
        return $settings['site_currency_symbol'];
    }

    public static function dateFormat($settings, $date)
    {
        return date($settings['site_date_format'], strtotime($date));
    }

    public static function timeFormat($settings, $time)
    {
        return date($settings['site_time_format'], strtotime($time));
    }

    public static function invoiceNumberFormat($settings, $number)
    {

        return $settings["invoice_prefix"] . sprintf("%05d", $number);
    }

    public static function customerInvoiceNumberFormat($number)
    {
        $settings = Utility::settings();

        return $settings["invoice_prefix"] . sprintf("%05d", $number);
    }

    public static function billNumberFormat($settings, $number)
    {
        return $settings["bill_prefix"] . sprintf("%05d", $number);
    }


    public static function getTax($tax)
    {
        if (self::$taxes == null) {
            $tax = Tax::find($tax);
            self::$taxes = $tax;
        }

        return self::$taxes;
    }

    public static function tax($taxes)
    {
        // if (self::$taxsData == null) {
        $taxArr = explode(',', $taxes);
        $taxes = [];
        foreach ($taxArr as $tax) {
            $taxes[] = Tax::find($tax);
        }
        // self::$taxsData = $taxes;
        // }
        return $taxes;
    }

    public static $rates;
    public static $data;

    public static function getTaxData()
    {
        $data = [];
        if (self::$rates == null) {
            $rates = Tax::get();
            self::$rates = $rates;
            foreach (self::$rates as $rate) {
                $data[$rate->id]['id'] = $rate->id
                ;
                $data[$rate->id]['name'] = $rate->name;
                $data[$rate->id]['rate'] = $rate->rate;
                $data[$rate->id]['created_by'] = $rate->created_by;
            }
            self::$data = $data;
        }
        return self::$data;
    }

    public static function taxRate($taxRate, $price, $quantity, $discount = 0)
    {

//        return ($taxRate / 100) * (($price-$discount) * $quantity);
        return (($price * $quantity) - $discount) * ($taxRate / 100);
    }

    public static function totalTaxRate($taxes)
    {

        // if (self::$taxRateData == null) {
        $taxArr = explode(',', $taxes);
        $taxRate = 0;
        foreach ($taxArr as $tax) {
            $tax = Tax::find($tax);
            $taxRate += !empty($tax->rate) ? $tax->rate : 0;
        }
        // self::$taxRateData = $taxRate;
        // }
        return $taxRate;
    }

    //start for customer & vendor balance
    public static function userBalance($users, $id, $amount, $type)
    {
        if ($users == 'customer') {
            $user = Customer::find($id);
        } else {
            $user = Vender::find($id);
        }

        if (!empty($user)) {
            if ($type == 'credit') {
                $oldBalance = $user->balance;
                $userBalance = $oldBalance + $amount;
                $user->balance = $userBalance;
                $user->save();
            } elseif ($type == 'debit') {
                $oldBalance = $user->balance;
                $userBalance = $oldBalance - $amount;
                $user->balance = $userBalance;
                $user->save();
            }
        }
    }

    public static function updateUserBalance($users, $id, $amount, $type)
    {
        if ($users == 'customer') {
            $user = Customer::find($id);
        } else {
            $user = Vender::find($id);
        }

        if (!empty($user)) {
            if ($type == 'credit') {
                $oldBalance = $user->balance;
                $userBalance = $oldBalance - $amount;
                $user->balance = $userBalance;
                $user->save();
            } elseif ($type == 'debit') {
                $oldBalance = $user->balance;
                $userBalance = $oldBalance + $amount;
                $user->balance = $userBalance;
                $user->save();
            }
        }
    }

    // get font-color code accourding to bg-color
    public static function hex2rgb($hex)
    {
        $hex = str_replace("#", "", $hex);

        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        $rgb = array(
            $r,
            $g,
            $b,
        );

        return $rgb; // returns an array with the rgb values
    }

    public static function getFontColor($color_code)
    {
        $rgb = self::hex2rgb($color_code);
        $R = $G = $B = $C = $L = $color = '';

        $R = (floor($rgb[0]));
        $G = (floor($rgb[1]));
        $B = (floor($rgb[2]));

        $C = [
            $R / 255,
            $G / 255,
            $B / 255,
        ];

        for ($i = 0; $i < count($C); ++$i) {
            if ($C[$i] <= 0.03928) {
                $C[$i] = $C[$i] / 12.92;
            } else {
                $C[$i] = pow(($C[$i] + 0.055) / 1.055, 2.4);
            }
        }

        $L = 0.2126 * $C[0] + 0.7152 * $C[1] + 0.0722 * $C[2];

        if ($L > 0.179) {
            $color = 'black';
        } else {
            $color = 'white';
        }

        return $color;
    }

    public static function delete_directory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!self::delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }



    public static function errorFormat($errors)
    {
        $err = '';

        foreach ($errors->all() as $msg) {
            $err .= $msg . '<br>';
        }

        return $err;
    }

    // get date formated
    public static function getDateFormated($date)
    {
        if (!empty($date) && $date != '0000-00-00') {
            return date("d M Y", strtotime($date));
        } else {
            return '';
        }
    }

    // get progress bar color
    public static function getProgressColor($percentage)
    {

        $color = '';

        if ($percentage <= 20) {
            $color = 'danger';
        } elseif ($percentage > 20 && $percentage <= 40) {
            $color = 'warning';
        } elseif ($percentage > 40 && $percentage <= 60) {
            $color = 'info';
        } elseif ($percentage > 60 && $percentage <= 80) {
            $color = 'secondary';
        } elseif ($percentage >= 80) {
            $color = 'primary';
        }

        return $color;
    }

    // Return Percentage from two value
    public static function getPercentage($val1 = 0, $val2 = 0)
    {
        $percentage = 0;
        if ($val1 > 0 && $val2 > 0) {
            $percentage = intval(($val1 / $val2) * 100);
        }

        return $percentage;
    }

    // For crm dashboard
    public static function getCrmPercentage($val1 = 0, $val2 = 0)
    {
        $percentage = 0;
        if ($val1 > 0 && $val2 > 0) {
            $percentage = ($val1 / $val2) * 100;
            $percentage = number_format($percentage, \Utility::getValByName('decimal_number'));
        }

        return $percentage;
    }

    public static function timeToHr($times)
    {
        $totaltime = self::calculateTimesheetHours($times);
        $timeArray = explode(':', $totaltime);
        if ($timeArray[1] <= '30') {
            $totaltime = $timeArray[0];
        }
        $totaltime = $totaltime != '00' ? $totaltime : '0';

        return $totaltime;
    }

    public static function calculateTimesheetHours($times)
    {
        $minutes = 0;
        foreach ($times as $time) {
            list($hour, $minute) = explode(':', $time);
            $minutes += $hour * 60;
            $minutes += $minute;
        }
        $hours = floor($minutes / 60);
        $minutes -= $hours * 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    // Return Last 7 Days with date & day name
    public static function getLastSevenDays()
    {
        $arrDuration = [];
        $previous_week = strtotime("-1 week +1 day");

        for ($i = 0; $i < 7; $i++) {
            $arrDuration[date('Y-m-d', $previous_week)] = date('D', $previous_week);
            $previous_week = strtotime(date('Y-m-d', $previous_week) . " +1 day");
        }

        return $arrDuration;
    }

    // Check File is exist and delete these
    public static function checkFileExistsnDelete(array $files)
    {
        $status = false;
        foreach ($files as $key => $file) {
            if (Storage::exists($file)) {
                $status = Storage::delete($file);
            }
        }

        return $status;
    }

    // get project wise currency formatted amount
    public static function projectCurrencyFormat($project_id, $amount, $decimal = false)
    {
        $project = Project::find($project_id);
        if (empty($project)) {
            $settings = Utility::settings();

            return (($settings['site_currency_symbol_position'] == "pre") ? $settings['site_currency_symbol'] : '') . number_format($amount, Utility::getValByName('decimal_number')) . (($settings['site_currency_symbol_position'] == "post") ? $settings['site_currency_symbol'] : '');
        }

    }

    // Return Week first day and last day
    public static function getFirstSeventhWeekDay($week = null)
    {
        $first_day = $seventh_day = null;
        if (isset($week)) {
            // $first_day = Carbon::now()->addWeeks($week)->startOfWeek();
            // $seventh_day = Carbon::now()->addWeeks($week)->endOfWeek();
            $week = (int) $week;
            if ($week >= 0) {
                $first_day = Carbon::now()->addWeeks($week)->startOfWeek();
                $seventh_day = Carbon::now()->addWeeks($week)->endOfWeek();
            } else {
                $first_day = Carbon::now()->subWeeks(abs($week))->startOfWeek();
                $seventh_day = Carbon::now()->subWeeks(abs($week))->endOfWeek();
            }
        }
        $dateCollection['first_day'] = $first_day;
        $dateCollection['seventh_day'] = $seventh_day;
        $period = CarbonPeriod::create($first_day, $seventh_day);
        foreach ($period as $key => $dateobj) {
            $dateCollection['datePeriod'][$key] = $dateobj;
        }

        return $dateCollection;
    }

    public static function companyData($company_id, $string)
    {
        $setting = DB::table('settings')->where('created_by', $company_id)->where('name', $string)->first();
        if (!empty($setting)) {
            return $setting->value;
        } else {
            return '';
        }
    }

    public static function addNewData()
    {
        \Artisan::call('cache:forget spatie.permission.cache');
        \Artisan::call('cache:clear');
        $usr = \Auth::user();

        $arrPermissions = [
            'manage form builder',
            'create form builder',
            'edit form builder',
            'delete form builder',
            'manage form field',
            'create form field',
            'edit form field',
            'delete form field',
            'view form response',
            'manage performance type',
            'create performance type',
            'edit performance type',
            'delete performance type',
            'manage budget plan',
            'create budget plan',
            'edit budget plan',
            'delete budget plan',
            'view budget plan',
            'stock report',
            'manage warehouse',
            'create warehouse',
            'edit warehouse',
            'show warehouse',
            'delete warehouse',
            'manage purchase',
            'create purchase',
            'edit purchase',
            'show purchase',
            'delete purchase',
            'send purchase',
            'create payment purchase',
            'manage pos',
            'manage contract type',
            'create contract type',
            'edit contract type',
            'delete contract type',
            'create barcode',
            'show crm dashboard',
            'share project',
            'show pos dashboard',
            'create webhook',
            'edit webhook',
            'delete webhook',
            'manage project expense',
            'create project expense',
            'edit project expense',
            'delete project expense',
            'manage quotation',
            'create quotation',
            'edit quotation',
            'delete quotation',
            'show quotation',
            'convert quotation',
            'show pos',
            'manage zoom meeting',
            'create zoom meeting',
            'show zoom meeting',
            'delete zoom meeting',
            'manage biometric attendance',
            'biometric attendance synchronize',
        ];
        foreach ($arrPermissions as $ap) {
            // check if permission is not created then create it.
            $permission = Permission::where('name', 'LIKE', $ap)->first();
            if (empty($permission)) {
                Permission::create(['name' => $ap]);
            }
        }
        $companyRole = Role::where('name', 'LIKE', 'company')->first();

        $companyPermissions = $companyRole->getPermissionNames()->toArray();
        $companyNewPermission = [
            'manage form builder',
            'create form builder',
            'edit form builder',
            'delete form builder',
            'manage form field',
            'create form field',
            'edit form field',
            'delete form field',
            'view form response',
            'manage performance type',
            'create performance type',
            'edit performance type',
            'delete performance type',
            'manage budget plan',
            'create budget plan',
            'edit budget plan',
            'delete budget plan',
            'view budget plan',
            'stock report',
            'manage warehouse',
            'create warehouse',
            'edit warehouse',
            'show warehouse',
            'delete warehouse',
            'manage purchase',
            'create purchase',
            'edit purchase',
            'show purchase',
            'delete purchase',
            'send purchase',
            'create payment purchase',
            'manage pos',
            'manage contract type',
            'create contract type',
            'edit contract type',
            'delete contract type',
            'create barcode',
            'show crm dashboard',
            'share project',
            'show pos dashboard',
            'create webhook',
            'edit webhook',
            'delete webhook',
            'manage project expense',
            'create project expense',
            'edit project expense',
            'delete project expense',
            'manage quotation',
            'create quotation',
            'edit quotation',
            'delete quotation',
            'show quotation',
            'convert quotation',
            'show pos',
            'manage biometric attendance',
            'biometric attendance synchronize',
        ];
        foreach ($companyNewPermission as $op) {
            // check if permission is not assign to owner then assign.
            if (!in_array($op, $companyPermissions)) {
                $permission = Permission::findByName($op);
                $companyRole->givePermissionTo($permission);
            }
        }

    }

    public static function getAdminPaymentSetting()
    {

        $data = \DB::table('admin_payment_settings');

        $settings = [];
        if (\Auth::check()) {

            $user_id = 1;
            $data = $data->where('created_by', '=', $user_id);

        }
        $data = $data->get();
        foreach ($data as $row) {
            $settings[$row->name] = $row->value;
        }

        return $settings;
    }

    public static function getCompanyPaymentSetting($user_id)
    {

        $data = \DB::table('company_payment_settings');
        $settings = [];
        $data = $data->where('created_by', '=', $user_id);
        $data = $data->get();

        foreach ($data as $row) {
            $settings[$row->name] = $row->value;
        }

        return $settings;
    }

    public static function getCompanyPayment()
    {

        $data = \DB::table('company_payment_settings');
        $settings = [];
        if (\Auth::check()) {
            $user_id = \Auth::user()->creatorId();
            $data = $data->where('created_by', '=', $user_id);

        }
        $data = $data->get();
        foreach ($data as $row) {
            $settings[$row->name] = $row->value;
        }

        return $settings;
    }

    public static function error_res($msg = "", $args = array())
    {
        $msg = $msg == "" ? "error" : $msg;
        $msg_id = 'error.' . $msg;
        $converted = \Lang::get($msg_id, $args);
        $msg = $msg_id == $converted ? $msg : $converted;
        $json = array(
            'flag' => 0,
            'msg' => $msg,
        );

        return $json;
    }

    public static function success_res($msg = "", $args = array())
    {
        $msg = $msg == "" ? "success" : $msg;
        $msg_id = 'success.' . $msg;
        $converted = \Lang::get($msg_id, $args);
        $msg = $msg_id == $converted ? $msg : $converted;
        $json = array(
            'flag' => 1,
            'msg' => $msg,
        );

        return $json;
    }

    public static function getselectedThemeColor()
    {
        $color = env('THEME_COLOR');
        if ($color == "" || $color == null) {
            $color = 'sapphire';
        }

        return $color;
    }

    public static function getAllThemeColors()
    {
        $colors = [
            'blue',
            'denim',
            'sapphire',
            'olympic',
            'violet',
            'black',
            'cyan',
            'dark-blue-natural',
            'gray-dark',
            'light-blue',
            'light-purple',
            'magenta',
            'orange-mute',
            'pale-green',
            'rich-magenta',
            'rich-red',
            'sky-gray',
        ];

        return $colors;
    }

    public static function diffance_to_time($start, $end)
    {
        $start = new Carbon($start);
        $end = new Carbon($end);
        $totalDuration = $start->diffInSeconds($end);
        return $totalDuration;
    }

    public static function second_to_time($seconds = 0)
    {
        $H = floor($seconds / 3600);
        $i = ($seconds / 60) % 60;
        $s = $seconds % 60;

        $time = sprintf("%02d:%02d:%02d", $H, $i, $s);

        return $time;
    }

    //Slack notification
    public static function send_slack_msg($slug, $obj, $user_id = null)
    {

        $notification_template = NotificationTemplates::where('slug', $slug)->first();

        if (!empty($notification_template) && !empty($obj)) {
            if (!empty($user_id)) {
                $user = User::find($user_id);
            } else {
                $user = \Auth::user();
            }
            $curr_noti_tempLang = NotificationTemplateLangs::where('parent_id', '=', $notification_template->id)->where('lang', $user->lang)->where('created_by', '=', $user->id)->first();

            if (empty($curr_noti_tempLang)) {
                $curr_noti_tempLang = NotificationTemplateLangs::where('parent_id', '=', $notification_template->id)->where('lang', $user->lang)->first();
            }
            if (empty($curr_noti_tempLang)) {
                $curr_noti_tempLang = NotificationTemplateLangs::where('parent_id', '=', $notification_template->id)->where('lang', 'en')->first();
            }
            if (!empty($curr_noti_tempLang) && !empty($curr_noti_tempLang->content)) {
                $msg = self::replaceVariable($curr_noti_tempLang->content, $obj);
            }
        }

        if (isset($msg)) {
            $settings = Utility::settingsById($user->id);
            try {
                if (isset($settings['slack_webhook']) && !empty($settings['slack_webhook'])) {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $settings['slack_webhook']);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['text' => $msg]));

                    $headers = array();
                    $headers[] = 'Content-Type: application/json';
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                    $result = curl_exec($ch);
                    if (curl_errno($ch)) {
                        echo 'Error:' . curl_error($ch);
                    }
                    curl_close($ch);
                }
            } catch (\Exception $e) {
            }
        }
    }

    //Telegram Notification
    public static function send_telegram_msg($slug, $obj, $user_id = null)
    {
        $notification_template = NotificationTemplates::where('slug', $slug)->first();

        if (!empty($notification_template) && !empty($obj)) {
            if (!empty($user_id)) {
                $user = User::find($user_id);
            } else {
                $user = \Auth::user();
            }
            $curr_noti_tempLang = NotificationTemplateLangs::where('parent_id', '=', $notification_template->id)->where('lang', $user->lang)->where('created_by', '=', $user->id)->first();

            if (empty($curr_noti_tempLang)) {
                $curr_noti_tempLang = NotificationTemplateLangs::where('parent_id', '=', $notification_template->id)->where('lang', $user->lang)->first();
            }
            if (empty($curr_noti_tempLang)) {
                $curr_noti_tempLang = NotificationTemplateLangs::where('parent_id', '=', $notification_template->id)->where('lang', 'en')->first();
            }
            if (!empty($curr_noti_tempLang) && !empty($curr_noti_tempLang->content)) {
                $msg = self::replaceVariable($curr_noti_tempLang->content, $obj);
            }
        }

        if (isset($msg)) {
            $settings = Utility::settingsById($user->id);
            try {
                if (isset($settings['telegram_accestoken']) && !empty($settings['telegram_accestoken'])) {
                    // Set your Bot ID and Chat ID.
                    $telegrambot = $settings['telegram_accestoken'];
                    $telegramchatid = $settings['telegram_chatid'];
                    // Function call with your own text or variable
                    $url = 'https://api.telegram.org/bot' . $telegrambot . '/sendMessage';
                    $data = array(
                        'chat_id' => $telegramchatid,
                        'text' => $msg,
                    );
                    $options = array(
                        'http' => array(
                            'method' => 'POST',
                            'header' => "Content-Type:application/x-www-form-urlencoded\r\n",
                            'content' => http_build_query($data),
                        ),
                    );
                    $context = stream_context_create($options);
                    $result = file_get_contents($url, false, $context);
                    $url = $url;
                }
            } catch (\Exception $e) {
            }
        }

    }

    public static function mode_layout()
    {

        $data = DB::table('settings');
        if (\Auth::check()) {

            $data = $data->where('created_by', '=', \Auth::user()->creatorId())->get();
            if (count($data) == 0) {
                $data = DB::table('settings')->where('created_by', '=', 1)->get();
            }

        } else {

            $data->where('created_by', '=', 1);
            $data = $data->get();
        }

        $settings = [
            "cust_darklayout" => "off",
            "cust_theme_bg" => "on",
            "color" => '',
        ];
        foreach ($data as $row) {
            $settings[$row->name] = $row->value;
        }
        return $settings;
    }

    public static function colorset()
    {
        if (\Auth::check()) {
            if (\Auth::user()->type == 'super admin') {
                $user = \Auth::user();

                $setting = DB::table('settings')->where('created_by', $user->id)->pluck('value', 'name')->toArray();

            } else {
                $setting = DB::table('settings')->where('created_by', \Auth::user()->creatorId())->pluck('value', 'name')->toArray();
            }
        } else {
            $user = User::where('type', 'super admin')->first();
            $setting = DB::table('settings')->where('created_by', $user->id)->pluck('value', 'name')->toArray();
        }

        if (!isset($setting['color'])) {
            $setting = Utility::settings();
        }

        return $setting;
    }

    public static function getSeoSetting()
    {
        $data = \DB::table('settings')->whereIn('name', ['meta_title', 'meta_desc', 'meta_image'])->get();
        $settings = [];
        foreach ($data as $row) {
            $settings[$row->name] = $row->value;
        }
        return $settings;
    }

    public static function get_superadmin_logo()
    {
        $is_dark_mode = self::getValByName('cust_darklayout');
        $setting = DB::table('settings')->where('created_by', Auth::user()->id)->pluck('value', 'name')->toArray();
        if (!empty($setting['cust_darklayout'])) {
            $is_dark_mode = $setting['cust_darklayout'];
            // dd($is_dark_mode);
            if ($is_dark_mode == 'on') {
                return 'logo-light.png';
            } else {
                return 'logo-dark.png';
            }

        } else {
            return 'logo-dark.png';
        }

    }

    public static function GetLogo()
    {
        $setting = Utility::colorset();

        if (\Auth::user() && \Auth::user()->type != 'super admin') {

            if (Utility::getValByName('cust_darklayout') == 'on') {

                return Utility::getValByName('company_logo_light');
            } else {
                return Utility::getValByName('company_logo_dark');
            }
        } else {
            if (Utility::getValByName('cust_darklayout') == 'on') {

                return Utility::getValByName('light_logo');
            } else {
                return Utility::getValByName('dark_logo');
            }
        }
    }

    public static function getGdpr()
    {
        $data = DB::table('settings');
        if (\Auth::check()) {
            $data = $data->where('created_by', '=', 1);
        } else {
            $data = $data->where('created_by', '=', 1);
        }
        $data = $data->get();
        $settings = [
            "gdpr_cookie" => "",
            "cookie_text" => "",
        ];
        foreach ($data as $row) {
            $settings[$row->name] = $row->value;
        }
        return $settings;
    }

    public static function getValByName1($key)
    {
        $setting = Utility::getGdpr();
        if (!isset($setting[$key]) || empty($setting[$key])) {
            $setting[$key] = '';
        }
        return $setting[$key];
    }
    public static function upload_file($request, $key_name, $name, $path, $custom_validation = [])
    {
        try {
            $settings = Utility::getStorageSetting();

            if (!empty($settings['storage_setting'])) {

                if ($settings['storage_setting'] == 'wasabi') {

                    config(
                        [
                            'filesystems.disks.wasabi.key' => $settings['wasabi_key'],
                            'filesystems.disks.wasabi.secret' => $settings['wasabi_secret'],
                            'filesystems.disks.wasabi.region' => $settings['wasabi_region'],
                            'filesystems.disks.wasabi.bucket' => $settings['wasabi_bucket'],
                            'filesystems.disks.wasabi.endpoint' => 'https://s3.' . $settings['wasabi_region'] . '.wasabisys.com',
                        ]
                    );

                    $max_size = !empty($settings['wasabi_max_upload_size']) ? $settings['wasabi_max_upload_size'] : '2048';
                    $mimes = !empty($settings['wasabi_storage_validation']) ? $settings['wasabi_storage_validation'] : '';

                } else if ($settings['storage_setting'] == 's3') {
                    config(
                        [
                            'filesystems.disks.s3.key' => $settings['s3_key'],
                            'filesystems.disks.s3.secret' => $settings['s3_secret'],
                            'filesystems.disks.s3.region' => $settings['s3_region'],
                            'filesystems.disks.s3.bucket' => $settings['s3_bucket'],
                            'filesystems.disks.s3.use_path_style_endpoint' => false,
                        ]
                    );
                    $max_size = !empty($settings['s3_max_upload_size']) ? $settings['s3_max_upload_size'] : '2048';
                    $mimes = !empty($settings['s3_storage_validation']) ? $settings['s3_storage_validation'] : '';

                } else {

                    $max_size = !empty($settings['local_storage_max_upload_size']) ? $settings['local_storage_max_upload_size'] : '20480000000';

                    $mimes = !empty($settings['local_storage_validation']) ? $settings['local_storage_validation'] : '';
                }

                $file = $request->$key_name;

                if (count($custom_validation) > 0) {

                    $validation = $custom_validation;
                } else {

                    $validation = [
                        'mimes:' . $mimes,
                        'max:' . $max_size,
                    ];

                }

                $validator = \Validator::make($request->all(), [
                    $key_name => $validation,
                ]);

                if ($validator->fails()) {

                    $res = [
                        'flag' => 0,
                        'msg' => $validator->messages()->first(),
                    ];

                    return $res;
                } else {

                    $name = $name;

                    if ($settings['storage_setting'] == 'local') {
//                    dd(\Storage::disk(),$path);
                        $request->$key_name->move(storage_path($path), $name);
                        $path = $path . $name;
                    } else if ($settings['storage_setting'] == 'wasabi') {

                        $path = \Storage::disk('wasabi')->putFileAs(
                            $path,
                            $file,
                            $name
                        );

                        // $path = $path.$name;

                    } else if ($settings['storage_setting'] == 's3') {

                        $path = \Storage::disk('s3')->putFileAs(
                            $path,
                            $file,
                            $name
                        );
                        // $path = $path.$name;
                        // dd($path);
                    }

                    $res = [
                        'flag' => 1,
                        'msg' => 'success',
                        'url' => $path,
                    ];
                    return $res;
                }

            } else {
                $res = [
                    'flag' => 0,
                    'msg' => __('Please set proper configuration for storage.'),
                ];
                return $res;
            }

        } catch (\Exception $e) {

            $res = [
                'flag' => 0,
                'msg' => $e->getMessage(),
            ];
            return $res;
        }
    }

    //only employee edit storage setting upload_coustom_file function

    public static function upload_coustom_file($request, $key_name, $name, $path, $data_key, $custom_validation = [])
    {

        try {
            $settings = Utility::getStorageSetting();

            if (!empty($settings['storage_setting'])) {

                if ($settings['storage_setting'] == 'wasabi') {

                    config(
                        [
                            'filesystems.disks.wasabi.key' => $settings['wasabi_key'],
                            'filesystems.disks.wasabi.secret' => $settings['wasabi_secret'],
                            'filesystems.disks.wasabi.region' => $settings['wasabi_region'],
                            'filesystems.disks.wasabi.bucket' => $settings['wasabi_bucket'],
                            'filesystems.disks.wasabi.endpoint' => 'https://s3.' . $settings['wasabi_region'] . '.wasabisys.com',
                        ]
                    );

                    $max_size = !empty($settings['wasabi_max_upload_size']) ? $settings['wasabi_max_upload_size'] : '2048';
                    $mimes = !empty($settings['wasabi_storage_validation']) ? $settings['wasabi_storage_validation'] : '';

                } else if ($settings['storage_setting'] == 's3') {
                    config(
                        [
                            'filesystems.disks.s3.key' => $settings['s3_key'],
                            'filesystems.disks.s3.secret' => $settings['s3_secret'],
                            'filesystems.disks.s3.region' => $settings['s3_region'],
                            'filesystems.disks.s3.bucket' => $settings['s3_bucket'],
                            'filesystems.disks.s3.use_path_style_endpoint' => false,
                        ]
                    );
                    $max_size = !empty($settings['s3_max_upload_size']) ? $settings['s3_max_upload_size'] : '2048';
                    $mimes = !empty($settings['s3_storage_validation']) ? $settings['s3_storage_validation'] : '';

                } else {
                    $max_size = !empty($settings['local_storage_max_upload_size']) ? $settings['local_storage_max_upload_size'] : '2048';

                    $mimes = !empty($settings['local_storage_validation']) ? $settings['local_storage_validation'] : '';
                }

                $file = $request->$key_name;

                if (count($custom_validation) > 0) {
                    $validation = $custom_validation;
                } else {

                    $validation = [
                        'mimes:' . $mimes,
                        'max:' . $max_size,
                    ];

                }
                $validator = \Validator::make($request->all(), [
                    $name => $validation,
                ]);

                if ($validator->fails()) {
                    $res = [
                        'flag' => 0,
                        'msg' => $validator->messages()->first(),
                    ];
                    return $res;
                } else {

                    $name = $name;

                    if ($settings['storage_setting'] == 'local') {

                        \Storage::disk()->putFileAs(
                            $path,
                            $request->file($key_name)[$data_key],
                            $name
                        );

                        $path = $name;
                    } else if ($settings['storage_setting'] == 'wasabi') {

                        $path = \Storage::disk('wasabi')->putFileAs(
                            $path,
                            $request->file($key_name)[$data_key],
                            $name
                        );

                        // $path = $path.$name;

                    } else if ($settings['storage_setting'] == 's3') {

                        $path = \Storage::disk('s3')->putFileAs(
                            $path,
                            $request->file($key_name)[$data_key],
                            $name
                        );
                        // $path = $path.$name;
                        // dd($path);
                    }

                    $res = [
                        'flag' => 1,
                        'msg' => 'success',
                        'url' => $path,
                    ];
                    return $res;
                }

            } else {
                $res = [
                    'flag' => 0,
                    'msg' => __('Please set proper configuration for storage.'),
                ];
                return $res;
            }

        } catch (\Exception $e) {
            $res = [
                'flag' => 0,
                'msg' => $e->getMessage(),
            ];
            return $res;
        }
    }

    public static function get_file($path)
    {
        $settings = Utility::getStorageSetting();

        try {
            if ($settings['storage_setting'] == 'wasabi') {
                config(
                    [
                        'filesystems.disks.wasabi.key' => $settings['wasabi_key'],
                        'filesystems.disks.wasabi.secret' => $settings['wasabi_secret'],
                        'filesystems.disks.wasabi.region' => $settings['wasabi_region'],
                        'filesystems.disks.wasabi.bucket' => $settings['wasabi_bucket'],
                        'filesystems.disks.wasabi.endpoint' => 'https://s3.' . $settings['wasabi_region'] . '.wasabisys.com',
                    ]
                );
            } elseif ($settings['storage_setting'] == 's3') {
                config(
                    [
                        'filesystems.disks.s3.key' => $settings['s3_key'],
                        'filesystems.disks.s3.secret' => $settings['s3_secret'],
                        'filesystems.disks.s3.region' => $settings['s3_region'],
                        'filesystems.disks.s3.bucket' => $settings['s3_bucket'],
                        'filesystems.disks.s3.use_path_style_endpoint' => false,
                    ]
                );
            }

            return \Storage::disk($settings['storage_setting'])->url($path);
        } catch (\Throwable $th) {
            return '';
        }
    }

    public static function getStorageSetting()
    {
        $data = DB::table('settings');
        $data = $data->where('created_by', '=', 1);
        $data = $data->get();
        $settings = [
            "storage_setting" => "local",
            "local_storage_validation" => "jpg,jpeg,png,xlsx,xls,csv,pdf",
            "local_storage_max_upload_size" => "2048000",
            "s3_key" => "",
            "s3_secret" => "",
            "s3_region" => "",
            "s3_bucket" => "",
            "s3_url" => "",
            "s3_endpoint" => "",
            "s3_max_upload_size" => "",
            "s3_storage_validation" => "",
            "wasabi_key" => "",
            "wasabi_secret" => "",
            "wasabi_region" => "",
            "wasabi_bucket" => "",
            "wasabi_url" => "",
            "wasabi_root" => "",
            "wasabi_max_upload_size" => "",
            "wasabi_storage_validation" => "",

        ];
        foreach ($data as $row) {
            $settings[$row->name] = $row->value;
        }
        return $settings;
    }



    public static $colorCode = [
        1 => 'event-warning',
        2 => 'event-secondary',
        3 => 'event-info',
        4 => 'event-warning',
        5 => 'event-danger',
        6 => 'event-dark',
        7 => 'event-black',
        8 => 'event-info',
        9 => 'event-dark',
        10 => 'event-success',
        11 => 'event-warning',

    ];
    public static function getStartEndMonthDates()
    {
        $first_day_of_current_month = Carbon::now()->startOfMonth()->subMonths(0)->toDateString();
        $first_day_of_next_month = Carbon::now()->startOfMonth()->subMonths(-1)->toDateString();

        return ['start_date' => $first_day_of_current_month, 'end_date' => $first_day_of_next_month];
    }

    public static function webhookSetting($module, $user_id = null)
    {
        if (!empty($user_id)) {
            $user = User::find($user_id);
        } else {
            $user = \Auth::user();
        }
        $webhook = WebhookSetting::where('module', $module)->where('created_by', '=', $user->id)->first();
        if (!empty($webhook)) {
            $url = $webhook->url;
            $method = $webhook->method;
            $reference_url = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

            $data['method'] = $method;
            $data['reference_url'] = $reference_url;
            $data['url'] = $url;
            return $data;
        }
        return false;
    }

    public static function WebhookCall($url = null, $parameter = null, $method = 'POST')
    {
        if (!empty($url) && !empty($parameter)) {
            try {

                $curlHandle = curl_init($url);
                curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $parameter);
                curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, strtoupper($method));
                $curlResponse = curl_exec($curlHandle);
                curl_close($curlHandle);
                if (empty($curlResponse)) {
                    return true;
                } else {
                    return false;
                }
            } catch (\Throwable $th) {
                return false;
            }
        } else {
            return false;
        }
    }

    //start for cookie settings
    public static function getCookieSetting()
    {
        $data = \DB::table('settings')->whereIn('name', ['enable_cookie', 'cookie_logging', 'cookie_title',
            'cookie_description', 'necessary_cookies', 'strictly_cookie_title',
            'strictly_cookie_description', 'more_information_description', 'contactus_url'])->get();
        $settings = [
            'enable_cookie' => 'off',
            'necessary_cookies' => 'on',
            'cookie_logging' => 'on',
            'cookie_title' => '',
            'cookie_description' => '',
            'strictly_cookie_title' => '',
            'strictly_cookie_description' => '',
            'more_information_description' => '',
            'contactus_url' => '#',

        ];
        foreach ($data as $row) {
            $settings[$row->name] = $row->value;
        }
        return $settings;
    }

    public static function get_device_type($user_agent)
    {
        $mobile_regex = '/(?:phone|windows\s+phone|ipod|blackberry|(?:android|bb\d+|meego|silk|googlebot) .+? mobile|palm|windows\s+ce|opera mini|avantgo|mobilesafari|docomo)/i';
        $tablet_regex = '/(?:ipad|playbook|(?:android|bb\d+|meego|silk)(?! .+? mobile))/i';
        if (preg_match_all($mobile_regex, $user_agent)) {
            return 'mobile';
        } else {
            if (preg_match_all($tablet_regex, $user_agent)) {
                return 'tablet';
            } else {
                return 'desktop';
            }

        }
    }
    //end for cookie settings

    // start for (plans) storage limit - for file upload size
    public static function updateStorageLimit($company_id, $image_size)
    {
        $image_size = number_format($image_size / 1048576, 2);

        $user = User::find($company_id);
        $plan = Plan::find($user->plan);
        $total_storage = $user->storage_limit + $image_size;
        if ($plan->storage_limit <= $total_storage && $plan->storage_limit != -1) {

            $error = __('Plan storage limit is over so please upgrade the plan.');
            return $error;
        } else {
            $user->storage_limit = $total_storage;
        }

        $user->save();
        return 1;

    }

    public static function changeStorageLimit($company_id, $file_path)
    {

        $files = \File::glob(storage_path($file_path));
        $fileSize = 0;
        foreach ($files as $file) {
            $fileSize += \File::size($file);
        }

        $image_size = number_format($fileSize / 1048576, 2);
        $user = User::find($company_id);
        $plan = Plan::find($user->plan);
        $total_storage = $user->storage_limit - $image_size;
        $user->storage_limit = $total_storage;
        $user->save();

        $status = false;
        foreach ($files as $key => $file) {
            if (\File::exists($file)) {
                $status = \File::delete($file);
            }
        }

        return true;

    }
    // end for (plans) storage limit - for file upload size
    public static function smtpDetail($user_id)
    {
        $settings = self::settingsById($user_id);
        $smtpDetail = config(
            [
                'mail.driver' => $settings['mail_driver'],
                'mail.host' => $settings['mail_host'],
                'mail.port' => $settings['mail_port'],
                'mail.encryption' => $settings['mail_encryption'],
                'mail.username' => $settings['mail_username'],
                'mail.password' => $settings['mail_password'],
                'mail.from.address' => $settings['mail_from_address'],
                'mail.from.name' => $settings['mail_from_name'],
            ]
        );

        return $smtpDetail;
    }

    public static function getPusherSetting($user_id)
    {
        $settings = self::settingsById($user_id);

        if ($settings) {
            config([
                'chatify.pusher.key' => isset($settings['pusher_app_key']) ? $settings['pusher_app_key'] : '',
                'chatify.pusher.secret' => isset($settings['pusher_app_secret']) ? $settings['pusher_app_secret'] : '',
                'chatify.pusher.app_id' => isset($settings['pusher_app_id']) ? $settings['pusher_app_id'] : '',
                'chatify.pusher.options.cluster' => isset($settings['pusher_app_cluster']) ? $settings['pusher_app_cluster'] : '',
            ]);

            return $settings;
        }
    }

    public static function referralTransaction($plan , $company= '')
    {
        if($company != '')
        {
            $objUser = $company;
        }
        else
        {
            $objUser = \Auth::user();
        }

        $user = ReferralTransaction::where('company_id',$objUser->id)->first();

        $referralSetting = ReferralSetting::where('created_by' , 1)->first();

        if($objUser->used_referral_code != 0 && $user == null && (isset($referralSetting) && $referralSetting->is_enable == 1))
        {
            $transaction         = new ReferralTransaction();
            $transaction->company_id = $objUser->id;
            $transaction->plan_id = $plan->id;
            $transaction->plan_price = $plan->price;
            $transaction->commission = $referralSetting->percentage;
            $transaction->referral_code = $objUser->used_referral_code;
            $transaction->save();

            $commissionAmount  = ($plan->price * $referralSetting->percentage)/100;
            $user = User::where('referral_code' , $objUser->used_referral_code)->first();

            $user->commission_amount = $user->commission_amount + $commissionAmount;
            $user->save();
        }
    }

    public static function file_validate()
    {
        try {
            $settings = Utility::getStorageSetting();
            if (!empty($settings['storage_setting'])) {
                if ($settings['storage_setting'] == 'wasabi') {
                    $max_size = !empty($settings['wasabi_max_upload_size']) ? $settings['wasabi_max_upload_size'] : '2048';
                    $mimes =  !empty($settings['wasabi_storage_validation']) ? $settings['wasabi_storage_validation'] : '';
                } else if ($settings['storage_setting'] == 's3') {
                    $max_size = !empty($settings['s3_max_upload_size']) ? $settings['s3_max_upload_size'] : '2048';
                    $mimes =  !empty($settings['s3_storage_validation']) ? $settings['s3_storage_validation'] : '';
                } else {
                    $max_size = !empty($settings['local_storage_max_upload_size']) ? $settings['local_storage_max_upload_size'] : '2048';
                    $mimes =  !empty($settings['local_storage_validation']) ? $settings['local_storage_validation'] : '';
                }
                    $res = [
                        'types'  => $mimes,
                        'max_size'  => $max_size,
                    ];
                    return $res;
            } else {
                $res = [
                    'flag' => 0,
                    'msg' => __('Please set proper configuration for storage.'),
                ];
                return $res;
            }
        } catch (\Exception $e) {
            $res = [
                'flag' => 0,
                'msg' => $e->getMessage(),
            ];
            return $res;
        }
    }

    public static function getTableFields($input, $extraFields = [])
    {
        try {
            // if (class_exists($input)) {
            //     // If you pass model name
            //     $model = new $input();
            //     if (method_exists($model, 'getTable')) {
            //         $table = $model->getTable();
            //     } else {
            //         return [
            //             'status' => true,
            //             'message' => 'Table name not found',
            //             'data' => null,
            //         ];
            //     }
            // } else {
            //     // If you pass table name
                // $table = $input;
            // }

            if(Schema::hasTable($input)){
                $tableFields = Schema::getColumnListing($input);
            } else {
                return [
                    'status' => true,
                    'message' => 'Table name not found',
                    'data' => null,
                ];
            }

            $filteredFields = array_diff($tableFields, $extraFields);

            return [
                'status' => true,
                'message' => 'success',
                'data' => $filteredFields,
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
    }
}
