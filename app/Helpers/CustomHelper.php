<?php

namespace App\Helpers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Transaction;
use App\Models\Customer;
use App\Models\Utility;
use App\Models\User;
use App\Models\Bandwidth;
use App\Models\Package;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Auth;
use App\Models\SmsAlert;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use App\Models\Device;


class CustomHelper
{
    public static function lockMac(Customer $customer)
    {
        if (empty($customer->mac_address)) {
            $mac = DB::table('radacct')
                ->whereNull('acctstoptime')
                ->where('username', $customer->username)
                ->where('created_by', $customer->created_by)
                ->value('callingstationid');

            if (!empty($mac)) {
                $customer->mac_address = $mac;
                $customer->save();

                DB::table('radcheck')->updateOrInsert(
                    ['username' => $customer->username, 'attribute' => 'Calling-Station-Id', 'created_by' => $customer->created_by],
                    ['op' => '==', 'value' => $customer->mac_address]

                );
            }
        }
    }

    public static function unlockMac(Customer $customer)
    {
        if (!empty($customer->mac_address)) {
            DB::table('radcheck')
                ->where('username', $customer->username)
                ->where('attribute', 'Calling-Station-Id')
                ->where('created_by', $customer->created_by)
                ->delete();

            $customer->mac_address = null;
            $customer->save();
        }
    }

    public static function updatePlan($customer)
    {
        // Get package using the foreign key relationship instead of string lookup
        $package = $customer->package_id ?
            Package::find($customer->package_id) :
            Package::where('name_plan', $customer->package)->first();

        if (!$package) {
            Log::error("Package not found for customer ID {$customer->id}");
            return false;
        } 

        $group_name = 'package_' . $package->id;
        $createdBy = $customer->created_by;
        $radiusUsername = $customer->username;
        $radiusPassword = $customer->password;
        $bandwidth = Bandwidth::where('package_id', $package->id)->first();

        if (!$bandwidth) {
            Log::error("Bandwidth not found for package ID: " . $package->id);
            return;
        }

        // $MikroRate = "{$bandwidth->rate_down}{$bandwidth->rate_down_unit}/{$bandwidth->rate_up}{$bandwidth->rate_up_unit}";
        if ($customer->is_override) {
            $MikroRate = "{$customer->override_download}{$customer->override_download_unit}/{$customer->override_upload}{$customer->override_upload_unit}";
        } else {
            $MikroRate = "{$bandwidth->rate_down}{$bandwidth->rate_down_unit}/{$bandwidth->rate_up}{$bandwidth->rate_up_unit}";
        }

        if (!empty($group_name)) {
            
            if ($customer->status !== 'off') { 
                DB::transaction(function () use ($customer, $group_name, $createdBy, $radiusUsername) {
                    DB::table('radusergroup')
                        ->where('username', $radiusUsername)
                        ->where('created_by', $createdBy)
                        ->delete();

                        DB::table('radusergroup')->insert([
                            'username'  => $customer->username,
                            'groupname' => $group_name,
                            'priority'  => 1,
                            'created_by' => $customer->created_by,
                        ]);
                });
            

                $active = DB::table('radacct')
                    ->where('username', $radiusUsername)
                    ->where('created_by', $createdBy)
                    ->whereNull('acctstoptime')
                    ->orderBy('acctstarttime', 'desc')
                    ->first();

                if (!empty($active)) {
                    $nasObj = DB::table('nas')->where('nasname', $active->nasipaddress)->first();

                    if ($nasObj) {
                        $attributes = [
                            'acctSessionID' => $active->acctsessionid,
                            'framedIPAddress' => $active->framedipaddress,
                        ];

                        $downm = "{$bandwidth->rate_down}{$bandwidth->rate_down_unit}";
                        $upm = "{$bandwidth->rate_up}{$bandwidth->rate_up_unit}";
                        $CoAData = "{$downm}/{$upm}";

                        self::sendCoA($nasObj, $customer, $attributes, $CoAData);
                        self::kickOutUsersByRadius($nasObj, $customer, $attributes);
                    } else {
                        Log::error("NAS not found for active session: " . json_encode($active));
                    }
                }
            }

            DB::table('radcheck')->where('username', $radiusUsername)->where('created_by', $createdBy)->delete();
            DB::table('radreply')->where('username', $radiusUsername)->where('created_by', $createdBy)->delete();

            DB::table('radcheck')->insert([
                [
                    'username' => $radiusUsername,
                    'attribute' => 'Cleartext-Password',
                    'op' => ':=',
                    'value' => $radiusPassword,
                    'created_by' => $createdBy,
                ],
            ]);

            DB::table('radreply')->insert([
                [
                    'username' => $radiusUsername,
                    'attribute' => 'Mikrotik-Rate-Limit',
                    'op' => ':=',
                    'value' => $MikroRate,
                    'created_by' => $createdBy,
                ],
            ]);
        }
    }

    public static function refreshCustomerInRadius($customer)
    {
        $createdBy = $customer->created_by;

        $activeSession = DB::table('radacct')
            ->where('username', $customer->username)
            ->where('created_by', $createdBy)
            ->whereNull('acctstoptime')
            ->orderBy('acctstarttime', 'desc')
            ->first();

        if (!$activeSession) {
            return ['status' => 'error', 'message' => 'User is not online'];
        }

        $nasObj = DB::table('nas')
            ->where('nasname', $activeSession->nasipaddress)
            ->first();

        if (!$nasObj) {
            return ['status' => 'error', 'message' => 'NAS not found'];
        }

        $attributes = [
            'acctSessionID'   => $activeSession->acctsessionid,
            'framedIPAddress' => $activeSession->framedipaddress,
        ];

        $package = $customer->package_id
            ? Package::with('bandwidth')->find($customer->package_id)
            : null;

        if (!$package || !$package->bandwidth) {
            return ['status' => 'error', 'message' => 'Package or bandwidth not found'];
        }

        if ($customer->is_override) {
            $up = $customer->override_upload . $customer->override_upload_unit;
            $down = $customer->override_download . $customer->override_download_unit;
        } else {
            $down = $package->bandwidth->rate_down . $package->bandwidth->rate_down_unit;
            $up = $package->bandwidth->rate_up . $package->bandwidth->rate_up_unit;
        }

        $CoAData = $down . "/" . $up;

        $result = self::sendCoA($nasObj, $customer, $attributes, $CoAData);

        return $result
            ? ['status' => 'success', 'message' => 'CoA sent and ACK received']
            : ['status' => 'error', 'message' => 'CoA failed or NAK received'];
    }

    public static function sendCoA($nasObj, $userData, array $attributes, $CoAData)
    {
        if (!isset($attributes['acctSessionID']) || !isset($attributes['framedIPAddress'])) {
            Log::error("Missing required CoA attributes: " . json_encode($attributes));
            return false;
        }

        $username = escapeshellarg($userData->username);
        $acctSessionID = escapeshellarg($attributes['acctSessionID']);
        $framedIPAddress = escapeshellarg($attributes['framedIPAddress']);
        $rateLimit = escapeshellarg($CoAData);

        $nasname = escapeshellarg($nasObj->nasname);
        $nasport = $nasObj->incoming_port ?? 3799;
        $nassecret = escapeshellarg($nasObj->secret);

        $command = "echo \"User-Name=$username, Acct-Session-Id=$acctSessionID, Framed-IP-Address=$framedIPAddress, Mikrotik-Rate-Limit=$rateLimit\" | radclient -x $nasname:$nasport coa $nassecret";

        Log::info("Sending CoA to $nasname for $username: $command");

        $response = shell_exec($command);

        Log::info("CoA response for $username: $response");

        return strpos($response, 'Received CoA-ACK') !== false;
    }


