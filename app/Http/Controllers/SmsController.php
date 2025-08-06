<?php

namespace App\Http\Controllers;
use App\Exports\CustomerExport;
use App\Imports\CustomerImport;
use App\Models\Customer;
use App\Models\SmsAlert;
use App\Models\SmsDelivered;
use App\Models\Transaction;
use App\Models\Package;
use App\Models\Utility;
use App\Jobs\SendBulkMessage;
use Auth;
use App\Helpers\CustomHelper;
use App\Models\User;
use App\Models\Router;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use File;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Devrabiul\ToastMagic\Facades\ToastMagic;


class SmsController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage sms template')) {

            $smsTemplates = SmsAlert::where('created_by', Auth::user()->creatorId())->paginate(10);
            // $smsTemplates = SmsAlert::where('created_by', Auth::user()->creatorId())->get();
            $sites = Router::where('created_by', Auth::user()->creatorId())->get();
            return view('sms.index', compact('smsTemplates', 'sites'));
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function create()
    {
        if(\Auth::user()->can('create sms template'))
        {
            return view('sms.create');
        }
        else
        {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create sms template')) {
            $rules = [
                'type'      => ['required',
                    Rule::unique('smsalerts', 'type')->where(function ($query) {
                        return $query->where('created_by', \Auth::user()->id);
                    })
                ],
                'template'      => 'required',
            ];
            $validator = \Validator::make($request->all(), $rules);

            if($validator->fails())
            {
                $messages = $validator->getMessageBag();
                return redirect()->route('sms.index')->with('error', $messages->first());
            }

            $sms = new SmsAlert();
            $sms->type = $request->type;
            $sms->template = $request->template;
            $sms->status = 1;
            $sms->is_system = 0;
            $sms->created_by = Auth::user()->id;
            $sms->save();

            ToastMagic::error('SMS Template Created Successfully.');
            return redirect()->route('sms.index');
        }else{
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function edit($id)
    {
        if (!Auth::user()->can('edit sms template')) {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }

        $sms = SmsAlert::findOrFail($id);

        return view('sms.edit', compact('sms'));
    }


    public function update(Request $request, $id)
    {
        if (!Auth::user()->can('edit sms template')) {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }

        $sms = SmsAlert::findOrFail($id);

        $request->validate([
            'type' => [
                'required',
                Rule::unique('smsalerts', 'type')->ignore($sms->id)->where(function ($query) {
                    return $query->where('created_by', Auth::id());
                })
            ],
            'template' => 'required',
        ]);

        $sms->update([
            'type' => $request->type,
            'template' => $request->template,
        ]);
        ToastMagic::error('Template successfully updated.');
        return redirect()->route('sms.index');
    }


    public function destroy($id)
    {
        if (!Auth::user()->can('delete sms template')) {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }

        $sms = SmsAlert::findOrFail($id);
        $sms->delete();
        ToastMagic::error('Template deleted successfully.');
        return redirect()->route('sms.index');
    }

    public function smsDelivery()
    {
        if (Auth::user()->can('manage sent sms')) {
            $SmsDelivered = SmsDelivered::where('created_by', Auth::user()->creatorId())->get();
            return view('sms.delivery', compact('SmsDelivered'));
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }

    public function bulkSmsForm()
    {
        if (Auth::user()->can('send bulk sms')) {
            $smsTemplates = SmsAlert::where('created_by', Auth::user()->creatorId())->get();
            // $smsGroups = Customer::whereNotNull('sms_group')
            //     ->where('created_by', Auth::user()->creatorId())
            //     ->pluck('sms_group', 'sms_group');
            $sites = Router::where('created_by', Auth::user()->creatorId())->get();

            return view('sms.bulk', compact('smsTemplates', 'sites'));
        } else {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }
    }



    public function sendBulkSms(Request $request)
    {
        Log::info('SendBulkSms request: ', $request->all());
        $request->validate([
            'sender' => 'required|string',
            'site_id' => 'nullable|numeric',
            'service' => 'required|string',
            'category' => 'required|string',
            'message_type' => 'required|string',
            'template_id' => 'nullable|string',
            'message' => 'nullable|string',
        ]);

        $creatorId = Auth::user()->creatorId();
        $site = null;

        if (!empty($request->site_id) && $request->site_id != 0) {
            $site = Router::where('created_by', $creatorId)->find($request->site_id);
            Log::info('Site found: ', $site ? $site->toArray() : []);
        }

        $baseQuery = Customer::where('created_by', $creatorId);

        if ($request->service !== 'All') {
            $baseQuery->where('service', $request->service);
        }

        if ($site) {
            $baseQuery->where('site', $site->ip_address);
        }

        switch ($request->category) {
            case 'active':
                $baseQuery->where('status', 'on');
                break;
            case 'expired':
                $baseQuery->where('status', 'off');
                break;
            case 'corporate':
                $baseQuery->where('corporate', 1);
                break;
            case 'suspended':
                $baseQuery->where('is_suspended', 1);
                break;
            case 'disabled':
                $baseQuery->where('is_active', 0);
                break;
            case 'new':
                $baseQuery->whereDate('created_at', Carbon::today());
                break;
            case 'all':
            default:
                break;
        }

        $customers = $baseQuery->get();
        Log::info('Customers fetched: ', $customers->toArray());
        $delaySeconds = 0;

        foreach ($customers as $customer) {
            $settings = Utility::settings(auth()->user()->creatorId());
            $phone = $customer->contact;

            // Determine which message to use based on message_type
            if ($request->message_type === 'custom' && !empty($request->message)) {
                $templateText = $request->message;
            } else {
                // Use template only if template_id is provided
                if ($request->template_id) {
                    $smsTemplate = SmsAlert::find($request->template_id);
                    if ($smsTemplate) {
                        $templateText = $smsTemplate->template;
                    } else {
                        Log::error('Template not found for ID: ' . $request->template_id);
                        continue;
                    }
                } else {
                    Log::error('No template or custom message provided');
                    continue;
                }
            }

            $txt = str_replace(
                ['{username}', '{account}', '{fullname}', '{company}', '{balance}', '{support}', '{package}', '{expiry}'],
                [$customer->username, $customer->account, $customer->fullname, $settings['company_name'] ?? 'Our Company', $customer->balance, $settings['company_telephone'], $customer->package, $customer->expiry],
                $templateText
            );

            // Dispatch with incremental delay
            SendBulkMessage::dispatch($customer, $txt, $request->sender, $creatorId)
                ->delay(now()->addSeconds($delaySeconds));

            Log::info("SMS sent to {$customer->username} with message: {$txt}");

            $delaySeconds += 5;
        }
        ToastMagic::success('SMS sent successfully.');
        return redirect()->back();
    }

}
