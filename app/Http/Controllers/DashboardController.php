<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AttendanceEmployee;
use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\Nas;
use App\Models\BugStatus;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Support\Str;
use App\Models\DealTask;
use App\Models\Router;
use App\Models\Event;
use App\Models\Expense;
use App\Models\Goal;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Lead;
use App\Models\LeadStage;
use App\Models\Meeting;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Pos;
use App\Models\ProductServiceCategory;
use App\Models\ProductServiceUnit;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Purchase;
use App\Models\Revenue;
use App\Models\Stage;
use App\Models\Tax;
use App\Models\Timesheet;
use App\Models\TimeTracker;
use App\Models\Package;
use App\Models\Training;
use App\Models\User;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }


    public function landingPage()
    {
        if (!file_exists(storage_path() . "/installed")) {
            header('location:install');
            die;
        }

        $adminSettings = Utility::settings();
        if ($adminSettings['display_landing_page'] == 'on' && \Schema::hasTable('landing_page_settings')) {

            return view('landingpage::layouts.landingpage' , compact('adminSettings'));

        } else {
            return redirect('login');
        }
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function show_dashboard()
    {
        if (Auth::check()) {
            if (Auth::user()->type == 'super admin') {
                return redirect()->route('client.dashboard.view');
            } elseif (Auth::user()->type == 'client') {
                return redirect()->route('client.dashboard.view');
            } else {
                if (\Auth::user()->can('show dashboard')) {
                    $data = [];
    
                    $data['latestIncome'] = Transaction::where('created_by', Auth::user()->creatorId())
                        ->orderBy('id', 'desc')
                        ->limit(5)
                        ->get();

                    $data['todayIncome'] = Transaction::where('created_by', Auth::user()->creatorId())
                        ->whereDate('date', Carbon::today())
                        ->sum('amount');
                    
                    // Get daily income for the past 7 days
                    $dailyIncomeData = [];
                    $dailyIncomeDates = [];
                    
                    for ($i = 6; $i >= 0; $i--) {
                        $date = Carbon::today()->subDays($i);
                        $dailyIncomeAmount = Transaction::where('created_by', Auth::user()->creatorId())
                            ->whereDate('date', $date)
                            ->sum('amount');
                        
                        $dailyIncomeData[] = $dailyIncomeAmount;
                        $dailyIncomeDates[] = $date->format('d M');
                    }
                    
                    $data['dailyIncomeData'] = $dailyIncomeData;
                    $data['dailyIncomeDates'] = $dailyIncomeDates;

                    $data['users'] = User::find(Auth::user()->creatorId());

                    $sites = Router::where('created_by', Auth::user()->creatorId())->get();
                    $data['totalSites'] = $sites->count();

                    $creatorId = Auth::user()->creatorId();
                    $totalCustomers = Customer::where('created_by', $creatorId)->count();
                    $activeCustomers = Customer::where('created_by', $creatorId)->where('status', 'on')->count();
                    $expiredCustomers = Customer::where('created_by', $creatorId)->where('status', 'off')->where('service', 'PPPoE')->count();

                    $usernames = Customer::where('created_by', $creatorId)->pluck('username')->toArray();

                    // Updated online users counting logic using correct RADIUS columns
                    $totalOnline = DB::table('radacct')
                        ->where('created_by', Auth::user()->creatorId())
                        ->where(function($query) {
                            $query->whereNull('acctstoptime')
                                ->orWhere(function($q) {
                                    // Consider sessions that have been updated recently (last 5 minutes) as still active
                                    $q->where('acctupdatetime', '>=', DB::raw('DATE_SUB(NOW(), INTERVAL 5 MINUTE)'));
                                });
                        })
                        ->whereNotNull('acctstarttime')
                        ->distinct('username') 
                        ->count();

                    $totalPPP = DB::table('radacct')
                        ->where('created_by', $creatorId)
                        ->where(function($query) {
                            $query->whereNull('acctstoptime')
                                ->orWhere(function($q) {
                                    $q->where('acctupdatetime', '>=', DB::raw('DATE_SUB(NOW(), INTERVAL 5 MINUTE)'));
                                });
                        })
                        ->whereNotNull('acctstarttime')
                        ->where('framedprotocol', 'PPP')
                        ->distinct('username')
                        ->count();

                    $totalHotspot = DB::table('radacct')
                        ->where('created_by', $creatorId)
                        ->where(function($query) {
                            $query->whereNull('acctstoptime')
                                ->orWhere(function($q) {
                                    $q->where('acctupdatetime', '>=', DB::raw('DATE_SUB(NOW(), INTERVAL 5 MINUTE)'));
                                });
                        })
                        ->whereNotNull('acctstarttime')
                        ->where('username', 'REGEXP', '^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$')
                        ->distinct('username')
                        ->distinct('username')
                        ->count();

                    // \Log::info("Totals: Online: {$totalOnline}, PPP: {$totalPPP}, Hotspot: {$totalHotspot}");

                    $data['totalCustomers'] = $totalCustomers ?? 0;
                    $data['activeCustomers'] = $activeCustomers ?? 0;
                    $data['expiredCustomers'] = $expiredCustomers ?? 0;
                    $data['onlineCustomers'] = $totalOnline ?? 0;
                    $data['onlinePPPoE'] = $totalPPP ?? 0;
                    $data['onlineHotspot'] = $totalHotspot ?? 0;

                    $data['activePercentage'] = $totalCustomers > 0 
                    ? round(($activeCustomers / $totalCustomers) * 100, 2) 
                    : 0;
                    $activePercentage = $totalCustomers > 0 
                        ? (int) (($activeCustomers / $totalCustomers) * 100) 
                        : 0; // Ensure it's an integer

                    $Actdata = [
                        'activePercentage' => $activePercentage
                    ];
                    $todayEntries = Transaction::where('created_by', $creatorId)
                        ->whereDate('date', Carbon::today())
                        ->count();
                    $data['todayEntries'] = $todayEntries;
                    $yesterdayEntries = Transaction::where('created_by', $creatorId)
                        ->whereDate('date', Carbon::yesterday())
                        ->count();
                    if ($yesterdayEntries == 0) {
                        $percentageChange = $todayEntries > 0 ? 100 : 0;
                    } else {
                        $percentageChange = (($todayEntries - $yesterdayEntries) / $yesterdayEntries) * 100;
                    }
                    $data['percentageChangeEntries'] = round($percentageChange, 2);
                    
                    $chartData = [
                        'labels' => [],
                        'data' => []
                    ];
                
                    for ($i = 5; $i >= 0; $i--) {
                        $date = Carbon::today()->subDays($i)->toDateString();
                        $count = Transaction::where('created_by', $creatorId)
                            ->whereDate('date', $date)
                            ->count();
                
                        $chartData['labels'][] = Carbon::today()->subDays($i)->format('d');
                        $chartData['data'][] = $count;
                    }
                    
                    // $thisMonthIncome = Transaction::where('created_by', $creatorId)
                    // ->where('amount', '>', 0) 
                    // ->whereMonth('created_at', Carbon::now()->month)
                    // ->whereYear('created_at', Carbon::now()->year)
                    // ->sum('amount');
                    $thisMonthIncome = Transaction::where('created_by', $creatorId)
                        ->where('amount', '>', 0)
                        ->whereBetween('date', [
                            Carbon::now()->startOfMonth()->toDateString(),
                            Carbon::now()->endOfMonth()->toDateString()
                        ])
                        ->sum('amount');

                    $lastMonthIncome = Transaction::where('created_by', $creatorId)
                    ->where('amount', '>', 0)
                    ->whereMonth('date', Carbon::now()->subMonth()->month)
                    ->whereYear('date', Carbon::now()->subMonth()->year)
                    ->sum('amount');

                    if ($lastMonthIncome > 0) {
                        $incomePercentageChange = round((($thisMonthIncome - $lastMonthIncome) / $lastMonthIncome) * 100, 2);
                    } else {
                        $incomePercentageChange = $thisMonthIncome > 0 ? 100 : 0;
                    }
                    
                    // Send data to the view
                    $data['thisMonthIncome'] = $thisMonthIncome;
                    $data['lastMonthIncome'] = $lastMonthIncome;
                    $data['incomePercentageChange'] = (int) $incomePercentageChange;

                    $thisMonthEntries = Transaction::where('created_by', $creatorId)
                        ->whereMonth('date', Carbon::now()->month)
                        ->whereYear('date', Carbon::now()->year)
                        ->sum('amount');

                    $hotspotEntries = Transaction::where('created_by', $creatorId)
                        ->whereMonth('date', Carbon::now()->month)
                        ->whereYear('date', Carbon::now()->year)
                        ->where('category', 'Hotspot')
                        ->sum('amount');

                    // Get entries for PPPoE
                    $pppoeEntries = Transaction::where('created_by', $creatorId)
                        ->whereMonth('date', Carbon::now()->month)
                        ->whereYear('date', Carbon::now()->year)
                        ->where('category', 'PPPoE')
                        ->sum('amount');
                        
                    // Ensure values are never null
                    $hotspotEntries = $hotspotEntries ?? 0;
                    $pppoeEntries = $pppoeEntries ?? 0;
                    $thisMonthEntries = $thisMonthEntries ?? 0;

                    $Entdata = [
                        'pppoeEntries' => $pppoeEntries,
                        'hotspotEntries' => $hotspotEntries,
                        'thisMonthEntries' =>  $thisMonthEntries
                    ];
                    
                    $months = collect(range(0, 11))->map(function ($i) {
                        return Carbon::now()->startOfYear()->addMonths($i)->format('M');
                    })->values(); // Get all months starting from January
                    
                    $revenues = [];
                    $expenses = [];
                    
                    foreach ($months as $monthIndex => $monthName) {
                        $startOfMonth = Carbon::now()->startOfYear()->addMonths($monthIndex)->startOfMonth();
                        $endOfMonth = Carbon::now()->startOfYear()->addMonths($monthIndex)->endOfMonth();
                    
                        // Calculate revenue
                        $revenue = Transaction::where('created_by', $creatorId)
                            ->where('amount', '>', 0)
                            ->whereBetween('date', [$startOfMonth, $endOfMonth])
                            ->sum('amount');
                    
                        // Calculate expenses
                        $expense = Expense::where('created_by', $creatorId)
                            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                            ->sum('amount');
                    
                        $revenues[] = $revenue;
                        $expenses[] = -$expense;
                    }
                    
                    $expenData = [
                        'months' => $months,
                        'revenues' => $revenues,
                        'expenses' => $expenses
                    ];
                    $currentYear = Carbon::now()->year;

                    // Get total revenue for the current year
                    $totalRevenueYear = Transaction::where('created_by', $creatorId)
                        ->where('amount', '>', 0)
                        ->whereYear('date', $currentYear)
                        ->sum('amount');

                    $data['totalRevenueYear'] = $totalRevenueYear;$currentYear = Carbon::now()->year;

                    // Get total revenue for the current year
                    $totalRevenueYear = Transaction::where('created_by', $creatorId)
                        ->where('amount', '>', 0)
                        ->whereYear('date', $currentYear)
                        ->sum('amount');

                    $data['totalRevenueYear'] = $totalRevenueYear;

                    // Prepare budget data for last month and current month
                    $daysInCurrentMonth = Carbon::now()->daysInMonth;
                    $currentMonthBudget = [];
                    $lastMonthBudget = [];
                    
                    // Get data for current month, day by day
                    for ($day = 1; $day <= $daysInCurrentMonth; $day++) {
                        $date = Carbon::now()->startOfMonth()->addDays($day - 1);
                        
                        // Skip future dates
                        if ($date->isAfter(Carbon::now())) {
                            $currentMonthBudget[] = null; // Use null for future dates
                        } else {
                            $dailyRevenue = Transaction::where('created_by', $creatorId)
                                ->where('amount', '>', 0)
                                ->whereDate('date', $date)
                                ->sum('amount');
                            $currentMonthBudget[] = $dailyRevenue;
                        }
                    }
                    
                    // Get data for previous month, day by day
                    $lastMonth = Carbon::now()->subMonth();
                    $daysInLastMonth = $lastMonth->daysInMonth;
                    
                    for ($day = 1; $day <= $daysInLastMonth; $day++) {
                        $date = $lastMonth->copy()->startOfMonth()->addDays($day - 1);
                        $dailyRevenue = Transaction::where('created_by', $creatorId)
                            ->where('amount', '>', 0)
                            ->whereDate('date', $date)
                            ->sum('amount');
                        $lastMonthBudget[] = $dailyRevenue;
                    }
                    
                    // Padding arrays to make them the same length if needed
                    $maxDays = max($daysInCurrentMonth, $daysInLastMonth);
                    if (count($currentMonthBudget) < $maxDays) {
                        $currentMonthBudget = array_pad($currentMonthBudget, $maxDays, null);
                    }
                    if (count($lastMonthBudget) < $maxDays) {
                        $lastMonthBudget = array_pad($lastMonthBudget, $maxDays, null);
                    }
                    
                    $budgetData = [
                        'currentMonth' => $currentMonthBudget,
                        'lastMonth' => $lastMonthBudget
                    ];

                    $today = Carbon::today();

                    $topPackages = Transaction::where('status', 1)
                        ->whereDate('date', $today)
                        ->where('created_by', Auth::user()->creatorId())
                        ->groupBy('package_id')
                        ->select('package_id', DB::raw('COUNT(*) as total_sales'), DB::raw('SUM(amount) as total_revenue'))
                        ->orderByDesc('total_sales')
                        ->limit(5)
                        ->get();
                    $topPackagesDetails = [];
                    foreach ($topPackages as $package) {
                        $packageDetails = \App\Models\Package::find($package->package_id);
                        if ($packageDetails) {
                            $topPackagesDetails[] = [
                                'name_plan' => $packageDetails->name_plan,
                                'category' => $packageDetails->type,
                                'total_sales' => $package->total_sales,
                                'total_revenue' => $package->total_revenue,
                            ];
                        }
                    }
                    $topUsers = DB::table('radacct')
                        ->select('username', DB::raw('SUM(acctinputoctets + acctoutputoctets) AS total_data_usage'))
                        ->whereIn('username', $usernames)
                        // ->whereNull('acctstoptime')
                        ->where('created_by', $creatorId)
                        ->groupBy('username')
                        ->orderByDesc('total_data_usage')
                        ->limit(5)
                        ->get();

                    $arrType = [
                        'PPPoE' => __('PPPoE'),
                    ];
        
                    $arrPackage = Package::where('created_by', \Auth::user()->creatorId())
                        ->where('type', 'PPPoE')
                        ->pluck('name_plan', 'id')
                        ->toArray();

                    $creatorId = \Auth::user()->creatorId();

                    if ($creatorId == 9) {
                        $customerN = self::generateNextAccountNumber('KN', 5, 99999);
                    } elseif ($creatorId == 13) {
                        $customerN = self::generateNextAccountNumber('LN', 6, 999999);
                    } else {
                        $customerN = self::generateNextAccountNumber('', 7, 999999);
                    }
        
                    $secret = strtoupper(Str::random(8));
                    $email = strtolower($customerN) . '@isp.net';
                    return view('dashboard.company', compact('data', 'chartData', 'Actdata', 'Entdata', 'expenData', 'budgetData', 'topPackagesDetails', 'topUsers', 'customerN', 'arrType', 'arrPackage', 'secret', 'email'));
                } else {
                    return view('dashboard.simple-dashboard');
                }
            }
        }
        return redirect('login');
    }

    public function generateNextAccountNumber($prefix, $lengthLimit, $maxValue, $excludeAccount = null)
    {
        $query = Customer::where('created_by', \Auth::user()->creatorId());

        if ($excludeAccount) {
            $query->where('account', '!=', $excludeAccount);
        }

        $query->where('account', 'LIKE', $prefix . '%')
            ->whereRaw('LENGTH(SUBSTRING(account, 3)) <= ?', [$lengthLimit])
            ->orderByRaw("LPAD(SUBSTRING(account, 3), {$lengthLimit}, '0') DESC");

        $latestAccount = $query->first();

        if (!$latestAccount) {
            $nextNumber = 1;
        } else {
            preg_match('/\d+$/', $latestAccount->account, $matches);
            $nextNumber = isset($matches[0]) ? (int)ltrim($matches[0], '0') + 1 : 1;
        }

        if ($nextNumber > $maxValue) {
            throw new \Exception("New account limit reached.");
        }

        return \Auth::user()->customerNumberFormat($nextNumber);
    }

    // Helper function to check NAS status
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

    public function getNasCounts()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $sites = Router::where('created_by', Auth::user()->creatorId())->get();

        $onlineCount = 0;
        $offlineCount = 0;

        foreach ($sites as $site) {
            $nasIp = $site->ip_address;
            $status = $this->isNasOnline($nasIp);

            if ($status) {
                $onlineCount++;
            } else {
                $offlineCount++;
            }
        }

        return response()->json([
            'total' => $sites->count(),
            'online' => $onlineCount,
            'offline' => $offlineCount
        ]);
    }


    // Load Dashboard user's using ajax
    public function filterView(Request $request)
    {
        $usr = Auth::user();
        $users = User::where('id', '!=', $usr->id);

        if ($request->ajax()) {
            if (!empty($request->keyword)) {
                $users->where('name', 'LIKE', $request->keyword . '%')->orWhereRaw('FIND_IN_SET("' . $request->keyword . '",skills)');
            }

            $users = $users->get();
            $returnHTML = view('dashboard.view', compact('users'))->render();

            return response()->json([
                'success' => true,
                'html' => $returnHTML,
            ]);
        }
    }

    public function clientView()
    {

        if (Auth::check()) {
            if (Auth::user()->type == 'super admin') {
                $user = \Auth::user();
                $user['total_user'] = $user->countCompany();
                $user['total_paid_user'] = $user->countPaidCompany();
                $user['total_orders'] = Order::total_orders();
                $user['total_orders_price'] = Order::total_orders_price();
                $user['total_plan'] = Plan::total_plan();
                if(!empty(Plan::most_purchese_plan()))
                {
                    $plan = Plan::find(Plan::most_purchese_plan()['plan']);
                    $user['most_purchese_plan'] = $plan->name;
                }
                else
                {
                    $user['most_purchese_plan'] = '-';
                }

                $chartData = $this->getOrderChart(['duration' => 'week']);

                // Get server metrics data
                $serverMetrics = null;

                return view('dashboard.superadmin', compact('user', 'chartData', 'serverMetrics'));

            }
        }
    }

    public function getOrderChart($arrParam)
    {
        $arrDuration = [];
        if ($arrParam['duration']) {
            if ($arrParam['duration'] == 'week') {
                $previous_week = strtotime("-2 week +1 day");
                for ($i = 0; $i < 14; $i++) {
                    $arrDuration[date('Y-m-d', $previous_week)] = date('d-M', $previous_week);
                    $previous_week = strtotime(date('Y-m-d', $previous_week) . " +1 day");
                }
            }
        }

        $arrTask = [];
        $arrTask['label'] = [];
        $arrTask['data'] = [];
        foreach ($arrDuration as $date => $label) {

            $data = Order::select(\DB::raw('count(*) as total'))->whereDate('created_at', '=', $date)->first();
            $arrTask['label'][] = $label;
            $arrTask['data'][] = $data->total;
        }

        return $arrTask;
    }

}