    public static function handleDeactivation($customer)
    {
        $activeSession = DB::table('radacct')
            ->where('username', $customer->username)
            ->where('created_by', $customer->created_by)
            ->whereNull('acctstoptime')
            ->orderBy('acctstarttime', 'desc')
            ->first();

        if ($activeSession) {
            $nasObj = DB::table('nas')->where('nasname', $activeSession->nasipaddress)->first();
            $attributes = [
                'acctSessionID' => $activeSession->acctsessionid,
                'framedIPAddress' => $activeSession->framedipaddress,
            ];

            self::kickOutUsersByRadius($nasObj, $customer, $attributes);
        }

        DB::table('radusergroup')->where('username', $customer->username)->where('created_by', $customer->created_by)->delete();
        DB::table('radusergroup')->insert([
            'username'  => $customer->username,
            'groupname' => 'Disabled_Plan',
            'priority'  => 1,
            'created_by' => $customer->created_by,
        ]);
    }

    public static function activateCustomer($customer)
    {
        $package = $customer->package_id ?
            Package::find($customer->package_id) :
            Package::where('name_plan', $customer->package)->firstOrFail();

        $group_name = 'package_' . $package->id;

        if (!empty($group_name)) {
            if ($customer->status === 'off' && (Carbon::parse($customer->expiry)->isFuture() || ($customer->extension_expiry && Carbon::parse($customer->extension_expiry)->isFuture()))) {
                // Reactivate customer - valid expiry or extension
                DB::transaction(function () use ($customer, $group_name) {
                    DB::table('radusergroup')
                        ->where('username', $customer->username)
                        ->where('created_by', $customer->created_by)
                        ->delete();

                    DB::table('radusergroup')->insert([
                        'username'   => $customer->username,
                        'groupname'  => $group_name,
                        'priority'   => 1,
                        'created_by' => $customer->created_by,
                    ]);
                });
            } elseif ( $customer->status === 'off' && ( Carbon::parse($customer->expiry)->isPast() && (!$customer->extension_expiry || Carbon::parse($customer->extension_expiry)->isPast()))) {
                // Fully expired
                DB::transaction(function () use ($customer) {
                    DB::table('radusergroup')
                        ->where('username', $customer->username)
                        ->where('created_by', $customer->created_by)
                        ->delete();

                    DB::table('radusergroup')->insert([
                        'username'   => $customer->username,
                        'groupname'  => 'Expired_Plan',
                        'priority'   => 1,
                        'created_by' => $customer->created_by,
                    ]);
                });

                Log::info("Customer {$customer->username} is expired — added to Expired_Plan");
            } else {
                // Active or force reassign to correct group
                DB::transaction(function () use ($customer, $group_name) {
                    DB::table('radusergroup')
                        ->where('username', $customer->username)
                        ->where('created_by', $customer->created_by)
                        ->delete();

                    DB::table('radusergroup')->insert([
                        'username'   => $customer->username,
                        'groupname'  => $group_name,
                        'priority'   => 1,
                        'created_by' => $customer->created_by,
                    ]);
                });
            }

            $activeSession = DB::table('radacct')
                ->where('username', $customer->username)
                ->where('created_by', $customer->created_by)
                ->whereNull('acctstoptime')
                ->orderBy('acctstarttime', 'desc')
                ->first();

            if ($activeSession) {
                $nasObj = DB::table('nas')->where('nasname', $activeSession->nasipaddress)->first();
                $attributes = [
                    'acctSessionID' => $activeSession->acctsessionid,
                    'framedIPAddress' => $activeSession->framedipaddress,
                ];

                self::kickOutUsersByRadius($nasObj, $customer, $attributes);
            }
        }
    }

    public static function handleExpiry($customer)
    {
        $createdBy = $customer->created_by;
        
        $activeSession = DB::table('radacct')
            ->where('username', $customer->username)
            ->where('created_by', $customer->created_by)
            ->whereNull('acctstoptime')
            ->orderBy('acctstarttime', 'desc')
            ->first();

        if ($activeSession) {
            $nasObj = DB::table('nas')->where('nasname', $activeSession->nasipaddress)->first();

            if ($nasObj) {
                $attributes = [
                    'acctSessionID'   => $activeSession->acctsessionid,
                    'framedIPAddress' => $activeSession->framedipaddress,
                ];
                self::kickOutUsersByRadius($nasObj, $customer, $attributes);
            } else {
                Log::warning("NAS not found for IP: {$activeSession->nasipaddress}");
            }
        }

        Log::info("Service for {$customer->username} is: {$customer->service}");
        try {
            DB::transaction(function () use ($customer, $createdBy) {
                // Always remove radusergroup entries for a clean slate
                DB::table('radusergroup')->where('username', $customer->username)->where('created_by', $customer->created_by)->delete();

                // Normalize the service string
                $service = trim(strtolower($customer->service));

                if (stripos($service, 'hotspot') !== false) {
                    // For Hotspot: Set up redirect to expired page instead of removing completely
                    // Remove old entries first
                    DB::table('radcheck')->where('username', $customer->username)->where('created_by', $createdBy)->delete();
                    DB::table('radreply')->where('username', $customer->username)->where('created_by', $createdBy)->delete();

                    // Add redirected URL entries to direct user to expired page
                    // $nasObj = $activeSession ? DB::table('nas')->where('nasname', $activeSession->nasipaddress)->first() : null;
                    // if ($nasObj) {
                    //     $nasIp = $nasObj->nasname;
                    //     $expiredUrl = route('hotspot.expired', ['nas_ip' => $nasIp]);

                    //     DB::table('radcheck')->insert([
                    //         ['username' => $customer->username, 'attribute' => 'Cleartext-Password', 'op' => ':=', 'value' => $customer->password, 'created_by' => $createdBy],
                    //     ]);

                    //     DB::table('radreply')->insert([
                    //         ['username' => $customer->username, 'attribute' => 'WISPr-Redirection-URL', 'op' => ':=', 'value' => $expiredUrl, 'created_by' => $createdBy],
                    //     ]);

                    //     DB::table('radusergroup')->insert([
                    //         'username'  => $customer->username,
                    //         'groupname' => 'Expired_Hotspot',
                    //         'priority'  => 1,
                    //         'created_by' => $createdBy,
                    //     ]); 

                    //     Log::info("Customer {$customer->username} set to redirect to expired page (Hotspot).");
                    // } else {
                        // Fallback to original behavior if we can't find the NAS
                        DB::table('radacct')->where('username', $customer->username)->where('created_by', $createdBy)->delete();
                        Log::info("Customer {$customer->username} removed from radius (Hotspot) - NAS not found for redirect.");

                        if($customer->parent_id !== null){
                            DB::table('customers')->where('username', $customer->username)->where('created_by', $createdBy)->delete();
                            Log::info("Child {$customer->username} removed from Customers (Hotspot) - NAS not found for redirect.");
                        }
                    // }
                } elseif ($service === 'pppoe') {
                    // For PPPoE: move to expired pool
                    DB::table('radusergroup')->insert([
                        'username'  => $customer->username,
                        'groupname' => 'Expired_Plan',
                        'priority'  => 1,
                        'created_by' => $customer->created_by,
                    ]);

                    Log::info("Customer {$customer->username} moved to expired pool (PPPoE).");

                    $phone = $customer->contact;
                    $smsTemplate = SmsAlert::where('type', 'User-Expired')
                        ->where('created_by', $customer->created_by)
                        ->first();

                    $templateText = $smsTemplate->template ?? 'Dear {username}, your internet connection has been terminated on {expiry}. Please, pay your subscription fee for activation.';

                    $txt = str_replace(
                        ['{username}', '{expiry}', '{account}', '{contact}', '{fullname}'],
                        [$customer->username, $customer->expiry, $customer->account, $customer->contact, $customer->fullname],
                        $templateText
                    );

                    self::sendAutoSMS($phone, $txt, $customer->created_by);

                } else {
                    Log::warning("Service type for {$customer->username} did not match known types. No action taken.");
                }

            });
        } catch (\Exception $e) {
            Log::error('Failed to clean customer records: ' . $e->getMessage());
        }
    }

