<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Transaction;
use App\Models\ExpenseAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Devrabiul\ToastMagic\Facades\ToastMagic;

class ExpenseController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage expense')) {
            $expenses = Expense::where('created_by', Auth::user()->creatorId())->paginate(10);

            $attachments = ExpenseAttachment::whereHas('expense', function ($query) {
                $query->where('created_by', Auth::user()->creatorId());
                })->paginate(10);

            $totalExpenses = Expense::where('created_by', Auth::user()->creatorId())->sum('amount');

            $totalIncome = Transaction::where('created_by', Auth::user()->creatorId())
                ->where('amount', '>', 0)
                ->sum('amount');

            $balance = (int) $totalIncome - (int) $totalExpenses;

            $startOfYear = Carbon::now()->startOfYear();
            $salesByMonth = collect();
            $expensesByMonth = collect();
            $creatorId = Auth::user()->creatorId();

            // Loop through 12 months: Jan (1) to Dec (12)
            for ($i = 1; $i <= 12; $i++) {
                $monthStart = Carbon::createFromDate(null, $i, 1)->startOfMonth();
                $monthEnd = Carbon::createFromDate(null, $i, 1)->endOfMonth();

                $monthlySales = DB::table('transactions')
                    ->where('created_by', $creatorId)
                    ->where('status', 1)
                    ->whereBetween('date', [$monthStart, $monthEnd])
                    ->sum('amount');

                $monthlyExpenses = DB::table('expenses')
                    ->where('created_by', $creatorId)
                    ->whereBetween('date', [$monthStart, $monthEnd])
                    ->sum('amount');

                $salesByMonth->push($monthlySales);
                $expensesByMonth->push($monthlyExpenses);
            }

            $yearlySales = $salesByMonth->toArray();
            $yearlyExpenses = $expensesByMonth->toArray();

            $category = [
                'hardware',
                'software',
                'salary',
                'rent',
                'electricity',
                'transport',
                'maintenance',
                'other'
            ];
            $payment = [
                'cash' => 'Cash',
                'mpesa' => 'Mpesa',
                'bank' => 'Bank',
                'credit' => 'Credit',
            ];

            return view('expense.index', compact('attachments', 'expenses', 'totalExpenses', 'totalIncome', 'balance', 'yearlySales', 'yearlyExpenses', 'category', 'payment'));
        } else {
            ToastMagic::error('Permission Denied.');
            return redirect()->back();
        }
    }

    public function create()
    {
        if (Auth::user()->can('create expense')) {
            return view('expense.create', [
                'jsonData' => json_encode([
                    'status' => 'success',
                    'message' => 'Create expense form data can be sent'
                ])
            ]);
        } else {
            ToastMagic::error('Permission Denied.');
            return redirect()->back();
        }
    }


    public function store(Request $request)
    {
        \Log::info($request->all());
        if (!\Auth::user()->can('create expense')) {
            ToastMagic::error('Permission Denied.');
            return redirect()->back();
        }

        // Validate request
        $validator = \Validator::make($request->all(), [
            'title' => 'required|string',
            'date' => 'required|date',
            'amount' => 'required',
            'category' => 'required|string',
            'description' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'reference' => 'nullable|string',
            'attachments.*' => 'nullable|file|max:5120',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Generate expense ID
        $expenseId = 'EXP' . str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // Save expense
        $expense = new Expense();
        $expense->expense_id = $expenseId;
        $expense->title = $request->title;
        $expense->description = $request->description;
        $expense->amount = $request->amount;
        $expense->category = $request->category;
        $expense->date = $request->date;
        $expense->payment_method = $request->payment_method;
        $expense->reference = $request->reference;
        $expense->created_by = \Auth::user()->creatorId();
        $expense->saveOrFail();

        // Save attachments if present
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $uniqueSuffix = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
                $extension = $file->getClientOriginalExtension();
                $filename = $expenseId . '-' . $uniqueSuffix . '.' . $extension;
                $path = $file->storeAs('public/expenses', $filename);

                $attachment = new ExpenseAttachment();
                $attachment->expense_id = $expense->id;
                $attachment->file_path = $path;
                $attachment->file_type = $extension;
                $attachment->save();
            }
        }
        ToastMagic::success('Expense successfully created.',  $expenseId);
        return redirect()->route('expense.index')->with('success', __('Expense successfully created.'));
    }

    public function show($id)
    {
        if (Auth::user()->can('show expense')) {
            $expense = Expense::find($id);
            if ($expense && $expense->created_by == Auth::user()->creatorId()) {
                return view('expense.show', [
                    'expense' => $expense,
                    'jsonData' => json_encode([
                        'status' => 'success',
                        'data' => $expense
                    ])
                ]);
            } else {
                ToastMagic::error('Expense not found.');
                return redirect()->back()->with('error', __('Expense not found.'));
            }
        } else {
            ToastMagic::error('Permission Denied.');
            return redirect()->back();
        }
    }

    public function edit($id)
    {
        if (Auth::user()->can('edit expense')) {
            $expense = Expense::find($id);
            if ($expense && $expense->created_by == Auth::user()->creatorId()) {
                return view('expense.edit', [
                    'expense' => $expense,
                    'jsonData' => json_encode([
                        'status' => 'success',
                        'data' => $expense
                    ])
                ]);
            } else {
                ToastMagic::error('Expense not found.');
                return redirect()->back();
            }
        } else {
            ToastMagic::error('Permission Denied.');
            return redirect()->back();
        }
    }

    public function update(Request $request, $id)
    {
        if (Auth::user()->can('edit expense')) {
            $expense = Expense::find($id);
            if ($expense && $expense->created_by == Auth::user()->creatorId()) {
                $validator = \Validator::make($request->all(), [
                    'title' => 'required|string',
                    'date' => 'required|date',
                    'amount' => 'required',
                    'category' => 'required|string',
                    'description' => 'nullable|string',
                    'payment_method' => 'nullable|string',
                    'reference' => 'nullable|string',
                    'attachments.*' => 'nullable|file|max:5120',
                ]);

                if ($validator->fails()) {
                    return redirect()->back()->withErrors($validator)->withInput();
                }

                // Generate expense ID
                // $expenseId = 'EXP' . str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

                // Save expense
                // $expense = new Expense();
                // $expense->expense_id = $expenseId;
                $expense->title = $request->title;
                $expense->description = $request->description;
                $expense->amount = $request->amount;
                $expense->category = $request->category;
                $expense->date = $request->date;
                $expense->payment_method = $request->payment_method;
                $expense->reference = $request->reference;
                $expense->created_by = \Auth::user()->creatorId();
                $expense->saveOrFail();

                // Save attachments if present
                if ($request->hasFile('attachments')) {
                    foreach ($request->file('attachments') as $file) {
                        $uniqueSuffix = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
                        $extension = $file->getClientOriginalExtension();
                        $filename = $expense->expense_id . '-' . $uniqueSuffix . '.' . $extension;
                        $path = $file->storeAs('public/expenses', $filename);

                        $attachment = new ExpenseAttachment();
                        $attachment->expense_id = $expense->id;
                        $attachment->file_path = $path;
                        $attachment->file_type = $extension;
                        $attachment->save();
                    }
                }
                ToastMagic::error('Expense successfully updated.');
                return redirect()->route('expense.index');
            } else {
                ToastMagic::error('Expense not found.');
                return redirect()->back();
            }
        } else {
            ToastMagic::error('Permission Denied.');
            return redirect()->back();
        }
    }

    public function destroy($id)
    {
        if (Auth::user()->can('delete expense')) {
            $expense = Expense::find($id);
            if ($expense && $expense->created_by == Auth::user()->creatorId()) {
                $expense->delete();
                ToastMagic::success('Expense successfully deleted.');
                return redirect()->route('expense.index');
            } else {
                ToastMagic::error('Expense not found.');
                return redirect()->back();
            }
        } else {
            ToastMagic::error('Permission Denied.');
            return redirect()->back();
        }
    }
}
