<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReferralSetting;
use App\Models\ReferralTransaction;
use App\Models\User;
use App\Models\TransactionOrder;
use Devrabiul\ToastMagic\Facades\ToastMagic;

class ReferralProgramController extends Controller
{
    public function index()
    {
        $setting = ReferralSetting::where('created_by',\Auth::user()->id)->first();
        $payRequests = TransactionOrder::where('status' , 1)->get();

        $transactions = ReferralTransaction::get();

        return view('referral-program.index' , compact('setting' , 'payRequests' , 'transactions'));
    }

    public function store(Request $request)
    {

        $validator = \Validator::make(
            $request->all(), [
                'percentage' => 'required',
                'minimum_threshold_amount' => 'required',
                'guideline' => 'required',
            ]
        );

        if($validator->fails())
        {
            $messages = $validator->getMessageBag();

            ToastMagic::error($messages->first());
            return redirect()->back();
        }

        if($request->has('is_enable') && $request->is_enable == 'on')
        {
            $is_enable = 1;
        }
        else
        {
            $is_enable = 0;
        }

        $setting = ReferralSetting::where('created_by' , \Auth::user()->id)->first();

        if($setting == null)
        {
            $setting = new ReferralSetting();
        }
        $setting->percentage = $request->percentage;
        $setting->minimum_threshold_amount = $request->minimum_threshold_amount;
        $setting->is_enable  = $is_enable;
        $setting->guideline = $request->guideline;
        $setting->created_by = \Auth::user()->creatOrId();
        $setting->save();

        ToastMagic::success('Referral Program Setting successfully Updated.');
        return redirect()->route('referral-program.index');

    }

    public function companyIndex()
    {
        $setting = ReferralSetting::where('created_by',1)->first();

        $objUser = \Auth::user();

        $transactions = ReferralTransaction::where('referral_code' , $objUser->referral_code)->get();

        $transactionsOrder = TransactionOrder::where('req_user_id',$objUser->id)->get();
        $paidAmount = $transactionsOrder->where('status' , 2)->sum('req_amount');

        $paymentRequest = TransactionOrder::where('status' , 1)->where('req_user_id',$objUser->id)->first();

        return view('referral-program.company' , compact('setting' , 'transactions' , 'paidAmount' , 'transactionsOrder' , 'paymentRequest'));
    }

    public function requestedAmountSent($id)
    {
        $id  = \Illuminate\Support\Facades\Crypt::decrypt($id);
        $paidAmount = TransactionOrder::where('req_user_id',\Auth::user()->id)->where('status' , 2)->sum('req_amount');
        $user = User::find(\Auth::user()->id);

        $netAmount = $user->commission_amount - $paidAmount;

        return view('referral-program.request_amount' , compact('id' , 'netAmount'));
    }

    public function requestCancel($id)
    {
        $transaction = TransactionOrder::where('req_user_id',$id)->orderBy('id','desc')->first();
        // $transaction->status = 0;
        // $transaction->req_user_id = \Auth::user()->id;
        $transaction->delete();

        ToastMagic::success('Request Cancel Successfully.');
        return redirect()->route('referral-program.company');
    }

    public function requestedAmountStore(Request $request , $id)
    {
        $order = new TransactionOrder();
        $order->req_amount =  $request->request_amount;
        $order->req_user_id = \Auth::user()->id;
        $order->status = 1;
        $order->date = date('Y-m-d');
        $order->save();

        ToastMagic::success('Request Send Successfully.');
        return redirect()->route('referral-program.company');
    }

    public function requestedAmount($id , $status)
    {

        $setting = ReferralSetting::where('created_by',1)->first();

        $transaction = TransactionOrder::find($id);

        $paidAmount = TransactionOrder::where('req_user_id',$transaction->req_user_id)->where('status' , 2)->sum('req_amount');
        $user = User::find($transaction->req_user_id);

        $netAmount = $user->commission_amount - $paidAmount;

        $minAmount = isset($setting) ? $setting->minimum_threshold_amount : 0;
        if($status == 0)
        {
            $transaction->status = 0;

            $transaction->save();

            ToastMagic::error('Request Rejected Successfully.');
            return redirect()->route('referral-program.index');
        }
        elseif($transaction->req_amount > $netAmount)
        {
            $transaction->status = 0;

            $transaction->save();
            ToastMagic::error('This request cannot be accepted because it exceeds the commission amount.');
            return redirect()->route('referral-program.index');
        }
        elseif($transaction->req_amount < $minAmount)
        {
            $transaction->status = 0;

            $transaction->save();
            ToastMagic::error('This request cannot be accepted because it less than the threshold amount.');
            return redirect()->route('referral-program.index');
        }
        else
        {
            $transaction->status = 2;

            $transaction->save();
            ToastMagic::success('Request Aceepted Successfully.');
            return redirect()->route('referral-program.index');
        }
    }
}