    public static function kickOutUsersByRadius($nasObj, $userData, array $attributes)
    {
        $username = $userData->username;
        $nasport = $nasObj->incoming_port ?? 3799;
        $nassecret = $nasObj->secret;
        $nasname = $nasObj->nasname;
        $command = 'disconnect';

        if (!isset($attributes['acctSessionID'])) {
            Log::error("Missing required attributes for Disconnect: " . json_encode($attributes));
            return false;
        }

        $args = escapeshellarg("$nasname:$nasport") . ' ' . escapeshellarg($command) . ' ' . escapeshellarg($nassecret);
        $query = 'User-Name=' . escapeshellarg($username) .
                ',Acct-Session-Id=' . escapeshellarg($attributes['acctSessionID']) .
                ',Framed-IP-Address=' . escapeshellarg($attributes['framedIPAddress']);

        // $query = 'User-Name=' . escapeshellarg($username) .
        //         ',Acct-Session-Id=' . escapeshellarg($attributes['acctSessionID']);

        $cmd = 'echo ' . escapeshellarg($query) . ' | radclient -xr 1 ' . $args . ' 2>&1';

        $res = shell_exec($cmd);
        Log::info("Disconnect response for $username: " . $res);

        return (strpos($res, 'Received Disconnect-ACK') !== false);
    }

    public static function activateWithDeposit($customer_id)
    {
        $customer = Customer::findOrFail($customer_id);
        $package = $customer->package_id ?
            Package::find($customer->package_id) :
            Package::where('name_plan', $customer->package)->firstOrFail();
        $group_name = 'package_' . $package->id;
        $createdBy = $customer->created_by;
        $radiusUsername = $customer->username;
        $radiusPassword = $customer->password;
        $bandwidth = Bandwidth::where('package_id', $package->id)->first();

        if ($customer->is_override) {
            $MikroRate = "{$customer->override_upload}{$customer->override_upload_unit}/{$customer->override_download}{$customer->override_download_unit}";
        } else {
            $MikroRate = "{$bandwidth->rate_up}{$bandwidth->rate_up_unit}/{$bandwidth->rate_down}{$bandwidth->rate_down_unit}";
        }

        $activeSession = DB::table('radacct')
            ->where('username', $customer->username)
            ->where('created_by', $customer->created_by)
            ->whereNull('acctstoptime')
            ->orderBy('acctstarttime', 'desc')
            ->first();

        if ($activeSession) {
            $nasObj = DB::table('nas')->where('nasname', $activeSession->nasipaddress)->first();
            $attributes = [
                'acctSessionID' => $activeSession->acctsessionid,
                'framedIPAddress' => $activeSession->framedipaddress,
            ];

            self::kickOutUsersByRadius($nasObj, $customer, $attributes);
        }
        DB::table('radcheck')->where('username', $customer->username)->where('created_by', $createdBy)->delete();
        DB::table('radreply')->where('username', $customer->username)->where('created_by', $createdBy)->delete();
        DB::table('radcheck')->insert([
            ['username' => $radiusUsername, 'attribute' => 'Cleartext-Password', 'op' => ':=', 'value' => $radiusPassword, 'created_by' => $createdBy],
        ]);
        DB::table('radreply')->insert([
            ['username' => $radiusUsername, 'attribute' => 'Mikrotik-Rate-Limit', 'op' => ':=', 'value' => $MikroRate, 'created_by' => $createdBy],
        ]);

        DB::table('radusergroup')->where('username', $customer->username)->where('created_by', $createdBy)->delete();
        DB::table('radusergroup')->insert([
            'username'  => $customer->username,
            'groupname' => $group_name,
            'priority'  => 1,
            'created_by' => $createdBy,
        ]);

        $packagePrice = $package->price;

        $baseTime = Carbon::now();

        // Handle package validity
        if ($package->validity_unit === 'Months') {
            $newExpiry = $baseTime->copy()->addMonths($package->validity);
        } else {
            $timelimit = match ($package->validity_unit) {
                'Minutes' => $package->validity * 60,
                'Hours'   => $package->validity * 3600,
                'Days'    => $package->validity * 86400,
                // default   => 0,
            };

            $newExpiry = $baseTime->copy()->addSeconds($timelimit);
        }

        // Check if customer has extension Days and apply extension logic
        if (
            $customer->is_extended === 1 &&
            $customer->extension_start &&
            $customer->extension_expiry
        ) {
            $start = Carbon::parse($customer->extension_start);
            $end = Carbon::parse($customer->extension_expiry);

            if ($baseTime->gte($start)) {
                $usedSeconds = $baseTime->diffInSeconds($start);
                $totalExtensionSeconds = $start->diffInSeconds($end);

                $usedSeconds = min($usedSeconds, $totalExtensionSeconds);
                $newExpiry = $newExpiry->subSeconds($usedSeconds);
            }
        }

        $newExpiry = $newExpiry->toDateTimeString();
        $customer->expiry = $newExpiry;
        $customer->status = 'on';
        $customer->is_extended = 0;
        $customer->extension_start = null;
        $customer->extension_expiry = null;
        $customer->save();
    }

    public static function editExpiry($customer_id)
    {
        $customer = Customer::findOrFail($customer_id);
        $package = $customer->package_id ?
            Package::find($customer->package_id) :
            Package::where('name_plan', $customer->package)->firstOrFail();
        $group_name = 'package_' . $package->id;
        $isp = $customer->created_by;

        $activeSession = DB::table('radacct')
            ->where('username', $customer->username)
            ->where('created_by', $customer->created_by)
            ->whereNull('acctstoptime')
            ->orderBy('acctstarttime', 'desc')
            ->first();

        if ($activeSession) {
            $nasObj = DB::table('nas')->where('nasname', $activeSession->nasipaddress)->first();
            $attributes = [
                'acctSessionID' => $activeSession->acctsessionid,
                'framedIPAddress' => $activeSession->framedipaddress,
            ];

            self::kickOutUsersByRadius($nasObj, $customer, $attributes);
        }

        $bandwidth = Bandwidth::where('package_id', $package->id)->first();
        if ($customer->is_override) {
            $MikroRate = "{$customer->override_upload}{$customer->override_upload_unit}/{$customer->override_download}{$customer->override_download_unit}";
        } else {
            $MikroRate = "{$bandwidth->rate_up}{$bandwidth->rate_up_unit}/{$bandwidth->rate_down}{$bandwidth->rate_down_unit}";
        }
        // Ensure no customer's radcheck entry exists to avoid duplicates
        DB::table('radcheck')->where('username', $customer->username)->where('created_by', $isp)->delete();
        DB::table('radreply')->where('username', $customer->username)->where('created_by', $isp)->delete();
        DB::table('radusergroup')->where('username', $customer->username)->where('created_by', $isp)->delete();


        DB::table('radcheck')->insert([
            'username' => $customer->username, 'attribute' => 'Cleartext-Password', 'op' => ':=', 'value' => $customer->password, 'created_by' => $isp,
        ]);

        DB::table('radreply')->insert([
            ['username' => $customer->username, 'attribute' => 'Mikrotik-Rate-Limit', 'op' => ':=', 'value' => $MikroRate, 'created_by' => $isp],
        ]);

        DB::table('radusergroup')->insert([
            'username' => $customer->username, 'groupname' => $group_name, 'priority' => 1, 'created_by' => $isp,
        ]);

        $customer->status = 'on';
        $customer->save();
    }

    public static function getMacVendor($mac)
    {
        $mac = strtoupper($mac);
        $apiUrl = "https://api.macvendors.com/{$mac}";

        try {
            $response = Http::get($apiUrl);

            if ($response->successful()) {
                $vendor = $response->body();
                $words = explode(' ', $vendor);
                $trimmed = implode(' ', array_slice($words, 0, 2));
                return $trimmed;
            }

            return "Unknown Device";
        } catch (\Exception $e) {
            return "Unknown Device";
        }
    }

