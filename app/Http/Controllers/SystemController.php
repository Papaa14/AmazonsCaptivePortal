<?php

namespace App\Http\Controllers;

use App\Mail\TestMail;
use App\Models\IpRestrict;
use App\Models\User;
use App\Models\Utility;
use App\Models\WebhookSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\Log;

class SystemController extends Controller
{
    public function index()
    {
        if (\Auth::user()->can('manage system settings')) {
            $settings = Utility::settings();
            $admin_payment_setting = Utility::getAdminPaymentSetting();
            // $emailSetting = Utility::settingsById(\Auth::user()->id);
            $file_size = 0;
            foreach (\File::allFiles(storage_path('/framework')) as $file) {
                $file_size += $file->getSize();
            }
            $file_size = number_format($file_size / 1000000, 4);

            return view('settings.index', compact('settings', 'admin_payment_setting', 'file_size'));
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function store(Request $request)
    {

        if (\Auth::user()->can('manage system settings')) {
            if ($request->logo_dark) {
                $logoName = 'logo-dark.png';
                $dir = 'uploads/logo/';
                $validation = [
                    'mimes:' . 'png',
                    'max:' . '20480',
                ];
                $path = Utility::upload_file($request, 'logo_dark', $logoName, $dir, []);
                if ($path['flag'] == 1) {
                    $logo = $path['url'];
                } else {
                    ToastMagic::error(__($path['msg']));
                    return redirect()->back();
                }
            }

            if ($request->logo_light) {

                $logoName = 'logo-light.png';

                $dir = 'uploads/logo';
                $validation = [
                    'mimes:' . 'png',
                    'max:' . '20480',
                ];
                $path = Utility::upload_file($request, 'logo_light', $logoName, $dir, $validation);
                if ($path['flag'] == 1) {
                    $logo = $path['url'];
                } else {
                    ToastMagic::error(__($path['msg']));
                    return redirect()->back();
                }
            }

            if ($request->favicon) {

                $favicon = 'favicon.png';
                $dir = 'uploads/logo';
                $validation = [
                    'mimes:' . 'png',
                    'max:' . '20480',
                ];

                $path = Utility::upload_file($request, 'favicon', $favicon, $dir, $validation);
                if ($path['flag'] == 1) {
                    $favicon = $path['url'];
                } else {
                    ToastMagic::error(__($path['msg']));
                    return redirect()->back();
                }
            }

            $settings = Utility::settings();

            if (
                !empty($request->title_text) || !empty($request->color) || !empty($request->SITE_RTL)
                || !empty($request->footer_text)
                || !empty($request->default_language)
                || isset($request->display_landing_page)
                || isset($request->gdpr_cookie) || isset($request->enable_signup) || isset($request->email_verification)
                || isset($request->color) || !empty($request->cust_theme_bg) || !empty($request->cust_darklayout)
            ) {
                $post = $request->all();

                $SITE_RTL = $request->has('SITE_RTL') ? $request->SITE_RTL : 'off';
                $post['SITE_RTL'] = $SITE_RTL;

                if (isset($request->color) && $request->color_flag == 'false') {
                    $post['color'] = $request->color;
                } else {
                    $post['color'] = $request->custom_color;
                }

                if (!isset($request->display_landing_page)) {
                    $post['display_landing_page'] = 'off';
                }
                if (!isset($request->gdpr_cookie)) {
                    $post['gdpr_cookie'] = 'off';
                }
                if (!isset($request->enable_signup)) {
                    $post['enable_signup'] = 'off';
                }
                if (!isset($request->email_verification)) {
                    $post['email_verification'] = 'off';
                }


                if (!isset($request->cust_theme_bg)) {
                    $cust_theme_bg = (!empty($request->cust_theme_bg)) ? 'on' : 'off';
                    $post['cust_theme_bg'] = $cust_theme_bg;
                }
                if (!isset($request->cust_darklayout)) {

                    $cust_darklayout = (!empty($request->cust_darklayout)) ? 'on' : 'off';
                    $post['cust_darklayout'] = $cust_darklayout;
                }

                unset($post['_token'], $post['company_logo_dark'], $post['company_logo_light'], $post['company_favicon'], $post['custom_color']);

                foreach ($post as $key => $data) {
                    if (in_array($key, array_keys($settings))) {
                        \DB::insert(
                            'insert into settings (`value`, `name`,`created_by`) values (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                            [
                                $data,
                                $key,
                                \Auth::user()->creatorId(),
                            ]
                        );
                    }
                }
            }
            ToastMagic::success('Brand Setting successfully updated.');
            return redirect()->back();
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function saveEmailSettings(Request $request)
    {
        if (\Auth::user()->can('manage system settings')) {
            $request->validate(
                [
                    'mail_driver' => 'required|string|max:255',
                    'mail_host' => 'required|string|max:255',
                    'mail_port' => 'required|string|max:255',
                    'mail_username' => 'required|string|max:255',
                    'mail_password' => 'required|string|max:255',
                    'mail_encryption' => 'required|string|max:255',
                    'mail_from_address' => 'required|string|max:255',
                    'mail_from_name' => 'required|string|max:255',
                ]
            );

            $post = $request->all();

            unset($post['_token']);
            $settings = Utility::settings();

            foreach ($post as $key => $data) {
                if (in_array($key, array_keys($settings))) {
                    \DB::insert(
                        'insert into settings (`value`, `name`,`created_by`) values (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                        [
                            $data,
                            $key,
                            \Auth::user()->creatorId(),
                        ]
                    );
                }
            }
            ToastMagic::success('Setting successfully updated.');
            return redirect()->back();
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function saveCompanyEmailSettings(Request $request)
    {
        if (\Auth::user()->type == 'company') {
            $request->validate(
                [
                    'mail_driver' => 'required|string|max:255',
                    'mail_host' => 'required|string|max:255',
                    'mail_port' => 'required|string|max:255',
                    'mail_username' => 'required|string|max:255',
                    'mail_password' => 'required|string|max:255',
                    'mail_encryption' => 'required|string|max:255',
                    'mail_from_address' => 'required|string|max:255',
                    'mail_from_name' => 'required|string|max:255',
                ]
            );
            $post = $request->all();

            unset($post['_token']);
            $settings = Utility::settings();


            foreach ($post as $key => $data) {
                if (in_array($key, array_keys($settings))) {
                    \DB::insert(
                        'insert into settings (`value`, `name`,`created_by`) values (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                        [
                            $data,
                            $key,
                            \Auth::user()->creatorId(),
                        ]
                    );
                }
            }
            ToastMagic::success('Setting successfully updated.');
            return redirect()->back();
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function saveCompanySettings(Request $request)
    {

        if (\Auth::user()->can('manage company settings')) {
            $user = \Auth::user();
            $request->validate(
                [
                    'company_name' => 'required|string|max:255',
                ]
            );
            $post = $request->all();
            if (isset($request->vat_gst_number_switch) && $request->vat_gst_number_switch == 'on') {
                $post['vat_gst_number_switch'] = 'on';
            } else {
                $post['vat_gst_number_switch'] = 'off';
            }

            if (isset($request->ip_restrict) && $request->ip_restrict == 'on') {
                $post['ip_restrict'] = 'on';
            } else {
                $post['ip_restrict'] = 'off';
            }

            unset($post['_token']);
            $settings = Utility::settings();

            foreach ($post as $key => $data) {
                if (in_array($key, array_keys($settings))) {
                    \DB::insert(
                        'insert into settings (`value`, `name`,`created_by`) values (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                        [
                            $data,
                            $key,
                            \Auth::user()->creatorId(),
                        ]
                    );
                }
            }
            ToastMagic::success('Setting successfully updated.');
            return redirect()->back();
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function savePaymentSettings(Request $request)
    {

        if (\Auth::user()->can('manage mpesa settings')) {
            //dd($request);

            $validator = \Validator::make(
                $request->all(),
                [
                    'currency' => 'required|string|max:255',
                    'currency_symbol' => 'required|string|max:255',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            self::adminPaymentSettings($request);
            ToastMagic::success('Payment setting successfully updated.');
            return redirect()->back();
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function saveSystemSettings(Request $request)
    {

        if (\Auth::user()->can('manage company settings')) {
            $user = \Auth::user();

            $post = $request->all();

            unset($post['_token']);

            if (!isset($post['shipping_display'])) {
                $post['shipping_display'] = 'off';
            }

            $settings = Utility::settings();

            foreach ($post as $key => $data) {
                if (in_array($key, array_keys($settings))) {
                    \DB::insert(
                        'insert into settings (`value`, `name`,`created_by`,`created_at`,`updated_at`) values (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                        [
                            $data,
                            $key,
                            \Auth::user()->creatorId(),
                            date('Y-m-d H:i:s'),
                            date('Y-m-d H:i:s'),
                        ]
                    );
                }
            }
            ToastMagic::success('Setting successfully updated.');
            return redirect()->back();
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function saveZoomSettings(Request $request)
    {
        $post = $request->all();
        unset($post['_token']);
        $created_by = \Auth::user()->creatorId();
        foreach ($post as $key => $data) {
            \DB::insert(
                'insert into settings (`value`, `name`,`created_by`,`created_at`,`updated_at`) values (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                [
                    $data,
                    $key,
                    $created_by,
                    date('Y-m-d H:i:s'),
                    date('Y-m-d H:i:s'),
                ]
            );
        }
        ToastMagic::success('Setting added successfully saved.');
        return redirect()->back();
    }

    public function saveBusinessSettings(Request $request)
    {

        if (\Auth::user()->can('manage business settings')) {
            $post = $request->all();

            $user = \Auth::user();
            if ($request->company_logo_dark) {

                $validation = [
                    'mimes:' . 'png',
                    'max:' . '20480',
                ];

                $logoName = $user->id . '-logo-dark.png';
                $dir = 'uploads/logo';
                $path = Utility::upload_file($request, 'company_logo_dark', $logoName, $dir, $validation);
                if ($path['flag'] == 1) {
                    $logo = $path['url'];
                } else {
                    ToastMagic::error($path['msg']);
                    return redirect()->back();
                }

                \DB::insert(
                    'insert into settings (`value`, `name`,`created_by`) values (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                    [
                        $logoName,
                        'company_logo_dark',
                        \Auth::user()->creatorId(),
                    ]
                );
            }

            if ($request->company_logo_light) {

                $validation = [
                    'mimes:' . 'png',
                    'max:' . '20480',
                ];
                //                $logoName = 'logo-light.png';
                $logoName = $user->id . '-logo-light.png';
                $dir = 'uploads/logo';
                $path = Utility::upload_file($request, 'company_logo_light', $logoName, $dir, $validation);
                if ($path['flag'] == 1) {
                    $logo = $path['url'];
                } else {
                    ToastMagic::error($path['msg']);
                    return redirect()->back()->with('error', __($path['msg']));
                }

                \DB::insert(
                    'insert into settings (`value`, `name`,`created_by`) values (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                    [
                        $logoName,
                        'company_logo_light',
                        \Auth::user()->creatorId(),
                    ]
                );
            }

            if ($request->company_favicon) {

                $validation = [
                    'mimes:' . 'png',
                    'max:' . '20480',
                ];

                //                $favicon = 'favicon.png';
                $favicon = $user->id . '-favicon.png';

                $dir = 'uploads/logo/';
                $path = Utility::upload_file($request, 'company_favicon', $favicon, $dir, $validation);
                if ($path['flag'] == 1) {
                } else {
                    ToastMagic::error($path['msg']);
                    return redirect()->back()->with('error', __($path['msg']));
                }

                \DB::insert(
                    'insert into settings (`value`, `name`,`created_by`) values (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                    [
                        $favicon,
                        'company_favicon',
                        \Auth::user()->creatorId(),
                    ]
                );
            }

            $settings = Utility::settings();

            if (
                !empty($request->title_text) || !empty($request->color) || !empty($request->cust_theme_bg)
                || !empty($request->footer_text)
                || !empty($request->default_language)
                || !empty($request->cust_darklayout)
            ) {

                $SITE_RTL = $request->has('SITE_RTL') ? $request->SITE_RTL : 'off';
                $post['SITE_RTL'] = $SITE_RTL;

                if (!isset($request->cust_theme_bg)) {
                    $cust_theme_bg = (!empty($request->cust_theme_bg)) ? 'on' : 'off';
                    $post['cust_theme_bg'] = $cust_theme_bg;
                }
                if (!isset($request->cust_darklayout)) {

                    $cust_darklayout = (!empty($request->cust_darklayout)) ? 'on' : 'off';
                    $post['cust_darklayout'] = $cust_darklayout;
                }

                if (isset($request->color) && $request->color_flag == 'false') {
                    $post['color'] = $request->color;
                } else {
                    $post['color'] = $request->custom_color;
                }

                unset($post['_token'], $post['company_logo_dark'], $post['company_logo_light'], $post['company_favicon'], $post['custom_color']);
                foreach ($post as $key => $data) {
                    if (in_array($key, array_keys($settings))) {

                        \DB::insert(
                            'insert into settings (`value`, `name`,`created_by`) values (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                            [
                                $data,
                                $key,
                                \Auth::user()->creatorId(),
                            ]
                        );
                    }
                }
            }
            ToastMagic::success('Brand Setting successfully updated.');
            return redirect()->back();
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function companyIndex(Request $request)
    {
        if (\Auth::user()->can('manage company settings')) {

            $setting = Utility::settingsById(Auth::user()->creatorId());

            $comSetting = DB::table('settings')->where('created_by', '=', \Auth::user()->id)->pluck('value', 'name')->toArray();

            $timezones = config('timezones');
            $company_payment_setting = Utility::getCompanyPaymentSetting(\Auth::user()->creatorId());

            $ips = IpRestrict::where('created_by', \Auth::user()->creatorId())->get();

            $emailSetting = DB::table('settings')->where('created_by', '=', \Auth::user()->id)->pluck('value', 'name')->toArray();

            $post = $request->all();

            return view('settings.company', compact(
                'setting',
                'company_payment_setting',
                'timezones',
                'ips',
                'emailSetting',
                'comSetting',
            ));
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function saveCompanyPaymentSettings(Request $request)
    {

        $user = \Auth::user();
        $post = [];              // Data for company_payment_settings table
        $paymentSettings = [];   // Data to be stored in the JSON column on users

        // Process MPesa settings
        if (isset($request->is_mpesa_enabled) && $request->is_mpesa_enabled == 'on') {
            $request->validate([
                'mpesa_mode'            => 'required',
                'mpesa_key'             => 'required',
                'mpesa_secret'          => 'required',
                'mpesa_shortcode'       => 'required',
                'mpesa_shortcode_type'  => 'required',
                'mpesa_passkey'         => 'required',
            ]);

            // Prepare for the company_payment_settings table
            $post['is_mpesa_enabled']      = $request->is_mpesa_enabled;
            $post['mpesa_mode']            = $request->mpesa_mode;
            $post['mpesa_key']             = $request->mpesa_key;
            $post['mpesa_secret']          = $request->mpesa_secret;
            $post['mpesa_shortcode']       = $request->mpesa_shortcode;
            $post['mpesa_shortcode_type']  = $request->mpesa_shortcode_type;
            $post['mpesa_passkey']         = $request->mpesa_passkey;

            // Prepare JSON data for gateway 'mpesa'
            $paymentSettings['mpesa'] = [
                'key'             => $request->mpesa_key,
                'secret'          => $request->mpesa_secret,
                'shortcode'       => $request->mpesa_shortcode,
                'shortcode_type'  => $request->mpesa_shortcode_type,
                'passkey'         => $request->mpesa_passkey,
            ];
        } else {
            $post['is_mpesa_enabled'] = 'off';
        }

        // Process MPesa bank settings
        if (isset($request->is_mpesa_bank_enabled) && $request->is_mpesa_bank_enabled == 'on') {
            $request->validate([
                'mpesa_bank_mode'             => 'required',
                'is_system_mpesa_api_enabled' => 'required',
                'mpesa_bank_paybill'          => 'required',
                'mpesa_bank_account'          => 'required',
            ]);

            $post['is_mpesa_bank_enabled']     = $request->is_mpesa_bank_enabled;
            $post['mpesa_bank_mode']           = $request->mpesa_bank_mode;
            $post['is_system_mpesa_api_enabled'] = $request->is_system_mpesa_api_enabled;
            $post['mpesa_bank_paybill']        = $request->mpesa_bank_paybill;
            $post['mpesa_bank_account']        = $request->mpesa_bank_account;

            $paymentSettings['bank'] = [
                'is_system_api_enabled' => $request->is_system_mpesa_api_enabled,
                'paybill'           => $request->mpesa_bank_paybill,
                'account'           => $request->mpesa_bank_account,
            ];
        } else {
            $post['is_mpesa_bank_enabled'] = 'off';
            // $paymentSettings['bank'] = null;
        }

        // Process MPesa paybill settings
        if (isset($request->is_mpesa_paybill_enabled) && $request->is_mpesa_paybill_enabled == 'on') {
            $request->validate([
                'mpesa_paybill_mode'             => 'required',
                'is_system_mpesa_paybill_api_enabled' => 'required',
                'mpesa_paybill'                  => 'required',
                'mpesa_paybill_account'          => 'required',
            ]);

            $post['is_mpesa_paybill_enabled']      = $request->is_mpesa_paybill_enabled;
            $post['mpesa_paybill_mode']            = $request->mpesa_paybill_mode;
            $post['is_system_mpesa_paybill_api_enabled'] = $request->is_system_mpesa_paybill_api_enabled;
            $post['mpesa_paybill']                 = $request->mpesa_paybill;
            $post['mpesa_paybill_account']         = $request->mpesa_paybill_account;

            $paymentSettings['paybill'] = [
                'is_system_api_enabled' => $request->is_system_mpesa_paybill_api_enabled,
                'paybill'   => $request->mpesa_paybill,
                'account'   => $request->mpesa_paybill_account,
            ];
        } else {
            $post['is_mpesa_paybill_enabled'] = 'off';
            // $paymentSettings['paybill'] = null;
        }

        // Process MPesa till settings
        if (isset($request->is_mpesa_till_enabled) && $request->is_mpesa_till_enabled == 'on') {
            $request->validate([
                'mpesa_till_mode'             => 'required',
                'is_system_mpesa_till_api_enabled' => 'required',
                'mpesa_till'                  => 'required',
                'mpesa_till_account'          => 'required',
            ]);

            $post['is_mpesa_till_enabled']     = $request->is_mpesa_till_enabled;
            $post['mpesa_till_mode']           = $request->mpesa_till_mode;
            $post['is_system_mpesa_till_api_enabled'] = $request->is_system_mpesa_till_api_enabled;
            $post['mpesa_till']                = $request->mpesa_till;
            $post['mpesa_till_account']        = $request->mpesa_till_account;

            $paymentSettings['till'] = [
                'is_system_api_enabled' => $request->is_system_mpesa_till_api_enabled,
                'till'      => $request->mpesa_till,
                'account'   => $request->mpesa_till_account,
            ];
        } else {
            $post['is_mpesa_till_enabled'] = 'off';
        }

        // Loop over $post to update company_payment_settings (via an insert on duplicate update)
        foreach ($post as $key => $data) {
            $arr = [$data, $key, $user->id];
            \DB::insert(
                'INSERT INTO company_payment_settings (`value`, `name`, `created_by`) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
                $arr
            );
        }

        // Build the userUpdate array (setting the gateway for PPPoE/Hotspot) exactly as before:
        $userUpdate = [];
        $methods = [
            'mpesa' => ['flag' => 'is_mpesa_enabled', 'mode' => 'mpesa_mode'],
            'bank' => ['flag' => 'is_mpesa_bank_enabled', 'mode' => 'mpesa_bank_mode'],
            'till' => ['flag' => 'is_mpesa_till_enabled', 'mode' => 'mpesa_till_mode'],
            'paybill' => ['flag' => 'is_mpesa_paybill_enabled', 'mode' => 'mpesa_paybill_mode'],
        ];

        // Default values (in case none are enabled)
        $userUpdate['pppoe_pay'] = null;
        $userUpdate['hotspot_pay'] = null;

        foreach ($methods as $type => $config) {
            if (isset($post[$config['flag']]) && $post[$config['flag']] === 'on') {
                $mode = strtolower($request->{$config['mode']} ?? '');

                if ($mode === 'pppoe' && !$userUpdate['pppoe_pay']) {
                    $userUpdate['pppoe_pay'] = $type;
                } elseif ($mode === 'hotspot' && !$userUpdate['hotspot_pay']) {
                    $userUpdate['hotspot_pay'] = $type;
                } elseif ($mode === 'both') {
                    if (!$userUpdate['pppoe_pay']) {
                        $userUpdate['pppoe_pay'] = $type;
                    }
                    if (!$userUpdate['hotspot_pay']) {
                        $userUpdate['hotspot_pay'] = $type;
                    }
                }
            }
        }

        // \Log::info('User update data:', $userUpdate);

        $userUpdate['payment_settings'] = json_encode($paymentSettings);
        // \Log::info('User update data:', $userUpdate);

        // Now update the users table in one query.
        \DB::table('users')
            ->where('id', $user->id)
            ->update($userUpdate);

        Cache::forget("payment_gateway_fast_{$user->id}_Hotspot");
        Cache::forget("payment_gateway_fast_{$user->id}_PPPoE");
        ToastMagic::success('Payment setting successfully updated and cache cleared.');
        return redirect()->back();
    }

    public function testMail(Request $request)
    {
        $data = [];
        $data['mail_driver'] = $request->mail_driver;
        $data['mail_host'] = $request->mail_host;
        $data['mail_port'] = $request->mail_port;
        $data['mail_username'] = $request->mail_username;
        $data['mail_password'] = $request->mail_password;
        $data['mail_encryption'] = $request->mail_encryption;
        $data['mail_from_address'] = $request->mail_from_address;
        $data['mail_from_name'] = $request->mail_from_name;

        return view('settings.test_mail', compact('data'));
    }

    public function testSendMail(Request $request)
    {

        $validator = \Validator::make(
            $request->all(),
            [
                'email' => 'required|email',
                'mail_driver' => 'required',
                'mail_host' => 'required',
                'mail_port' => 'required',
                'mail_username' => 'required',
                'mail_password' => 'required',
                'mail_from_address' => 'required',
                'mail_from_name' => 'required',
            ]
        );
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return response()->json(
                [
                    'is_success' => false,
                    'message' => $messages->first(),
                ]
            );
            // return redirect()->back()->with('error', $messages->first());
        }

        try {
            config(
                [
                    'mail.driver' => $request->mail_driver,
                    'mail.host' => $request->mail_host,
                    'mail.port' => $request->mail_port,
                    'mail.encryption' => $request->mail_encryption,
                    'mail.username' => $request->mail_username,
                    'mail.password' => $request->mail_password,
                    'mail.from.address' => $request->mail_from_address,
                    'mail.from.name' => $request->mail_from_name,
                ]
            );
            Mail::to($request->email)->send(new TestMail());
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ]
            );
        }

        return response()->json(
            [
                'success' => true,
                'message' => __('Email send Successfully'),
            ]
        );
    }

    public function printIndex()
    {
        if (\Auth::user()->can('manage print settings')) {
            $settings = Utility::settings();

            return view('settings.print', compact('settings'));
        } else {
            return redirect()->back()->with('error', 'Permission denied.');
        }
    }

    public function adminPaymentSettings($request)
    {
        $user = \Auth::user();
        $post = [];
        $paymentSettings = [];
        $post['currency'] = $request->currency;
        $post['currency_symbol'] = $request->currency_symbol;
        if (isset($request->is_manually_payment_enabled) && $request->is_manually_payment_enabled == 'on') {

            $post['is_manually_payment_enabled'] = $request->is_manually_payment_enabled;
        } else {
            $post['is_manually_payment_enabled'] = 'off';
        }
        if (isset($request->is_mpesa_enabled) && $request->is_mpesa_enabled == 'on') {
            $request->validate(
                [
                    'mpesa_key' => 'required',
                    'mpesa_secret' => 'required',
                    'mpesa_shortcode' => 'required',
                    'mpesa_shortcode_type' => 'required',
                    'mpesa_passkey' => 'required',
                ]
            );
            $post['is_mpesa_enabled'] = $request->is_mpesa_enabled;
            $post['mpesa_key'] = $request->mpesa_key;
            $post['mpesa_secret'] = $request->mpesa_secret;
            $post['mpesa_shortcode'] = $request->mpesa_shortcode;
            $post['mpesa_shortcode_type'] = $request->mpesa_shortcode_type;
            $post['mpesa_passkey'] = $request->mpesa_passkey;

            $paymentSettings['mpesa'] = [
                'key'             => $request->mpesa_key,
                'secret'          => $request->mpesa_secret,
                'shortcode'       => $request->mpesa_shortcode,
                'shortcode_type'  => $request->mpesa_shortcode_type,
                'passkey'         => $request->mpesa_passkey,
            ];
        }else {
            $post['is_mpesa_enabled'] = 'off';
            // $paymentSettings['till'] = null;
        }

        if (isset($request->is_paybill_enabled) && $request->is_paybill_enabled == 'on') {
            $request->validate(
                [
                    'personal_paybill_key' => 'required',
                    'personal_paybill_secret' => 'required',
                    'personal_paybill_shortcode' => 'required',
                    'personal_paybill_passkey' => 'required',
                ]
            );
            $post['is_paybill_enabled'] = $request->is_paybill_enabled;
            $post['personal_paybill_key'] = $request->personal_paybill_key;
            $post['personal_paybill_secret'] = $request->personal_paybill_secret;
            $post['personal_paybill_shortcode'] = $request->personal_paybill_shortcode;
            $post['personal_paybill_passkey'] = $request->personal_paybill_passkey;

            $paymentSettings['paybill_bank'] = [
                'key'             => $request->personal_paybill_key,
                'secret'          => $request->personal_paybill_secret,
                'shortcode'       => $request->personal_paybill_shortcode,
                'passkey'         => $request->personal_paybill_passkey,
            ];
        }else {
            $post['is_paybill_enabled'] = 'off';
            // $paymentSettings['till'] = null;
        }

        if (isset($request->is_till_enabled) && $request->is_till_enabled == 'on') {
            $request->validate(
                [
                    'personal_till_key' => 'required',
                    'personal_till_secret' => 'required',
                    'personal_till_shortcode' => 'required',
                    'personal_till_passkey' => 'required',
                ]
            );
            $post['is_till_enabled'] = $request->is_till_enabled;
            $post['personal_till_key'] = $request->personal_till_key;
            $post['personal_till_secret'] = $request->personal_till_secret;
            $post['personal_till_shortcode'] = $request->personal_till_shortcode;
            $post['personal_till_passkey'] = $request->personal_till_passkey;

            $paymentSettings['till'] = [
                'key'             => $request->personal_till_key,
                'secret'          => $request->personal_till_secret,
                'shortcode'       => $request->personal_till_shortcode,
                'passkey'         => $request->personal_till_passkey,
            ];
        } else {
            $post['is_till_enabled'] = 'off';
            // $paymentSettings['till'] = null;
        }

        foreach ($post as $key => $data) {

            $arr = [
                $data,
                $key,
                \Auth::user()->id,
            ];
            \DB::insert(
                'insert into admin_payment_settings (`value`, `name`,`created_by`) values (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                $arr
            );
        }
        $userUpdate = [];

        $userUpdate['payment_settings'] = json_encode($paymentSettings);

        // Now update the users table in one query.
        \DB::table('users')
            ->where('id', $user->id)
            ->update($userUpdate);
    }

    public function savePusherSettings(Request $request)
    {
        if (\Auth::user()->type == 'super admin') {

            $validator = \Validator::make(
                $request->all(),
                [
                    'pusher_app_id' => 'required',
                    'pusher_app_key' => 'required',
                    'pusher_app_secret' => 'required',
                    'pusher_app_cluster' => 'required',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $post = $request->all();

            unset($post['_token']);
            $settings = Utility::settings();

            foreach ($post as $key => $data) {
                if (in_array($key, array_keys($settings))) {
                    \DB::insert(
                        'insert into settings (`value`, `name`,`created_by`) values (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                        [
                            $data,
                            $key,
                            \Auth::user()->creatorId(),
                        ]
                    );
                }
            }
            ToastMagic::success('Pusher Settings updated successfully');
            return redirect()->back();
        }
    }

    public function saveSlackSettings(Request $request)
    {
        $post = [];
        $post['slack_webhook'] = $request->input('slack_webhook');
        $post['lead_notification'] = $request->has('lead_notification') ? $request->input('lead_notification') : 0;
        $post['deal_notification'] = $request->has('deal_notification') ? $request->input('deal_notification') : 0;
        $post['leadtodeal_notification'] = $request->has('leadtodeal_notification') ? $request->input('leadtodeal_notification') : 0;
        $post['contract_notification'] = $request->has('contract_notification') ? $request->input('contract_notification') : 0;
        $post['project_notification'] = $request->has('project_notification') ? $request->input('project_notification') : 0;
        $post['task_notification'] = $request->has('task_notification') ? $request->input('task_notification') : 0;
        $post['taskmove_notification'] = $request->has('taskmove_notification') ? $request->input('taskmove_notification') : 0;
        $post['taskcomment_notification'] = $request->has('taskcomment_notification') ? $request->input('taskcomment_notification') : 0;
        $post['payslip_notification'] = $request->has('payslip_notification') ? $request->input('payslip_notification') : 0;
        $post['award_notification'] = $request->has('award_notification') ? $request->input('award_notification') : 0;
        $post['announcement_notification'] = $request->has('announcement_notification') ? $request->input('announcement_notification') : 0;
        $post['holiday_notification'] = $request->has('holiday_notification') ? $request->input('holiday_notification') : 0;
        $post['support_notification'] = $request->has('support_notification') ? $request->input('support_notification') : 0;
        $post['event_notification'] = $request->has('event_notification') ? $request->input('event_notification') : 0;
        $post['meeting_notification'] = $request->has('meeting_notification') ? $request->input('meeting_notification') : 0;
        $post['policy_notification'] = $request->has('policy_notification') ? $request->input('policy_notification') : 0;
        $post['invoice_notification'] = $request->has('invoice_notification') ? $request->input('invoice_notification') : 0;
        $post['revenue_notification'] = $request->has('revenue_notification') ? $request->input('revenue_notification') : 0;
        $post['bill_notification'] = $request->has('bill_notification') ? $request->input('bill_notification') : 0;
        $post['payment_notification'] = $request->has('payment_notification') ? $request->input('payment_notification') : 0;
        $post['budget_notification'] = $request->has('budget_notification') ? $request->input('budget_notification') : 0;

        if (isset($post) && !empty($post) && count($post) > 0) {
            $created_at = $updated_at = date('Y-m-d H:i:s');

            foreach ($post as $key => $data) {
                DB::insert(
                    'INSERT INTO settings (`value`, `name`,`created_by`,`created_at`,`updated_at`) values (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `updated_at` = VALUES(`updated_at`) ',
                    [
                        $data,
                        $key,
                        Auth::user()->id,
                        $created_at,
                        $updated_at,
                    ]
                );
            }
        }
        ToastMagic::success('Slack updated successfully.');
        return redirect()->back();
    }

    public function saveTelegramSettings(Request $request)
    {
        $post = [];
        $post['telegram_accestoken'] = $request->input('telegram_accestoken');
        $post['telegram_chatid'] = $request->input('telegram_chatid');
        $post['telegram_lead_notification'] = $request->has('telegram_lead_notification') ? $request->input('telegram_lead_notification') : 0;
        $post['telegram_deal_notification'] = $request->has('telegram_deal_notification') ? $request->input('telegram_deal_notification') : 0;
        $post['telegram_leadtodeal_notification'] = $request->has('telegram_leadtodeal_notification') ? $request->input('telegram_leadtodeal_notification') : 0;
        $post['telegram_contract_notification'] = $request->has('telegram_contract_notification') ? $request->input('telegram_contract_notification') : 0;
        $post['telegram_project_notification'] = $request->has('telegram_project_notification') ? $request->input('telegram_project_notification') : 0;
        $post['telegram_task_notification'] = $request->has('telegram_task_notification') ? $request->input('telegram_task_notification') : 0;
        $post['telegram_taskmove_notification'] = $request->has('telegram_taskmove_notification') ? $request->input('telegram_taskmove_notification') : 0;
        $post['telegram_taskcomment_notification'] = $request->has('telegram_taskcomment_notification') ? $request->input('telegram_taskcomment_notification') : 0;
        $post['telegram_payslip_notification'] = $request->has('telegram_payslip_notification') ? $request->input('telegram_payslip_notification') : 0;
        $post['telegram_award_notification'] = $request->has('telegram_award_notification') ? $request->input('telegram_award_notification') : 0;
        $post['telegram_announcement_notification'] = $request->has('telegram_announcement_notification') ? $request->input('telegram_announcement_notification') : 0;
        $post['telegram_holiday_notification'] = $request->has('telegram_holiday_notification') ? $request->input('telegram_holiday_notification') : 0;
        $post['telegram_support_notification'] = $request->has('telegram_support_notification') ? $request->input('telegram_support_notification') : 0;
        $post['telegram_event_notification'] = $request->has('telegram_event_notification') ? $request->input('telegram_event_notification') : 0;
        $post['telegram_meeting_notification'] = $request->has('telegram_meeting_notification') ? $request->input('telegram_meeting_notification') : 0;
        $post['telegram_policy_notification'] = $request->has('telegram_policy_notification') ? $request->input('telegram_policy_notification') : 0;
        $post['telegram_invoice_notification'] = $request->has('telegram_invoice_notification') ? $request->input('telegram_invoice_notification') : 0;
        $post['telegram_revenue_notification'] = $request->has('telegram_revenue_notification') ? $request->input('telegram_revenue_notification') : 0;
        $post['telegram_bill_notification'] = $request->has('telegram_bill_notification') ? $request->input('telegram_bill_notification') : 0;
        $post['telegram_payment_notification'] = $request->has('telegram_payment_notification') ? $request->input('telegram_payment_notification') : 0;
        $post['telegram_budget_notification'] = $request->has('telegram_budget_notification') ? $request->input('telegram_budget_notification') : 0;

        if (isset($post) && !empty($post) && count($post) > 0) {
            $created_at = $updated_at = date('Y-m-d H:i:s');

            foreach ($post as $key => $data) {
                DB::insert(
                    'INSERT INTO settings (`value`, `name`,`created_by`,`created_at`,`updated_at`) values (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `updated_at` = VALUES(`updated_at`) ',
                    [
                        $data,
                        $key,
                        Auth::user()->id,
                        $created_at,
                        $updated_at,
                    ]
                );
            }
        }
        ToastMagic::success('Telegram updated successfully.');
        return redirect()->back();
    }

    public function saveSMSSettings(Request $request)
    {
        $post = [];
        $post['sms_url'] = $request->input('sms_url');
        // $post['sms_notify_type'] = $request->input('sms_notify_type');
        $post['sms_apitoken'] = $request->input('sms_apitoken');
        $post['sms_senderid'] = $request->input('sms_senderid');
        $post['sms_patnerid'] = $request->input('sms_patnerid', null);
        $post['sms_customer_notification'] = $request->has('sms_customer_notification') ? $request->input('sms_customer_notification') : 0;
        $post['sms_deposit_notification'] = $request->has('sms_deposit_notification') ? $request->input('sms_deposit_notification') : 0;
        $post['sms_invoice_notification'] = $request->has('sms_invoice_notification') ? $request->input('sms_invoice_notification') : 0;
        $post['sms_payment_notification'] = $request->has('sms_payment_notification') ? $request->input('sms_payment_notification') : 0;
        $post['sms_reminder_notification'] = $request->has('sms_reminder_notification') ? $request->input('sms_reminder_notification') : 0;
        $post['sms_expiry_notification'] = $request->has('sms_expiry_notification') ? $request->input('sms_expiry_notification') : 0;

        if (isset($post) && !empty($post) && count($post) > 0) {
            $created_at = $updated_at = date('Y-m-d H:i:s');

            foreach ($post as $key => $data) {
                DB::insert(
                    'INSERT INTO settings (`value`, `name`,`created_by`,`created_at`,`updated_at`) values (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `updated_at` = VALUES(`updated_at`) ',
                    [
                        $data,
                        $key,
                        Auth::user()->id,
                        $created_at,
                        $updated_at,
                    ]
                );
            }
        }
        ToastMagic::success('SMS Settings updated successfully.');
        return redirect()->back();
    }
    public function saveWhatsappSettings(Request $request)
    {
        $post = [];
        $post['wa_notify_type'] = $request->input('wa_notify_type');
        $post['whatsapp_url'] = $request->input('whatsapp_url');
        // $post['whatsapp_apitoken'] = $request->input('whatsapp_apitoken');
        // $post['sms_senderid'] = $request->input('sms_senderid');
        $post['whatsapp_customer_notification'] = $request->has('whatsapp_customer_notification') ? $request->input('whatsapp_customer_notification') : 0;
        $post['whatsapp_deposit_notification'] = $request->has('whatsapp_deposit_notification') ? $request->input('whatsapp_deposit_notification') : 0;
        $post['whatsapp_invoice_notification'] = $request->has('whatsapp_invoice_notification') ? $request->input('whatsapp_invoice_notification') : 0;
        // $post['twilio_revenue_notification'] = $request->has('twilio_revenue_notification') ? $request->input('twilio_revenue_notification') : 0;
        // $post['twilio_bill_notification'] = $request->has('twilio_bill_notification') ? $request->input('twilio_bill_notification') : 0;
        // $post['sms_proposal_notification'] = $request->has('sms_proposal_notification') ? $request->input('twilio_proposal_notification') : 0;
        $post['whatsapp_payment_notification'] = $request->has('whatsapp_payment_notification') ? $request->input('whatsapp_payment_notification') : 0;
        $post['whatsapp_reminder_notification'] = $request->has('whatsapp_reminder_notification') ? $request->input('whatsapp_reminder_notification') : 0;
        $post['whatsapp_expiry_notification'] = $request->has('whatsapp_expiry_notification') ? $request->input('whatsapp_expiry_notification') : 0;

        if (isset($post) && !empty($post) && count($post) > 0) {
            $created_at = $updated_at = date('Y-m-d H:i:s');

            foreach ($post as $key => $data) {
                DB::insert(
                    'INSERT INTO settings (`value`, `name`,`created_by`,`created_at`,`updated_at`) values (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `updated_at` = VALUES(`updated_at`) ',
                    [
                        $data,
                        $key,
                        Auth::user()->id,
                        $created_at,
                        $updated_at,
                    ]
                );
            }
        }

        ToastMagic::success(message: 'Whatsapp Settings updated successfully.');
        return redirect()->back();
    }

    public function recaptchaSettingStore(Request $request)
    {

        $user = \Auth::user();
        $rules = [];

        if (isset($request->recaptcha_module) && $request->recaptcha_module == 'on') {

            $request->validate(
                [
                    'google_recaptcha_key' => 'required|string|max:50',
                    'google_recaptcha_secret' => 'required|string|max:50',
                    'google_recaptcha_version' => 'required',
                ]
            );
            $post['recaptcha_module'] = $request->recaptcha_module;
            $post['google_recaptcha_key'] = $request->google_recaptcha_key;
            $post['google_recaptcha_secret'] = $request->google_recaptcha_secret;
            $post['google_recaptcha_version'] = $request->google_recaptcha_version;
        } else {
            $post['recaptcha_module'] = 'off';
            $post['google_recaptcha_key'] = $request->google_recaptcha_key;
            $post['google_recaptcha_secret'] = $request->google_recaptcha_secret;
            $post['google_recaptcha_version'] = $request->google_recaptcha_version;
        }

        $settings = Utility::settings();
        foreach ($post as $key => $data) {
            if (in_array($key, array_keys($settings))) {
                \DB::insert(
                    'insert into settings (`value`, `name`,`created_by`) values (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                    [
                        $data,
                        $key,
                        \Auth::user()->creatorId(),
                    ]
                );
            }
        }

        ToastMagic::success('Recaptcha Settings updated successfully');
        return redirect()->back();
    }

    public function storageSettingStore(Request $request)
    {

        if (isset($request->storage_setting) && $request->storage_setting == 'local') {

            $request->validate(
                [

                    'local_storage_validation' => 'required',
                    'local_storage_max_upload_size' => 'required',
                ]
            );

            $post['storage_setting'] = $request->storage_setting;
            $local_storage_validation = implode(',', $request->local_storage_validation);
            $post['local_storage_validation'] = $local_storage_validation;
            $post['local_storage_max_upload_size'] = $request->local_storage_max_upload_size;
        }

        if (isset($request->storage_setting) && $request->storage_setting == 's3') {
            $request->validate(
                [
                    's3_key' => 'required',
                    's3_secret' => 'required',
                    's3_region' => 'required',
                    's3_bucket' => 'required',
                    's3_url' => 'required',
                    's3_endpoint' => 'required',
                    's3_max_upload_size' => 'required',
                    's3_storage_validation' => 'required',
                ]
            );
            $post['storage_setting'] = $request->storage_setting;
            $post['s3_key'] = $request->s3_key;
            $post['s3_secret'] = $request->s3_secret;
            $post['s3_region'] = $request->s3_region;
            $post['s3_bucket'] = $request->s3_bucket;
            $post['s3_url'] = $request->s3_url;
            $post['s3_endpoint'] = $request->s3_endpoint;
            $post['s3_max_upload_size'] = $request->s3_max_upload_size;
            $s3_storage_validation = implode(',', $request->s3_storage_validation);
            $post['s3_storage_validation'] = $s3_storage_validation;
        }

        if (isset($request->storage_setting) && $request->storage_setting == 'wasabi') {
            $request->validate(
                [
                    'wasabi_key' => 'required',
                    'wasabi_secret' => 'required',
                    'wasabi_region' => 'required',
                    'wasabi_bucket' => 'required',
                    'wasabi_url' => 'required',
                    'wasabi_root' => 'required',
                    'wasabi_max_upload_size' => 'required',
                    'wasabi_storage_validation' => 'required',
                ]
            );
            $post['storage_setting'] = $request->storage_setting;
            $post['wasabi_key'] = $request->wasabi_key;
            $post['wasabi_secret'] = $request->wasabi_secret;
            $post['wasabi_region'] = $request->wasabi_region;
            $post['wasabi_bucket'] = $request->wasabi_bucket;
            $post['wasabi_url'] = $request->wasabi_url;
            $post['wasabi_root'] = $request->wasabi_root;
            $post['wasabi_max_upload_size'] = $request->wasabi_max_upload_size;
            $wasabi_storage_validation = implode(',', $request->wasabi_storage_validation);
            $post['wasabi_storage_validation'] = $wasabi_storage_validation;
        }

        foreach ($post as $key => $data) {

            $arr = [
                $data,
                $key,
                \Auth::user()->id,
            ];

            \DB::insert(
                'insert into settings (`value`, `name`,`created_by`) values (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                $arr
            );
        }

        ToastMagic::success('Storage setting successfully updated.');
        return redirect()->back();
    }

    public function offerletterupdate($lang, Request $request)
    {
        /*
        $user = GenerateOfferLetter::updateOrCreate(['lang' => $lang, 'created_by' => \Auth::user()->id], ['content' => $request->content]);

        return response()->json(
            [
                'is_success' => true,
                'success' => __('Offer Letter successfully saved!'),
            ],
            200
        );
        */
        return response()->json(
            [
                'is_success' => true,
                'success' => __('This feature is not available'),
            ],
            200
        );
    }

    public function joiningletterupdate($lang, Request $request)
    {
        /*
        $user = JoiningLetter::updateOrCreate(['lang' => $lang, 'created_by' => \Auth::user()->id], ['content' => $request->content]);

        return response()->json(
            [
                'is_success' => true,
                'success' => __('Joing Letter successfully saved!'),
            ],
            200
        );
        */
        return response()->json(
            [
                'is_success' => true,
                'success' => __('This feature is not available'),
            ],
            200
        );
    }

    public function experienceCertificateupdate($lang, Request $request)
    {
        /*
        $user = ExperienceCertificate::updateOrCreate(['lang' => $lang, 'created_by' => \Auth::user()->id], ['content' => $request->content]);

        return response()->json(
            [
                'is_success' => true,
                'success' => __('Experience Certificate successfully saved!'),
            ],
            200
        );
        */
        return response()->json(
            [
                'is_success' => true,
                'success' => __('This feature is not available'),
            ],
            200
        );
    }
    public function NOCupdate($lang, Request $request)
    {
        // Commented out to prevent errors with missing NOC class
        /*
        $user = NOC::updateOrCreate(['lang' => $lang, 'created_by' => \Auth::user()->id], ['content' => $request->content]);

        return response()->json(
            [
                'is_success' => true,
                'success' => __('NOC successfully saved!'),
            ],
            200
        );
        */
        return response()->json(
            [
                'is_success' => true,
                'success' => __('This feature is not available'),
            ],
            200
        );
    }

    //Save Google calendar settings

    public function saveGoogleCalenderSettings(Request $request)
    {
        if (isset($request->google_calendar_enable) && $request->google_calendar_enable == 'on') {

            $validator = \Validator::make(
                $request->all(),
                [
                    'google_calender_json_file' => 'required',
                    'google_clender_id' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            $post['google_calendar_enable'] = 'on';
        } else {
            $post['google_calendar_enable'] = 'off';
        }
        if ($request->google_calender_json_file) {
            $dir = storage_path() . '/' . md5(time());
            if (!is_dir($dir)) {
                File::makeDirectory($dir, $mode = 0777, true, true);
            }
            $file_name = $request->google_calender_json_file->getClientOriginalName();
            $file_path = md5(time()) . '/' . md5(time()) . "." . $request->google_calender_json_file->getClientOriginalExtension();

            $file = $request->file('google_calender_json_file');
            $file->move($dir, $file_path);
            $post['google_calender_json_file'] = $file_path;
        }

        if ($request->google_clender_id) {
            $post['google_clender_id'] = $request->google_clender_id;
            foreach ($post as $key => $data) {
                \DB::insert(
                    'insert into settings (`value`, `name`,`created_by`,`created_at`,`updated_at`) values (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                    [
                        $data,
                        $key,
                        \Auth::user()->id,
                        date('Y-m-d H:i:s'),
                        date('Y-m-d H:i:s'),
                    ]
                );
            }
        }

        ToastMagic::success('Google Calendar setting successfully updated.');
        return redirect()->back();
    }

    public function seoSettings(Request $request)
    {
        $validator = \Validator::make(
            $request->all(),
            [
                'meta_title' => 'required|string',
                'meta_desc' => 'required|string',
                'meta_image' => 'required|file',
            ]
        );
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }
        $dir = storage_path() . '/uploads' . '/meta';
        if (!is_dir($dir)) {
            File::makeDirectory($dir, $mode = 0777, true, true);
        }
        $file_path = $request->meta_image->getClientOriginalName();
        $file = $request->file('meta_image');
        $file->move($dir, $file_path);
        $post['meta_title'] = $request->meta_title;
        $post['meta_desc'] = $request->meta_desc;
        $post['meta_image'] = $file_path;

        foreach ($post as $key => $data) {
            \DB::insert(
                'insert into settings (`value`, `name`,`created_by`,`created_at`,`updated_at`) values (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                [
                    $data,
                    $key,
                    \Auth::user()->id,
                    date('Y-m-d H:i:s'),
                    date('Y-m-d H:i:s'),
                ]
            );
        }
        ToastMagic::success('SEO setting successfully updated.');
        return redirect()->back();
    }

    public function webhook()
    {

        if (\Auth::user()->can('create webhook')) {
            $webhookSettings = WebhookSetting::where('created_by', '=', \Auth::user()->creatorId())->get();
            ToastMagic::success('Webhook successfully created.');
            return redirect()->back();
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function webhookCreate()
    {
        if (\Auth::user()->can('create webhook')) {

            $modules = WebhookSetting::$modules;
            $methods = WebhookSetting::$method;

            return view('webhook.create', compact('modules', 'methods'));
        } else {
            ToastMagic::error('Permission denied.');
            // return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function webhookStore(Request $request)
    {

        if (\Auth::user()->can('create webhook')) {

            $validator = \Validator::make(
                $request->all(),
                [
                    'module' => 'required',
                    'url' => 'required',
                    'method' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $webhookSetting = new WebhookSetting();
            $webhookSetting->module = $request->module;
            $webhookSetting->url = $request->url;
            $webhookSetting->method = $request->method;
            $webhookSetting->created_by = \Auth::user()->creatorId();
            $webhookSetting->save();

            ToastMagic::success('Webhook successfully created.');
            return redirect()->back();
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function webhookEdit($id)
    {
        $webhooksetting = WebhookSetting::find($id);
        $modules = WebhookSetting::$modules;
        $methods = WebhookSetting::$method;
        return view('webhook.edit', compact('webhooksetting', 'modules', 'methods'));
    }

    public function webhookUpdate(Request $request, $id)
    {

        if (\Auth::user()->can('edit webhook')) {

            $validator = \Validator::make(
                $request->all(),
                [
                    'module' => 'required',
                    'method' => 'required',
                    'url' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $webhookSetting = WebhookSetting::find($id);
            $webhookSetting->module = $request->module;
            $webhookSetting->method = $request->method;
            $webhookSetting->url = $request->url;
            $webhookSetting->save();

            ToastMagic::success('Webhook successfully Updated.');
            return redirect()->back();
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function webhookDestroy($id)
    {
        if (\Auth::user()->can('delete webhook')) {
            $webhookSetting = WebhookSetting::find($id);
            $webhookSetting->delete();
            ToastMagic::success('Webhook successfully deleted.');
            return redirect()->back();
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function saveCookieSettings(Request $request)
    {

        $validator = \Validator::make(
            $request->all(),
            [
                'cookie_title' => 'required',
                'cookie_description' => 'required',
                'strictly_cookie_title' => 'required',
                'strictly_cookie_description' => 'required',
                'more_information_description' => 'required',
                'contactus_url' => 'required',
            ]
        );

        $post = $request->all();

        unset($post['_token']);

        if ($request->enable_cookie) {
            $post['enable_cookie'] = 'on';
        } else {
            $post['enable_cookie'] = 'off';
        }
        if ($request->cookie_logging) {
            $post['cookie_logging'] = 'on';
        } else {
            $post['cookie_logging'] = 'off';
        }

        $post['cookie_title'] = $request->cookie_title;
        $post['cookie_description'] = $request->cookie_description;
        $post['strictly_cookie_title'] = $request->strictly_cookie_title;
        $post['strictly_cookie_description'] = $request->strictly_cookie_description;
        $post['more_information_description'] = $request->more_information_description;
        $post['contactus_url'] = $request->contactus_url;

        $settings = Utility::settings();
        foreach ($post as $key => $data) {

            if (in_array($key, array_keys($settings))) {
                \DB::insert(
                    'insert into settings (`value`, `name`,`created_by`,`created_at`,`updated_at`) values (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                    [
                        $data,
                        $key,
                        \Auth::user()->creatorId(),
                        date('Y-m-d H:i:s'),
                        date('Y-m-d H:i:s'),
                    ]
                );
            }
        }
        ToastMagic::success('Cookie setting successfully saved.');
        return redirect()->back();
    }

    public function CookieConsent(Request $request)
    {

        $settings = Utility::settings();

        if ($settings['enable_cookie'] == "on" && $settings['cookie_logging'] == "on") {
            $allowed_levels = ['necessary', 'analytics', 'targeting'];
            $levels = array_filter($request['cookie'], function ($level) use ($allowed_levels) {
                return in_array($level, $allowed_levels);
            });
            $whichbrowser = new \WhichBrowser\Parser($_SERVER['HTTP_USER_AGENT']);
            // Generate new CSV line
            $browser_name = $whichbrowser->browser->name ?? null;
            $os_name = $whichbrowser->os->name ?? null;
            $browser_language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? mb_substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : null;
            $device_type = Utility::get_device_type($_SERVER['HTTP_USER_AGENT']);

            //            $ip = $_SERVER['REMOTE_ADDR'];
            $ip = '49.36.83.154';
            $query = @unserialize(file_get_contents('http://ip-api.com/php/' . $ip));

            $date = (new \DateTime())->format('Y-m-d');
            $time = (new \DateTime())->format('H:i:s') . ' UTC';

            $new_line = implode(',', [
                $ip,
                $date,
                $time,
                json_encode($request['cookie']),
                $device_type,
                $browser_language,
                $browser_name,
                $os_name,
                isset($query) ? $query['country'] : '',
                isset($query) ? $query['region'] : '',
                isset($query) ? $query['regionName'] : '',
                isset($query) ? $query['city'] : '',
                isset($query) ? $query['zip'] : '',
                isset($query) ? $query['lat'] : '',
                isset($query) ? $query['lon'] : ''
            ]);

            if (!file_exists(storage_path() . '/uploads/sample/data.csv')) {

                $first_line = 'IP,Date,Time,Accepted cookies,Device type,Browser language,Browser name,OS Name,Country,Region,RegionName,City,Zipcode,Lat,Lon';
                file_put_contents(storage_path() . '/uploads/sample/data.csv', $first_line . PHP_EOL, FILE_APPEND | LOCK_EX);
            }
            file_put_contents(storage_path() . '/uploads/sample/data.csv', $new_line . PHP_EOL, FILE_APPEND | LOCK_EX);

            return response()->json('success');
        }
        return response()->json('error');
    }

    public function cacheSettingStore(Request $request)
    {
        Artisan::call('cache:clear');
        Artisan::call('optimize:clear');
        ToastMagic::success('Cache clear Successfully');
        return redirect()->back();
    }

    //system-setting footer note
    public function footerNoteStore(Request $request, $user_id = null)
    {

        if (!empty($user_id)) {
            $user = User::find($user_id);
        } else {
            $user = \Auth::user();
        }
        $post = $request->all();
        $post['footer_notes'] = $request->notes;
        $settings = Utility::settingsById($user->id);

        foreach ($post as $key => $data) {

            if (in_array($key, array_keys($settings))) {
                \DB::insert(
                    'insert into settings (`value`, `name`,`created_by`,`created_at`,`updated_at`) values (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                    [
                        $data,
                        $key,
                        \Auth::user()->creatorId(),
                        date('Y-m-d H:i:s'),
                        date('Y-m-d H:i:s'),
                    ]
                );
            }
        }

        return response()->json(
            [
                'is_success' => true,
                'success' => __('Note successfully saved!'),
            ],
            200
        );
    }

    //for time-tracker setting
    public function saveTrackerSettings(Request $request)
    {
        $request->validate(
            [
                'interval_time' => 'required',
            ]
        );
        $post = $request->all();
        unset($post['_token']);
        $settings = Utility::settings();

        foreach ($post as $key => $data) {
            \DB::insert(
                'insert into settings (`value`, `name`,`created_by`,`created_at`,`updated_at`)
                values (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                [
                    $data,
                    $key,
                    \Auth::user()->creatorId(),
                    date('Y-m-d H:i:s'),
                    date('Y-m-d H:i:s'),
                ]
            );
        }
        ToastMagic::success('Time Tracker successfully updated.');
        return redirect()->back();
    }

    //chat gpt setting
    public function chatgptSetting(Request $request)
    {
        $post = $request->all();
        unset($post['_token']);
        $created_by = \Auth::user()->creatorId();
        foreach ($post as $key => $data) {
            \DB::insert(
                'insert into settings (`value`, `name`,`created_by`,`created_at`,`updated_at`) values (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                [
                    $data,
                    $key,
                    $created_by,
                    date('Y-m-d H:i:s'),
                    date('Y-m-d H:i:s'),
                ]
            );
        }
        ToastMagic::success('ChatGPT Setting successfully saved.');
        return redirect()->back();
    }

    //ip settings
    public function createIp()
    {
        return view('restrict_ip.create');
    }

    public function storeIp(Request $request)
    {
        if (\Auth::user()->can('manage company settings')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'ip' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                ToastMagic::error($messages->first());
                return redirect()->back()->with('error', $messages->first());
            }

            $ip = new IpRestrict();
            $ip->ip = $request->ip;
            $ip->created_by = \Auth::user()->creatorId();
            $ip->save();
            ToastMagic::success('IP successfully created.');
            return redirect()->back();
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function editIp($id)
    {
        $ip = IpRestrict::find($id);

        return view('restrict_ip.edit', compact('ip'));
    }

    public function updateIp(Request $request, $id)
    {
        if (\Auth::user()->type == 'company' || \Auth::user()->type == 'super admin') {
            $validator = \Validator::make(
                $request->all(),
                [
                    'ip' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $ip = IpRestrict::find($id);
            $ip->ip = $request->ip;
            $ip->save();
            ToastMagic::success('IP successfully updated.');
            return redirect()->back();
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function destroyIp($id)
    {
        if (\Auth::user()->type == 'company' || \Auth::user()->type == 'super admin') {
            $ip = IpRestrict::find($id);
            $ip->delete();
            ToastMagic::success('IP successfully deleted.');
            return redirect()->back();
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function saveCurrencySettings(Request $request)
    {
        $user = \Auth::user();

        $post = $request->all();

        unset($post['_token']);

        $settings = Utility::settings();

        foreach ($post as $key => $data) {
            if (in_array($key, array_keys($settings))) {
                \DB::insert(
                    'insert into settings (`value`, `name`,`created_by`,`created_at`,`updated_at`) values (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                    [
                        $data,
                        $key,
                        \Auth::user()->creatorId(),
                        date('Y-m-d H:i:s'),
                        date('Y-m-d H:i:s'),
                    ]
                );
            }
        }
        ToastMagic::success('Setting successfully updated.');
        return redirect()->back();
    }


    public function currencyPreview(Request $request)
    {
        $float_number = $request->float_number == 'dot' ? '.' : ',';
        $decimal_separator = $request->decimal_separator == 'dot' ? '.' : ',';
        $thousand_separator = $request->thousand_separator == 'dot' ? '.' : ',';

        $currency = $request->currency_symbol == 'withcurrencysymbol' ? $request->site_currency_symbol : $request->site_currency;
        $decimal_number = $request->decimal_number;
        $currency_space = $request->currency_space;

        $price = number_format(10000.00, $decimal_number, $decimal_separator, $thousand_separator);

        if ($request->float_number == 'dot') {
            $price = preg_replace('/' . preg_quote($thousand_separator, '/') . '([^' . preg_quote($thousand_separator, '/') . ']*)$/', $float_number . '$1', $price);
        } else {
            $price = preg_replace('/' . preg_quote($decimal_separator, '/') . '([^' . preg_quote($decimal_separator, '/') . ']*)$/', $float_number . '$1', $price);
        }

        $price = (($request->site_currency_symbol_position == "pre") ? $currency : '') . ($currency_space == 'withspace' ? ' ' : '') . $price . ($currency_space == 'withspace' ? ' ' : '') . (($request->site_currency_symbol_position == "post") ? $currency : '');
        return $price;
    }
    public function BiometricSetting(Request $request)
    {
        $validator = \Validator::make(
            $request->all(),
            [
                'zkteco_api_url' => 'required',
                'username' => 'required',
                'user_password' => 'required',
            ]
        );
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            ToastMagic::error($messages->first());
            return redirect()->back();
        }

        $user = \Auth::user();
        if (!empty($request->zkteco_api_url) && !empty($request->username) && !empty($request->user_password)) {
            try {

                $url = "$request->zkteco_api_url" . '/api-token-auth/';
                $headers = array(
                    "Content-Type: application/json"
                );
                $data = array(
                    "username" => $request->username,
                    "password" => $request->user_password
                );

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                $response = curl_exec($ch);
                curl_close($ch);
                $auth_token = json_decode($response, true);

                if (isset($auth_token['token'])) {

                    $post = $request->all();
                    $post['zkteco_api_url'] = $request->zkteco_api_url;
                    $post['username'] = $request->username;
                    $post['user_password'] = $request->user_password;
                    $post['auth_token'] = $auth_token['token'];
                    unset($post['_token']);
                    foreach ($post as $key => $data) {
                        $settings = Utility::settings();
                        if (in_array($key, array_keys($settings))) {
                            \DB::insert(
                                'insert into settings (`value`, `name`,`created_by`,`created_at`,`updated_at`) values (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                                [
                                    $data,
                                    $key,
                                    \Auth::user()->creatorId(),
                                    date('Y-m-d H:i:s'),
                                    date('Y-m-d H:i:s'),
                                ]
                            );
                        }
                    }
                } else {
                    ToastMagic::error(isset($auth_token['non_field_errors']) ? $auth_token['non_field_errors'][0] : __("something went wrong please try again"));
                    return redirect()->back();
                }
            } catch (\Exception $e) {
                ToastMagic::error($e->getMessage());
                return redirect()->back()->with('error', $e->getMessage());
            }
            ToastMagic::error('Biometric setting successfully saved.');
            return redirect()->back();
        }
    }
}
