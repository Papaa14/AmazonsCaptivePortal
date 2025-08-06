<?php

namespace App\Http\Controllers;

use App\Exports\TransactionExport;
use App\Exports\CustomerExport;
use App\Imports\CustomerImport;
use App\Models\Customer;
use App\Models\CustomField;
use App\Models\Transaction;
use App\Models\Package;
use App\Models\Bandwidth;
use App\Models\Router;
use App\Models\MpesaTransaction;
use App\Models\Utility;
use Auth;
use App\Helpers\CustomHelper;
use App\Models\User;
use App\Models\Plan;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Http;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Devrabiul\ToastMagic\Facades\ToastMagic;

class TransactionController extends Controller
{

    public function index(Request $request)
    {
        if (Auth::user()->can('manage transaction')) {
            // Get today's date
            $today = now()->startOfDay();

            // Query to get transactions with status 1 for today
            $transactions = Transaction::with(['customer', 'package'])
                ->where('status', 1)
                ->whereDate('date', $today)
                ->where('created_by', Auth::user()->creatorId())
                ->orderBy('id', 'desc')
                ->get();
            return view('transaction.index', compact(var_name: 'transactions'));
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function indexPeriod(Request $request)
    {
        $creatorId = Auth::user()->creatorId();
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $service = $request->input('service', 'all');
        $siteId = $request->input('site_id');
        $currentsite = Router::find($siteId);


        // Handle cases where only one date is selected
        if ($startDate && !$endDate) {
            $endDate = $startDate;
        }
        if (!$startDate && $endDate) {
            $startDate = $endDate;
        }
        if (empty($startDate) && empty($endDate)) {
            $startDate = Carbon::now()->startOfMonth()->toDateString();
            $endDate = Carbon::now()->endOfMonth()->toDateString();
        }

        // Convert to Carbon full-day timestamps
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();


        // Get all sites for dropdown
        $sites = Router::where('created_by', $creatorId)->get();

        // Base transaction query for summary
        $baseTransactionQuery = Transaction::where('created_by', $creatorId)
            ->where('amount', '>', 0)
            ->whereBetween('date', [$start, $end]);


        if ($service !== 'all') {
            $baseTransactionQuery->where('category', $service);
        }

        if ($currentsite) {
            $baseTransactionQuery->where('site', $currentsite->ip_address);
        }

        $thisMonthIncome = (clone $baseTransactionQuery)->sum('amount');
        $thisMonthEntries = (clone $baseTransactionQuery)->count();

        // Unresolved M-PESA query
        $mpesaQuery = MpesaTransaction::where('created_by', $creatorId)
            ->where('status', 0)
            ->whereBetween('TransTime', [$start, $end]);

        // if ($currentsite) {
        //     $mpesaQuery->where('site', $currentsite->ip_address);
        // }

        $thisMonthUnresolved = $mpesaQuery->count();

        $transactions = $baseTransactionQuery
            ->with(['customer:id,username,package'])
            ->paginate(10);

        return view('transaction.period', compact(
            'thisMonthIncome', 'thisMonthEntries', 'thisMonthUnresolved',
            'sites', 'startDate', 'endDate', 'service', 'siteId', 'transactions'
        ));
    }


    // Method to show M-Pesa transactions
    public function indexMpesa()
    {
        if (Auth::user()->can('manage payment')) {
            $mpesaTransactions = MpesaTransaction::where('created_by', Auth::user()->creatorId())
                ->orderBy('id', 'desc')
                ->get();
            return view('transaction.mpesa', compact('mpesaTransactions'));
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function indexBalance()
    {
        if (Auth::user()->can('manage payment')) {
            $customerBalance = Customer::where('created_by', Auth::user()->creatorId())
                ->where('balance', '>', 0)
                ->orderBy('id', 'desc')
                ->get();
            return view('transaction.balance', compact('customerBalance'));
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    //for export in transaction report
    public function export()
    {
        $name = 'transaction_' . date('Y-m-d i:h:s');
        $data = Excel::download(new TransactionExport(), $name . '.xlsx');

        return $data;
    }

}