    public static function generateInvoice($customer, $type, $amount)
    {
        return Invoice::create([
            'invoice_id' => self::invoiceNumber(),
            'customer_id' => $customer->id,
            'issue_date' => now(),
            'due_date' => now(),
            'send_date' => now(),
            'ref_number' => Auth::user()->invoiceNumberFormat(self::invoiceNumber()),
            'status' => 'Unpaid',
            'category' => $type,
            'created_by' => auth()->id(),
        ]);
    }

    public static function recordInvoicePayment($customer, $invoice, $amount)
    {
        return InvoicePayment::create([
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'payment_method' => 'Balance',
            'date' => now(),
            'created_by' => auth()->id(),
        ]);
    }

    public static function updateInvoiceStatus($invoice)
    {
        if ($invoice->getDue() <= 0) {
            $invoice->status = 'Paid';
            $invoice->save();
        }
    }

    public static function processTransaction($invoicePayment, $user_id)
    {
        $invoicePayment->user_id = $user_id;
        $invoicePayment->user_type = 'Customer';
        $invoicePayment->type = 'Partial';
        $invoicePayment->created_by = auth()->id();
        $invoicePayment->payment_id = $invoicePayment->id;
        $invoicePayment->category = 'Invoice';

        Transaction::addTransaction($invoicePayment);
    }

    public static function sendInvoiceNotification($customer, $invoice, $amount)
    {
        $settings = Utility::settings();
        if ($settings['new_invoice_payment'] == 1) {
            $invoicePaymentArr = [
                'invoice_payment_name' => $customer->name,
                'invoice_payment_amount' => $amount,
                'invoice_payment_date' => now()->format('Y-m-d'),
                'payment_dueAmount' => $invoice->getDue(),
                'invoice_number' => Auth::user()->invoiceNumberFormat($invoice->invoice_id),
                'invoice_payment_method' => 'Balance',
            ];

            // Utility::sendEmailTemplate('new_invoice_payment', [$customer->id => $customer->email], $invoicePaymentArr);
        }
    }

    public static function invoiceNumber()
    {
        $latest = Invoice::where('created_by', '=', \Auth::user()->creatorId())->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->invoice_id + 1;
    }

    public static function sendTelegram($txt)
    {
        $bot = config('services.telegram.bot');
        $chatId = config('services.telegram.target_id');

        if (!empty($bot) && !empty($chatId)) {
            return Http::get("https://api.telegram.org/bot{$bot}/sendMessage", [
                'query' => [
                    'chat_id' => $chatId,
                    'text' => $txt
                ]
            ]);
        }
    }

    public static function sendWhatsapp($phone, $txt, $createdBy)
    {
        if (empty($txt)) {
            return "";
        }
        Log::info("sendWhatsapp called for: {$phone}");
        Log::info("sendWhatsapp called for: {$txt}");
        // Notification Handling
        $settings = DB::table('settings')
            ->where('created_by', $createdBy)
            ->pluck('value', 'name')
            ->toArray();

        Log::info("Sms Settings for created_by={$createdBy}:", $settings);

        $waUrl = $settings['whatsapp_url'] ?? null;
        Log::info("Whatsapp Url: " . $waUrl);

        if (!empty($waUrl)) {
            $waUrl = str_replace(['[number]', '[text]'], [urlencode($phone), urlencode($txt)], $waUrl);
            Log::info("Whatsapp Url: " . $waUrl);
            return Http::get($waUrl);
        }
    }

    public static function sendSMS($phone, $txt, $createdBy)
    {
        if (empty($txt)) {
            return "";
        }

        $settings = DB::table('settings')
            ->where('created_by', $createdBy)
            ->pluck('value', 'name')
            ->toArray();

        // Log::info("Sms Settings for created_by={$createdBy}:", $settings);

        $smsUrl   = $settings['sms_url'] ?? null;
        $senderId = $settings['sms_senderid'] ?? null;
        $apiToken = $settings['sms_apitoken'] ?? null;
        $patnerId = $settings['sms_patnerid'] ?? null;

        if($createdBy === 12){
            $smsUrl = 'https://stech.smartisp.co.ke/bytewave.php?message=[text]&phone=[number]&senderid=[senderid]&api=[apikey]';
            $senderId = 'BytewaveSMS';
            $apiToken = '418|07VpXmnkfXw9S4nKVvIm9BeLx9TRI7kUrTfA6jLy89588db8';
        }

        if (!empty($smsUrl)) {
            if (!empty($patnerId)) {
                $smsUrl = str_replace(
                    ['[number]', '[text]', '[apikey]', '[patnerid]', '[senderid]'],
                    [urlencode($phone), urlencode($txt), urlencode($apiToken), urlencode($patnerId), urlencode($senderId)],
                    $smsUrl
                );
            } else {
                $smsUrl = str_replace(
                    ['[number]', '[text]', '[apikey]', '[senderid]'],
                    [urlencode($phone), urlencode($txt), urlencode($apiToken), urlencode($senderId)],
                    $smsUrl
                );
            }
            return Http::get($smsUrl);
        }

        return null;
    }

    public static function handleReminder($customer)
    {
        $service = trim(strtolower($customer->service));

        if (stripos($service, 'hotspot') !== false) {
            // For Hotspot: remove radcheck to fully block login
        } elseif ($service === 'pppoe') {
            $phone = $customer->contact;
            $smsTemplate = SmsAlert::where('type', 'Expiry-Notice')->where('created_by', $customer->created_by)->first();
            $templateText = $smsTemplate->template ?? 'Dear {username}, your internet connection will be terminated on {expiry}. Please, pay your subscription fee before termination.';
            $txt = str_replace(
                ['{username}', '{expiry}', '{account}', '{contact}', '{fullname}'],
                [$customer->username, $customer->expiry, $customer->account, $customer->contact, $customer->fullname],
                $templateText
            );
            self::sendAutoSMS($phone, $txt, $customer->created_by);
        } else {
            Log::warning("Service type for {$customer->username} did not match known types. No action taken.");
        }
    }

    public static function sendAutoSMS($phone, $txt, $createdBy)
    {
        if (empty($txt)) {
            return "";
        }

        $settings = DB::table('settings')
            ->where('created_by', $createdBy)
            ->pluck('value', 'name')
            ->toArray();

        // Log::info("Sms Settings for created_by={$createdBy}:", $settings);

        $smsUrl   = $settings['sms_url'] ?? null;
        $senderId = $settings['sms_senderid'] ?? null;
        $apiToken = $settings['sms_apitoken'] ?? null;
        $patnerId = $settings['sms_patnerid'] ?? null;

        if($createdBy === 12){
            $smsUrl = 'https://stech.smartisp.co.ke/bytewave.php?message=[text]&phone=[number]&senderid=[senderid]&api=[apikey]';
            $senderId = 'BytewaveSMS';
            $apiToken = '418|07VpXmnkfXw9S4nKVvIm9BeLx9TRI7kUrTfA6jLy89588db8';
        }

        if (!empty($smsUrl)) {
            if (!empty($patnerId)) {
                $smsUrl = str_replace(
                    ['[number]', '[text]', '[apikey]', '[patnerid]', '[senderid]'],
                    [urlencode($phone), urlencode($txt), urlencode($apiToken), urlencode($patnerId), urlencode($senderId)],
                    $smsUrl
                );
            } else {
                $smsUrl = str_replace(
                    ['[number]', '[text]', '[apikey]', '[senderid]'],
                    [urlencode($phone), urlencode($txt), urlencode($apiToken), urlencode($senderId)],
                    $smsUrl
                );
            }

            return Http::get($smsUrl);
        }

        return null;
    }



