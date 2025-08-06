<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Nas;
use App\Models\Router;
use App\Models\Package;
use App\Models\RouterPackage;
use App\Services\RouterService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use phpseclib3\Net\SSH2;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use ZipArchive;
use Devrabiul\ToastMagic\Facades\ToastMagic;

class NasController extends Controller
{

    public function index()
    {
        if(Auth::user()->can('manage nas'))
        {
            // $nases = Nas::where('created_by', \Auth::user()->creatorId())->get();
            $nases = Nas::with('routers.packages.bandwidth')->where('created_by', \Auth::user()->creatorId())->paginate(10);
            foreach ($nases as $nas) {

                $nas->status = $this->isNasOnline($nas->nasname, $nas->api_port) ? 'Online' : 'Offline';
            }

            return view('nas.index', compact('nases'));
        }
        else
        {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }
    public function checkStatus(Request $request)
    {
        try {
            $ip = $request->query('ip');
            $port = $request->query('port');
            $online = $this->isNasOnline($ip, $port);
            return response()->json(['status' => $online ? 'Online' : 'Offline']);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'Offline']);
        }
    }
    private function isNasOnline($nasIp)
    {
        $nas= Nas::where('nasname', $nasIp)->first();
        $port = $nas->api_port ?? 8728;
        $timeout = 5;

        if (is_callable('fsockopen') && false === stripos(ini_get('disable_functions'), 'fsockopen')) {
            $fsock = @fsockopen($nasIp, $port, $errno, $errstr, $timeout);
            if ($fsock) {
                fclose($fsock);
                return true;
            }
        } elseif (is_callable('stream_socket_client') && false === stripos(ini_get('disable_functions'), 'stream_socket_client')) {
            $connection = @stream_socket_client("$nasIp:$port", $errno, $errstr, $timeout);
            if ($connection) {
                fclose($connection);
                return true;
            }
        }
        return false;
    }

    public function getNasStatusCheck()
    {
        if(Auth::user()->can('manage nas'))
        {
            $routers = Router::where('created_by', Auth::user()->creatorId())->get();

            $nasStatus = $routers->map(function ($router) {
                return [
                    'ip' => $router->ip_address,
                    'status' => $this->isNasOnline($router->ip_address) ? 'online' : 'offline',
                ];
            });

            return response()->json([
                'nas' => $nasStatus,
            ]);
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }
    
    public function nasReboot($id)
    {
        if (!Auth::user()->can('manage nas')) {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }

        $nas = Nas::where('created_by', Auth::user()->creatorId())->findOrFail($id);

        try {
            $router = new RouterService(
                $nas->nasname,
                $nas->nasname,
                $nas->secret,
                $nas->api_port ?? 8728
            );

            $response = $router->reboot();

            if ($response['status']) {
                ToastMagic::success("Reboot command sent to {$nas->name}");
                \Log::info("Reboot command sent to {$nas->name}");
            } else {
                ToastMagic::error("Failed to reboot {$nas->name}: " . $response['message']);
                \Log::info("Failed to reboot {$nas->name}: " . $response['message']);
            }

        } catch (\Exception $e) {
            ToastMagic::error("Exception while rebooting {$nas->name}: " . $e->getMessage());
            \Log::info("Exception while rebooting {$nas->name}: " . $e->getMessage());
        }

        return redirect()->back();
    }

    public function statusCheck()
    {
        if(Auth::user()->can('manage nas'))
        {
            $nases = Nas::where('created_by', Auth::user()->creatorId())->get();
            $results = [];

            foreach ($nases as $nas) {
                $ip = $nas->nasname;
                $port = $nas->api_port ?? 8728;

                $fp = @fsockopen($ip, $port, $errno, $errstr, 1);
                $status = $fp ? 'online' : 'offline';
                if ($fp) fclose($fp);

                $results[] = [
                    'id'     => $nas->id,
                    'ip'     => $ip,
                    'status' => $status,
                ];
            }

            return response()->json(['nas' => $results]);
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function create()
    {
        if(\Auth::user()->can('create nas'))
        {
            return view('nas.create');
        }
        else
        {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function store(Request $request)
    {
        if (!\Auth::user()->can('create nas')) {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
        $request->merge([
            'site_name' => str_replace(' ', '', $request->input('site_name'))
        ]);

        $rules = [
            'site_name' => [
                'required',
                'string',
                Rule::unique('nas', 'shortname')->where(function ($query) {
                    return $query->where('created_by', \Auth::user()->id);
                })
            ],
            'api_port' => 'nullable|numeric',
        ];

        $validator = \Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->route('nas.index')->with('error', $validator->errors()->first());
        }

        try {
            // Step 1: Generate OpenVPN Client and Get Static IP
            $vpnScriptPath = "/var/www/html/openvpn/openvpn-clients.sh";
            $clientName = escapeshellarg($request->site_name);
            $vpnCommand = "sudo -u www-data /bin/bash $vpnScriptPath $clientName";
            $vpnOutput = shell_exec($vpnCommand);

            \Log::info("OpenVPN output for {$request->site_name}: " . $vpnOutput);

            preg_match('/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/', $vpnOutput, $matches);
            $staticIp = $matches[0] ?? null;

            if (!$staticIp) {
                ToastMagic::error('Failed to generate OpenVPN client.');
                return redirect()->route('nas.index');
            }

            // Step 2: Generate a Secret for FreeRADIUS
            $nasSecret = Str::random(8);

            // Step 3: Store NAS in FreeRADIUS Database
            $nas = new Nas();
            $nas->nasname = $staticIp;
            $nas->shortname = $request->site_name;
            $nas->secret = $nasSecret;
            $nas->api_port = $request->api_port;
            $nas->nasapi = 0;
            $nas->type = "other";
            $nas->server = 'default';
            $nas->community = '';
            $nas->created_by = \Auth::user()->creatorId();
            $nas->save();

            // Link NAS to Routers Table
            $router = new Router();
            $router->nas_id = $nas->id;
            $router->name = $nas->shortname;
            $router->api_port = $request->api_port;
            $router->ip_address = $staticIp;
            $router->type = 'Radius';
            $router->secret = $nasSecret;
            $router->location = 'Dynamic';
            $router->created_by = Auth::user()->creatorId();
            $router->save();

            // Step 4: Update FreeRADIUS Clients Configuration
            $serverIP = config('radius.server_ip');
            $serverPort = config('radius.server_port');
            $serverUser = config('radius.server_user');
            $serverPass = config('radius.server_pass');

            $ssh = new SSH2($serverIP, $serverPort);
            if (!$ssh->login($serverUser, $serverPass)) {
                ToastMagic::error('Unable to connect to FreeRADIUS server via SSH.');
                return redirect()->route('nas.index');
            }

            // Stop FreeRADIUS before making changes
            $ssh->exec("echo '$serverPass' | sudo -S service freeradius stop");

            // Step 5: Check if CoA exists before appending
            $coaExists = $ssh->exec("grep -c 'home_server' /etc/freeradius/3.0/clients.conf");
            if (intval(trim($coaExists)) == 0) {
                $coaConfig = "\n\n##############################################\n";
                $coaConfig .= "home_server ekinpay_" . date('YmdHis') . " {\n";
                $coaConfig .= "\ttype = coa\n";
                $coaConfig .= "\tipaddr = $staticIp\n";
                $coaConfig .= "\tport = 3799\n";
                $coaConfig .= "\tsecret = $nasSecret\n\n";
                $coaConfig .= "\tcoa {\n";
                $coaConfig .= "\t\tirt = 2\n";
                $coaConfig .= "\t\tmrt = 16\n";
                $coaConfig .= "\t\tmrc = 5\n";
                $coaConfig .= "\t\tmrd = 30\n";
                $coaConfig .= "\t}\n";
                $coaConfig .= "}\n";
                $coaConfig .= "##############################################\n";

                $ssh->exec("echo '$serverPass' | sudo -S bash -c \"echo '$coaConfig' >> /etc/freeradius/3.0/clients.conf\"");
            }

            // Step 6: Append new NAS entry
            $clientConfig = "\n\n##############################################\n";
            $clientConfig .= "client $staticIp {\n";
            $clientConfig .= "\tipaddr = $staticIp\n";
            $clientConfig .= "\tsecret = $nasSecret\n";
            $clientConfig .= "}\n";
            $clientConfig .= "##############################################\n";

            $ssh->exec("echo '$serverPass' | sudo -S bash -c \"echo '$clientConfig' >> /etc/freeradius/3.0/clients.conf\"");

            // Step 7: Restart FreeRADIUS and clear history
            $ssh->exec("echo '$serverPass' | sudo -S chmod -R 751 /etc/freeradius");
            $ssh->exec("echo '$serverPass' | sudo -S service freeradius restart");
            $ssh->exec("history -c && history -w");

            return redirect()->route('nas.index')->with('success', __('Site successfully created with VPN and FreeRADIUS.'));
        } catch (\Exception $e) {
            ToastMagic::error('Error updating FreeRADIUS: ' .$e->getMessage());
            return redirect()->route('nas.index');
        }
    }

    public function show($ids)
    {
        try {
            $id = Crypt::decrypt($ids);
        } catch (\Throwable $th) {
            ToastMagic::error('Site Not Found.');
            return redirect()->back();
        }
        $id = \Crypt::decrypt($ids);

        $usr = \Auth::user()->creatorId();
        $user = User::find($usr);
        $bridge = strtoupper(trim(explode(' ', $user->name)[0])) . "-BRIDGE";

        $nas = Nas::with('routers.packages.bandwidth')->findOrFail($id);

        $nas->status = $this->isNasOnline($nas->nasname, $nas->api_port) ? 'Online' : 'Offline';
        $routerIds = $nas->routers->pluck('id')->toArray();
        $packages = Package::leftJoin('router_packages', function ($join) use ($routerIds) {
            $join->on('packages.id', '=', 'router_packages.package_id')
                ->whereIn('router_packages.router_id', $routerIds);
        })
        ->where('packages.created_by', \Auth::user()->creatorId())
        ->select('packages.*', 'router_packages.router_id as assigned_router_id')
        ->get();

        return view('nas.show', compact('nas', 'packages', 'bridge'));
    }


    public function edit(Nas $nas)
    {
        if (\Auth::user()->can('edit nas')) {
            // $nas = Nas::find($id);
            return view('nas.edit', compact('nas'));
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function update(Request $request, Nas $nas)
    {
        if (\Auth::user()->can('edit nas')) {
            $rules = [
                'site_name' => 'required|string|max:255',
            ];

            $validator = \Validator::make($request->all(), $rules);
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->route('nas.index')->with('error', $messages->first());
            }

            $nas->shortname = $request->site_name;
            $nas->save();
            ToastMagic::success('Site successfully updated.');
            return redirect()->route('nas.index');
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function destroy(Nas $nas)
    {
        if(\Auth::user()->can('delete nas'))
        {
            if($nas->created_by == \Auth::user()->creatorId())
            {
                $nas->delete();
                ToastMagic::success('Site successfully deleted.');
                return redirect()->route('nas.index');
            }
            else
            {
                ToastMagic::error('Permission denied.');
            return redirect()->back();
            }
        }
        else
        {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }
    public function assignPackage(Request $request, $nasId)
    {
        $request->validate([
            'package_ids' => 'nullable|array',
            'package_ids.*' => 'exists:packages,id',
        ]);

        $router = Router::where('nas_id', $nasId)->firstOrFail();

        $selectedPackages = $request->input('package_ids', []);

        $existingPackages = RouterPackage::where('router_id', $router->id)->pluck('package_id')->toArray();

        $packagesToRemove = array_diff($existingPackages, $selectedPackages);
        RouterPackage::where('router_id', $router->id)->whereIn('package_id', $packagesToRemove)->delete();

        foreach ($selectedPackages as $packageId) {
            if (!in_array($packageId, $existingPackages)) {
                RouterPackage::create([
                    'router_id' => $router->id,
                    'package_id' => $packageId,
                ]);
            }
        }
        ToastMagic::success('Packages Assigned successfully.');
        return redirect()->route('nas.show', ['nas' => encrypt($nasId)]);
    }

    public function downloadHotspotPage($nas_ip)
    {
        $usr = \Auth::user()->creatorId();
        $user = User::find($usr);
        $company = trim(explode(' ', $user->name)[0]);
        $zipFileName = $company . '-' . $nas_ip . '.zip';

        $zipPath = storage_path($zipFileName);

        $loginContent = <<<HTML
    <html>
    <head>
        <title>Login</title>
        <meta http-equiv="refresh"
            content="0; url=https://captive.ekinpay.com/hs/{$nas_ip}/\$(mac)?chapID=\$(chap-id)&chapChallenge=\$(chap-challenge)&loginLink=\$(link-login-only)">
        <meta http-equiv="pragma" content="no-cache">
        <meta http-equiv="expires" content="-1">
    </head>
    <body>
    </body>
    </html>
    HTML;

        $statusContent = <<<HTML
    <html>
    <head>
        <title>Status</title>
        <meta http-equiv="refresh"
            content="0; url=https://captive.ekinpay.com/hs/{$nas_ip}/\$(mac)?chapID=\$(chap-id)&chapChallenge=\$(chap-challenge)&loginLink=\$(link-login-only)">
        <meta http-equiv="pragma" content="no-cache">
        <meta http-equiv="expires" content="-1">
    </head>
    <body>
    </body>
    </html>
    HTML;

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            $zip->addFromString('login.html', $loginContent);
            $zip->addFromString('status.html', $statusContent);
            $zip->close();
        } else {
            ToastMagic::error('Failed to create ZIP file');
            // return response()->json(['success' => false, 'message' => 'Failed to create ZIP file'], 500);
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

}