    public static function processTransactionH($amount, $user_id, $isp, $ref)
    {
        $transaction = Transaction::where('checkout_id', $ref)->first();
        $transaction->user_id = $user_id;
        $transaction->user_type = 'Customer';
        $transaction->type = 'Fully';
        $transaction->created_by = $isp;
        $transaction->payment_id = $ref; 
        $transaction->amount = $amount;
        $transaction->category = 'Hotspot';
        $transaction->status = 1;
        $transaction->save();
    }

    public static function sendInvoiceNotificationH($customer, $invoice, $amount)
    {
        $settings = Utility::settings();
        $user = User::find($customer->created_by);
        if ($settings['new_invoice_payment'] == 1) {
            $invoicePaymentArr = [
                'invoice_payment_name' => $customer->name,
                'invoice_payment_amount' => $amount,
                'invoice_payment_date' => now()->format('Y-m-d'),
                'payment_dueAmount' => $invoice->getDue(),
                'invoice_number' => $user->invoiceNumberFormat($invoice->invoice_id),
                'invoice_payment_method' => 'Balance',
            ];
            Utility::sendEmailTemplate('new_invoice_payment', [$customer->id => $customer->email], $invoicePaymentArr);
        }
    }

    public static function invoiceNumberH($user)
    {
        $latest = Invoice::where('created_by', '=', $user->creatorId())->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->invoice_id + 1;
    }

    // public static function rechargeUser($customer, $package_id, $isp, $ref)
    // {
    //     $cus = $customer->username;
    //     Log::info('Called recharge for:' . $cus);
    //     DB::transaction(function () use ($customer, $package_id, $isp, $ref) {
    //         $package = Package::with('bandwidth')->where('id', $package_id)->where('created_by', $isp)->first();
    //         $radiusGroup = 'package_' . $package->id;
    //         if (!$package) {
    //             Log::error('Package not found for package_id ' . $package_id);
    //             return response()->json(['success' => false, 'message' => 'Package not found']);
    //         }

    //         $bandwidth = Bandwidth::where('package_id', $package->id)->first();
    //         if ($customer->is_override) {
    //             $MikroRate = "{$customer->override_upload}{$customer->override_upload_unit}/{$customer->override_download}{$customer->override_download_unit}";
    //         } else {
    //             $MikroRate = "{$bandwidth->rate_up}{$bandwidth->rate_up_unit}/{$bandwidth->rate_down}{$bandwidth->rate_down_unit}";
    //         }

    //         // Ensure no customer's radcheck entry exists to avoid duplicates
    //         DB::table('radcheck')->where('username', $customer->username)->where('created_by', $isp)->delete();
    //         DB::table('radreply')->where('username', $customer->username)->where('created_by', $isp)->delete();
    //         DB::table('radusergroup')->where('username', $customer->username)->where('created_by', $isp)->delete();


    //         DB::table('radcheck')->insert([
    //             'username' => $customer->username, 'attribute' => 'Cleartext-Password', 'op' => ':=', 'value' => $customer->password, 'created_by' => $isp,
    //         ]);

    //         DB::table('radreply')->insert([
    //             ['username' => $customer->username, 'attribute' => 'Mikrotik-Rate-Limit', 'op' => ':=', 'value' => $MikroRate, 'created_by' => $isp],
    //         ]);

    //         DB::table('radusergroup')->insert([
    //             'username' => $customer->username, 'groupname' => $radiusGroup, 'priority' => 1, 'created_by' => $isp,
    //         ]);

    //         $baseTime = Carbon::now();
    //         if ($package->validity_unit === 'Months') {
    //             if ($customer->status === 'off' || Carbon::now()->gt($baseTime)) {
    //                 $newExpiry = Carbon::now()->addMonths($package->validity);
    //             } else {
    //                 $newExpiry = $baseTime->addMonths($package->validity);
    //             }
    //         } else {
    //             $timelimit = match ($package->validity_unit) {
    //                 'Minutes' => $package->validity * 60,
    //                 'Hours'   => $package->validity * 3600,
    //                 'Days'    => $package->validity * 86400,
    //                 default   => 0,
    //             };

    //             if ($customer->status === 'off' || Carbon::now()->gt($baseTime)) {
    //                 $newExpiry = Carbon::now()->addSeconds($timelimit);
    //             } else {
    //                 $newExpiry = $baseTime->addSeconds($timelimit);
    //             }
    //         }

    //         $customer->expiry = $newExpiry->toDateTimeString();
    //         $customer->status = 'on';
    //         $customer->save();

    //         $type = 'Hotspot';
    //         $amount = $package->price;
    //         self::processTransactionH($amount, $customer->id, $isp, $ref);

    //         Log::info("Customer Recharged sussessfully");
    //         return response()->json(['success' => true, 'message' => 'C']);
    //     });
    // }
    public static function rechargeUser($customer, $package_id, $isp, $ref)
{
    $cus = $customer->username;
    Log::info("[Recharge Start] Called recharge for: {$cus}, ISP: {$isp}, Package ID: {$package_id}, Ref: {$ref}");

    DB::transaction(function () use ($customer, $package_id, $isp, $ref) {
        $cus = $customer->username;

        Log::info("[Recharge Step] Fetching package...");
        $package = Package::with('bandwidth')->where('id', $package_id)->where('created_by', $isp)->first();
        if (!$package) {
            Log::error("[Recharge Error] Package not found for ID: {$package_id} by ISP: {$isp}");
            return response()->json(['success' => false, 'message' => 'Package not found']);
        }
        Log::info("[Recharge Step] Package found: {$package->name}");

        $bandwidth = Bandwidth::where('package_id', $package->id)->first();
        if (!$bandwidth) {
            Log::error("[Recharge Error] Bandwidth not defined for package ID: {$package->id}");
        }

        if ($customer->is_override) {
            $MikroRate = "{$customer->override_upload}{$customer->override_upload_unit}/{$customer->override_download}{$customer->override_download_unit}";
            Log::info("[Recharge Step] Using override speed: {$MikroRate}");
        } else {
            $MikroRate = "{$bandwidth->rate_up}{$bandwidth->rate_up_unit}/{$bandwidth->rate_down}{$bandwidth->rate_down_unit}";
            Log::info("[Recharge Step] Using package speed: {$MikroRate}");
        }

        Log::info("[Recharge Step] Cleaning old radius entries for {$cus}");
        DB::table('radcheck')->where('username', $cus)->where('created_by', $isp)->delete();
        DB::table('radreply')->where('username', $cus)->where('created_by', $isp)->delete();
        DB::table('radusergroup')->where('username', $cus)->where('created_by', $isp)->delete();

        Log::info("[Recharge Step] Inserting new radcheck, radreply, radusergroup for {$cus}");
        DB::table('radcheck')->insert([
            'username' => $cus,
            'attribute' => 'Cleartext-Password',
            'op' => ':=',
            'value' => $customer->password,
            'created_by' => $isp,
        ]);

        DB::table('radreply')->insert([
            [
                'username' => $cus,
                'attribute' => 'Mikrotik-Rate-Limit',
                'op' => ':=',
                'value' => $MikroRate,
                'created_by' => $isp,
            ],
        ]);

        $radiusGroup = 'package_' . $package->id;
        DB::table('radusergroup')->insert([
            'username' => $cus,
            'groupname' => $radiusGroup,
            'priority' => 1,
            'created_by' => $isp,
        ]);

        Log::info("[Recharge Step] Calculating expiry time for {$cus}");
        $baseTime = Carbon::now();
        if ($package->validity_unit === 'Months') {
            $newExpiry = ($customer->status === 'off' || now()->gt($baseTime))
                ? now()->addMonths($package->validity)
                : $baseTime->addMonths($package->validity);
        } else {
            $seconds = match ($package->validity_unit) {
                'Minutes' => $package->validity * 60,
                'Hours' => $package->validity * 3600,
                'Days' => $package->validity * 86400,
                default => 0,
            };
            $newExpiry = ($customer->status === 'off' || now()->gt($baseTime))
                ? now()->addSeconds($seconds)
                : $baseTime->addSeconds($seconds);
        }

        Log::info("[Recharge Step] Setting expiry for {$cus} to: {$newExpiry}");
        $customer->expiry = $newExpiry->toDateTimeString();
        $customer->status = 'on';
        $customer->save();

        $amount = $package->price;
        Log::info("[Recharge Step] Processing transaction for user_id: {$customer->id}, amount: {$amount}, ref: {$ref}");
        self::processTransactionH($amount, $customer->id, $isp, $ref);

        Log::info("[Recharge Complete] Recharge completed for {$cus}, new expiry: {$customer->expiry}");
        return response()->json(['success' => true, 'message' => 'Recharged']);
    });
}


    public static function generateInvoiceP($customer, $type, $amount)
    {
        $user = User::find($customer->created_by);
        return Invoice::create([
            'invoice_id' => self::invoiceNumberP($user),
            'customer_id' => $customer->id,
            'issue_date' => now(),
            'due_date' => now(),
            'send_date' => now(),
            'ref_number' => $user->invoiceNumberFormat(self::invoiceNumberP($user)),
            'status' => 'Unpaid',
            'category' => $type,
            'created_by' => $customer->created_by,
        ]);
    }

    public static function recordInvoicePaymentP($customer, $invoice, $amount)
    {
        $user = User::find($customer->created_by);
        return InvoicePayment::create([
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'payment_method' => 'Mpesa',
            'date' => now(),
            'created_by' => $user->id,
        ]);
    }

    public static function updateInvoiceStatusP($invoice)
    {
        $invoice->status = 'Paid';
        $invoice->save();
    }

    public static function processTransactionP($amount, $user_id, $isp, $normalizedBillRefNumber, $TransID, $transactionDate, $customer, $package)
    {

        $transaction = new Transaction();
        $transaction->user_id = $customer->id;
        $transaction->user_type = 'Customer';
        $transaction->type = 'Fully';
        $transaction->created_by = $isp;
        $transaction->checkout_id = $normalizedBillRefNumber;
        $transaction->mpesa_code = $TransID;
        $transaction->date = $transactionDate;
        $transaction->phone = $customer->contact;
        $transaction->package_id = $package->id;
        // $transaction->payment_id = $invoicePayment->id;
        $transaction->amount = $amount;
        $transaction->category = 'PPPoE';
        $transaction->status = 1;
        $transaction->save();

    }

    public static function sendInvoiceNotificationP($customer, $invoice, $amount)
    {
        $settings = Utility::settings();
        $user = User::find($customer->created_by);
        if ($settings['new_invoice_payment'] == 1) {
            $invoicePaymentArr = [
                'invoice_payment_name' => $customer->name,
                'invoice_payment_amount' => $amount,
                'invoice_payment_date' => now()->format('Y-m-d'),
                'payment_dueAmount' => $invoice->getDue(),
                'invoice_number' => $user->invoiceNumberFormat($invoice->invoice_id),
                'invoice_payment_method' => 'MPESA',
            ];

            Utility::sendEmailTemplate('new_invoice_payment', [$customer->id => $customer->email], $invoicePaymentArr);
        }
    }

    public static function invoiceNumberP($user)
    {
        $latest = Invoice::where('created_by', '=', $user->creatorId())->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->invoice_id + 1;
    }

    /**
     * Efficiently retrieve payment gateway details optimized for multi-tenancy
     * This version minimizes DB queries and uses caching to improve performance
     */
    public static function getOptimizedPaymentGateway($user_id, $paymentType)
    {
        // Define cache key based on user and payment type
        $cacheKey = "payment_gateway_fast_{$user_id}_{$paymentType}";

        // Try to get from cache first with short TTL for critical payment data
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // If not in cache, get with minimal DB queries
        // Use query builder directly instead of Eloquent to reduce overhead
        $user = DB::table('users')
            ->select(['id', 'hotspot_pay', 'pppoe_pay', 'payment_settings'])
            ->where('id', $user_id)
            ->first();

        if (!$user) {
            Log::error("User not found for payment gateway", ['user_id' => $user_id]);
            return ['success' => false, 'message' => 'User not found'];
        }

        // Determine gateway based on payment type
        $gateway = '';
        switch ($paymentType) {
            case 'Hotspot':
                $gateway = $user->hotspot_pay;
                break;
            case 'PPPoE':
                $gateway = $user->pppoe_pay;
                break;
            // default:
            //     Log::error("Unsupported payment type", ['type' => $paymentType]);
            //     return ['success' => false, 'message' => "Unsupported payment type: $paymentType"];
        }

        // Decode company settings once
        $companySettings = json_decode($user->payment_settings, true) ?: [];

        // Get admin settings if needed (only for system APIs)
        $adminSettings = [];
        $adminId = 1; // Assuming admin ID is 1

        // Check if we need admin settings
        $needsAdminSettings = false;

        if (($gateway === 'bank' && ($companySettings['bank']['is_system_api_enabled'] ?? 'off') === 'on') ||
            ($gateway === 'paybill' && ($companySettings['paybill']['is_system_api_enabled'] ?? 'off') === 'on') ||
            ($gateway === 'till' && ($companySettings['till']['is_system_api_enabled'] ?? 'off') === 'on')) {
            $needsAdminSettings = true;
        }

        // Only fetch admin settings if really needed
        if ($needsAdminSettings) {
            $admin = DB::table('users')
                ->select(['payment_settings'])
                ->where('id', $adminId)
                ->first();

            if ($admin) {
                $adminSettings = json_decode($admin->payment_settings, true) ?: [];
            }
        }

        // Extract only needed settings based on gateway type
        $paymentDetails = [];

        switch ($gateway) {
            case 'mpesa':
                $paymentDetails = [
                    'key'       => $companySettings['mpesa']['key'] ?? '',
                    'secret'    => $companySettings['mpesa']['secret'] ?? '',
                    'shortcode' => $companySettings['mpesa']['shortcode'] ?? '',
                    'passkey'   => $companySettings['mpesa']['passkey'] ?? '',
                    'partyB'    => $companySettings['mpesa']['shortcode'] ?? '',
                    'mode'      => 'mpesa',
                    'TransType' => ($companySettings['mpesa']['shortcode_type'] ?? '') === 'paybill'
                                ? 'CustomerPayBillOnline'
                                : 'CustomerBuyGoodsOnline',
                ];
                break;

            case 'bank':
                $api = $companySettings['bank']['is_system_api_enabled'] ?? 'off';
                if ($api === 'on' && !empty($adminSettings)) {
                    $paymentDetails = [
                        'key'       => $adminSettings['paybill_bank']['key'] ?? '',
                        'secret'    => $adminSettings['paybill_bank']['secret'] ?? '',
                        'shortcode' => $adminSettings['paybill_bank']['shortcode'] ?? '',
                        'passkey'   => $adminSettings['paybill_bank']['passkey'] ?? '',
                        'partyB'    => $companySettings['bank']['paybill'] ?? '',
                        'ref'       => $companySettings['bank']['account'] ?? '',
                        'TransType' => 'CustomerPayBillOnline',
                    ];
                } else {
                    $paymentDetails = [
                        'key'       => $companySettings['mpesa']['key'] ?? '',
                        'secret'    => $companySettings['mpesa']['secret'] ?? '',
                        'shortcode' => $companySettings['mpesa']['shortcode'] ?? '',
                        'passkey'   => $companySettings['mpesa']['passkey'] ?? '',
                        'partyB'    => $companySettings['bank']['paybill'] ?? '',
                        'ref'       => $companySettings['bank']['account'] ?? '',
                        'TransType' => 'CustomerPayBillOnline',
                    ];
                }
                break;

            case 'paybill':
                $api = $companySettings['paybill']['is_system_api_enabled'] ?? 'off';
                if ($api === 'on' && !empty($adminSettings)) {
                    $paymentDetails = [
                        'key'       => $adminSettings['paybill_bank']['key'] ?? '',
                        'secret'    => $adminSettings['paybill_bank']['secret'] ?? '',
                        'shortcode' => $adminSettings['paybill_bank']['shortcode'] ?? '',
                        'passkey'   => $adminSettings['paybill_bank']['passkey'] ?? '',
                        'partyB'    => $companySettings['paybill']['paybill'] ?? '',
                        'ref'       => $companySettings['paybill']['account'] ?? '',
                        'TransType' => 'CustomerPayBillOnline',
                    ];
                } else {
                    $paymentDetails = [
                        'key'       => $companySettings['mpesa']['key'] ?? '',
                        'secret'    => $companySettings['mpesa']['secret'] ?? '',
                        'shortcode' => $companySettings['mpesa']['shortcode'] ?? '',
                        'passkey'   => $companySettings['mpesa']['passkey'] ?? '',
                        'partyB'    => $companySettings['paybill']['paybill'] ?? '',
                        'ref'       => $companySettings['paybill']['account'] ?? '',
                        'TransType' => 'CustomerPayBillOnline',
                    ];
                }
                break;

            case 'till':
                $api = $companySettings['till']['is_system_api_enabled'] ?? 'off';
                if ($api === 'on' && !empty($adminSettings)) {
                    $paymentDetails = [
                        'key'       => $adminSettings['till']['key'] ?? '',
                        'secret'    => $adminSettings['till']['secret'] ?? '',
                        'shortcode' => $adminSettings['till']['shortcode'] ?? '',
                        'passkey'   => $adminSettings['till']['passkey'] ?? '',
                        'partyB'    => $companySettings['till']['till'] ?? '',
                        'ref'       => $companySettings['till']['account'] ?? '',
                        'TransType' => 'CustomerBuyGoodsOnline',
                    ];
                } else {
                    $paymentDetails = [
                        'key'       => $companySettings['mpesa']['key'] ?? '',
                        'secret'    => $companySettings['mpesa']['secret'] ?? '',
                        'shortcode' => $companySettings['mpesa']['shortcode'] ?? '',
                        'passkey'   => $companySettings['mpesa']['passkey'] ?? '',
                        'partyB'    => $companySettings['till']['till'] ?? '',
                        'ref'       => $companySettings['till']['account'] ?? '',
                        'TransType' => 'CustomerBuyGoodsOnline',
                    ];
                }
                break;

            // default:
            //     Log::error("Unsupported payment gateway", ['gateway' => $gateway]);
            //     return ['success' => false, 'message' => "Unsupported gateway: {$gateway}"];
        }

        // Calculate and log the execution time
        // Log::info("Optimized gateway retrieval:", [
        //     'user_id' => $user_id,
        //     'payment_type' => $paymentType
        // ]);

        // Cache the result (5 minutes is a good balance between performance and freshness)
        // Use Cache::put with seconds as the third parameter (300 seconds = 5 minutes)
        Cache::put($cacheKey, $paymentDetails, 300);

        return $paymentDetails;
    }

   

    /**
     * Optimized method for initiating STK push with performance monitoring
     * This version uses the optimized gateway retrieval and direct cURL calls
     */
    public static function fastInitiateSTKPush($account, $phone, $amount, $user_id, $paymentType)
    {
        $user_id = $user_id;
        // Log::info("User ID: " . $user_id);
        try {
            // Step 1: Get optimized gateway settings
            $settings = self::getOptimizedPaymentGateway($user_id, $paymentType);
            // Log::info($settings);
            if (!isset($settings['key'], $settings['secret'], $settings['shortcode'], $settings['passkey'])) {
                Log::error("Incomplete payment settings", [
                    'user_id' => $user_id,
                    'payment_type' => $paymentType,
                    'settings' => $settings
                ]);
                return ['success' => false, 'message' => 'Incomplete payment settings.'];
            }

            // Set reference based on settings
            $ref = $settings['ref'] ?? null;
            if (($settings['mode'] ?? '') === 'mpesa') {
                $ref = $account;
            }

            // Step 2: Get access token with direct cURL
            $consumerKey = $settings['key'];
            $consumerSecret = $settings['secret'];

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

            if (!$access_token) {
                Log::error("Invalid access token response", ['response' => $response]);
                return ['success' => false, 'message' => 'Invalid access token response.'];
            }

            // Step 3: Prepare STK push request
            $stk_url = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
            $PartyA = $phone;
            $PartyB = $settings['partyB'];
            $AccountReference = $ref ?? $account;
            $TransactionDesc = 'Payment';
            $Amount = (int)$amount;
            $BusinessShortCode = $settings['shortcode'];
            $Passkey = $settings['passkey'];
            $Timestamp = date("YmdHis", time());
            $Password = base64_encode($BusinessShortCode.$Passkey.$Timestamp);
            $CallBackURL = 'https://app.ekinpay.com/api/hs/mpesa-callback';

            $curl_post_data = [
                'BusinessShortCode' => $BusinessShortCode,
                'Password' => $Password,
                'Timestamp' => $Timestamp,
                'TransactionType' => $settings['TransType'] ?? 'CustomerPayBillOnline',
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

            $mpesaResponse = json_decode($curl_response);


            // Log timing data
            Log::info('Optimized STK Push', [
                'user_id' => $user_id,
                'payment_type' => $paymentType,
                'checkout_request_id' => $mpesaResponse->CheckoutRequestID ?? null
            ]);

            return $mpesaResponse;
        } catch (\Exception $e) {
            // End timing and log the error
            Log::error('Exception in fastInitiateSTKPush', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => $user_id,
                'payment_type' => $paymentType
            ]);

            return ['success' => false, 'message' => 'Payment processing failed.'];
        }
    }

    public static function fastMpesaQuery($ref, $user_id, $paymentType)
    {
        $settings = self::getOptimizedPaymentGateway($user_id, $paymentType);
        if (!isset($settings['key'], $settings['secret'], $settings['shortcode'], $settings['passkey'])) {
            return ['success' => false, 'message' => 'Incomplete payment settings.'];
        }

        $timestamp = Carbon::rawParse('now')->format('YmdHis');
        $password  = base64_encode($settings['shortcode'] . $settings['passkey'] . $timestamp);
        $consumerKey =  $settings['key'];
        $consumerSecret = $settings['secret'];
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

        if (!$access_token) {
            Log::error("Invalid access token response", ['response' => $response]);
            return ['success' => false, 'message' => 'Invalid access token response.'];
        }
        $stkQueryData = [
            "BusinessShortCode" => $settings['shortcode'],
            "Password"          => $password,
            "Timestamp"         => $timestamp,
            "CheckoutRequestID" => $ref
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

        $data = json_decode($response, true);
        return  $data;
    }

    /**
     * Command to pre-warm payment gateway settings cache for all tenants
     * Also running as a scheduled task to ensure optimal performance
     */
    public static function prewarmPaymentGatewayCache()
    {
        // Get all active users who have payment settings
        $users = DB::table('users')
            ->whereNotNull('payment_settings')
            ->select(['id'])
            ->get();

        $count = 0;

        foreach ($users as $user) {
            // Pre-warm cache for both payment types
            self::getOptimizedPaymentGateway($user->id, 'Hotspot');
            self::getOptimizedPaymentGateway($user->id, 'PPPoE');
            $count++;
        }

        return [
            'success' => true,
            'message' => "Pre-warmed cache for {$count} users"
        ];
    }
    public static function handlePayments($amount, $package, $customer, $transactionDate, $TransID, $isp, $type)
    {
        $amount = (int) $amount;
        $refNumber = $customer->account;

        if ($amount <= 0) return;

        // Normalize SMS template
        $templateType = $type === "MPESA" ? 'Mpesa-Payment' : 'Deposit-Balance';
        $smsTemplate = SmsAlert::where('type', $templateType)->where('created_by', $isp)->first();
        $templateText = $smsTemplate->template ?? 'Dear {username}, Ksh {amount} has been deposited into your account.';

        $txt = str_replace(
            ['{username}', '{amount}', '{fullname}', '{account}'],
            [$customer->username, $amount, $customer->fullname, $customer->account],
            $templateText
        );

        $result = self::applyPaymentAndRenew($customer, $amount, $package, $transactionDate, $TransID, $refNumber, $isp);

        $msg = $txt . ' ' . $result['message'];
        self::sendAutoSMS($customer->contact, $msg, $isp);
    }

    public static function applyPaymentAndRenew($customer, $amount, $package, $transactionDate, $TransID, $refNumber, $isp)
    {
        $prevBalance = (int)$customer->balance;
        $charges = (int)$customer->charges;
        $packagePrice = $package->price;
        $resultMessage = "";
        
        // Log::info('Before top-up: balance = ' . $customer->balance . ', amount = ' . $amount);

        // Step 1: Deduct charges
        if ($charges > 0) {
            $amount -= $charges;
            $customer->charges = 0;
            $customer->save();
        }

        // Step 2: Clear any debt first
        if ($prevBalance < 0) {
            $owed = abs($prevBalance);
            $amount -= $owed;

            if ($amount < 0) {
                // Not enough to clear debt
                $customer->balance += ($amount + $owed);
                $customer->save();
                return ['message' => 'Partial debt payment received.'];
            }

            $customer->balance = 0;
        }

        if ( ($customer->is_extended === 1 && $amount >= $packagePrice) || ($customer->expiry < now() && $amount >= $packagePrice)) {
            if (!str_starts_with($TransID, 'REN-')) {
                $customer->balance += $amount;
            }
            $customer->save();
            self::activateWithDepositm($customer->id, $packagePrice); 
            self::processTransactionP($amount, $customer->created_by, $isp, $refNumber, $TransID, $transactionDate, $customer, $package);
            return ['message' => 'Package renewed successfully.'];
        }

        // Step 4: Top-up 
        $customer->balance += $amount;
        $customer->save();
        // Log::info('After top-up, after activation: balance = ' . $customer->balance);
        return ['message' => 'Balance topped up.'];
    } 

    public static function activateWithDepositm($customer_id, $packagePrice = null)
    {
        $customer = Customer::findOrFail($customer_id);
        $package = $customer->package_id ?
            Package::find($customer->package_id) :
            Package::where('name_plan', $customer->package)->firstOrFail();

        $group_name = 'package_' . $package->id;
        $createdBy = $customer->created_by;
        $radiusUsername = $customer->username;
        $radiusPassword = $customer->password;
        $bandwidth = Bandwidth::where('package_id', $package->id)->first();

        if ($customer->is_override) {
            $MikroRate = "{$customer->override_upload}{$customer->override_upload_unit}/{$customer->override_download}{$customer->override_download_unit}";
        } else {
            $MikroRate = "{$bandwidth->rate_up}{$bandwidth->rate_up_unit}/{$bandwidth->rate_down}{$bandwidth->rate_down_unit}";
        }

        $activeSession = DB::table('radacct')
            ->where('username', $customer->username)
            ->where('created_by', $customer->created_by)
            ->whereNull('acctstoptime')
            ->orderByDesc('acctstarttime')
            ->first();

        if ($activeSession  && $activeSession->acctsessionid) {
            // Match NAS based on IP from radacct (nasipaddress)
            $nasObj = DB::table('nas')
                ->where('nasname', $activeSession->nasipaddress)
                ->first();

            if ($nasObj) {
                $attributes = [
                    'acctSessionID'     => $activeSession->acctsessionid,
                    'framedIPAddress'   => $activeSession->framedipaddress,
                ];

                self::kickOutUsersByRadius($nasObj, $customer, $attributes);
                // Log::warning("Customer kickout for IP: " . $activeSession->framedipaddress);
            } else {
                Log::warning("NAS not found for IP: " . $activeSession->nasipaddress);
            }
        } else {
            Log::warning("No active session or missing data for user: {$customer->username}");
        }

        DB::table('radcheck')->where('username', $customer->username)->where('created_by', $createdBy)->delete();
        DB::table('radreply')->where('username', $customer->username)->where('created_by', $createdBy)->delete();
        DB::table('radcheck')->insert([
            ['username' => $radiusUsername, 'attribute' => 'Cleartext-Password', 'op' => ':=', 'value' => $radiusPassword, 'created_by' => $createdBy],
        ]);
        DB::table('radreply')->insert([
            ['username' => $radiusUsername, 'attribute' => 'Mikrotik-Rate-Limit', 'op' => ':=', 'value' => $MikroRate, 'created_by' => $createdBy],
        ]);

        DB::table('radusergroup')->where('username', $customer->username)->where('created_by', $createdBy)->delete();
        DB::table('radusergroup')->insert([
            'username'  => $customer->username,
            'groupname' => $group_name,
            'priority'  => 1,
            'created_by' => $createdBy,
        ]);

        $baseTime = Carbon::now();
        $newExpiry = $package->validity_unit === 'Months'
            ? $baseTime->copy()->addMonths($package->validity)
            : $baseTime->copy()->addSeconds(match ($package->validity_unit) {
                'Minutes' => $package->validity * 60,
                'Hours'   => $package->validity * 3600,
                'Days'    => $package->validity * 86400,
            });

        if (
            $customer->is_extended === 1 &&
            $customer->extension_start &&
            $customer->extension_expiry
        ) {
            $start = Carbon::parse($customer->extension_start);
            $end = Carbon::parse($customer->extension_expiry);
            $now = Carbon::now();

            // Total extension duration (cap it in case dates are misordered)
            $usedSeconds = $start->diffInSeconds(min($now, $end));
            Log::info('Deducted extension time: ' . $usedSeconds);
            // Subtract only the time already used

            $newExpiry = $newExpiry->subSeconds($usedSeconds);
        }

        // Finalize
        $customer->expiry = $newExpiry->toDateTimeString();
        $customer->status = 'on';
        $customer->is_extended = 0;
        $customer->extension_start = null;
        $customer->extension_expiry = null;

        // Deduct if provided
        if ($packagePrice !== null) {
            $customer->balance -= $packagePrice;
        }

        $customer->save();

        self::updateChildrenExpiry($customer);
    }

    public static function updateChildrenExpiry($parent)
    {
        // Find all children with inherit_expiry set to true
        $children = Customer::where('parent_id', $parent->id)
            ->where('inherit_expiry', true)
            ->get();
                          
        foreach ($children as $child) {
            $child->expiry = $parent->expiry;
            $child->extension_start = $parent->extension_start;
            $child->extension_expiry = $parent->extension_expiry;
            $child->is_extended = $parent->is_extended;
            $child->status = $parent->status;
            $child->save();
            
            // Update RADIUS settings for the child
            CustomHelper::editExpiry($child->id);

            $activeSession = DB::table('radacct')
                ->where('username', $child->username)
                ->where('created_by', $child->created_by)
                ->whereNull('acctstoptime')
                ->orderByDesc('acctstarttime')
                ->first();

            if ($activeSession  && $activeSession->acctsessionid) {
                // Match NAS based on IP from radacct (nasipaddress)
                $nasObj = DB::table('nas')
                    ->where('nasname', $activeSession->nasipaddress)
                    ->first();

                if ($nasObj) {
                    $attributes = [
                        'acctSessionID'     => $activeSession->acctsessionid,
                        'framedIPAddress'   => $activeSession->framedipaddress,
                    ];

                    self::kickOutUsersByRadius($nasObj, $child, $attributes);
                } else {
                    Log::warning("NAS not found for IP: " . $activeSession->nasipaddress);
                }
            } else {
                Log::warning("No active session or missing data for user: {$child->username}");
            }
        }
    }
}
