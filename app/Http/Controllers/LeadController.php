<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Devrabiul\ToastMagic\Facades\ToastMagic;

class LeadController extends Controller
{
    /**
     * Display a listing of the leads.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(\Auth::user()->can('manage lead'))
        {
            $leads = Lead::where('created_by', '=', \Auth::user()->creatorId())->paginate(10);

            $arrStatus = [
                'new' => __('Pending'),
                'converted' => __('Converted'),
                'lost' => __('Lost'),
            ];
            $technicians = User::where(function ($q) {
                $q->where('type', 'technician')
                ->where('created_by', Auth::user()->creatorId());
            })
            ->orWhere(function ($q) {
                $q->where('id', Auth::user()->creatorId())
                ->where('type', 'company');
            })
            ->get();

            return view('leads.index', compact('leads', 'arrStatus', 'technicians'));
        }
        else
        {
            ToastMagic::error('Permission Denied.');
            return redirect()->back();
        }
    }

    /**
     * Store a newly created lead in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if(!\Auth::user()->can('create lead'))
        {
            return response()->json(['error' => __('Permission Denied.')], 401);
        }

        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'location' => 'required|string|max:255',
        ]);

        if($validator->fails())
        {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $lead = new Lead();
        $lead->name = $request->name;
        $lead->email = $request->email;
        $lead->phone = $request->phone;
        $lead->location = $request->location;
        $lead->notes = $request->notes;
        $lead->date = $request->date;
        $lead->status = 'new';
        $lead->by = $request->by;
        $lead->created_by = \Auth::user()->creatorId();
        $lead->save();

        return redirect()->back();
    }

    /**
     * Display the specified lead.
     *
     * @param  \App\Models\Lead  $lead
     * @return \Illuminate\Http\Response
     */
    public function show(Lead $lead)
    {
        if(!\Auth::user()->can('view lead'))
        {
            ToastMagic::error('Permission Denied.');
            return redirect()->back();
        }

        return view('leads.show', compact('lead'));
    }

    /**
     * Show the form for converting lead to an installation ticket.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function convertToTicket($id)
    {
        if(!\Auth::user()->can('edit lead'))
        {
            ToastMagic::error('Permission Denied.');
            return redirect()->back();
        }

        $lead = Lead::findOrFail($id);

        // Only leads that aren't already converted can be converted
        if($lead->status === 'converted')
        {
            ToastMagic::error('Lead is already converted.');
            return redirect()->back()->with('error', __('Lead is already converted.'));
        }

        // Get technicians (users with technician role)
        $technicians = User::whereHas('roles', function($query) {
            $query->where('name', 'technician');
        })->where('created_by', \Auth::user()->creatorId())
        ->get()->pluck('name', 'id');

        return view('leads.convert-to-ticket', compact('lead', 'technicians'));
    }

    /**
     * Convert the lead to an installation ticket.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function storeTicket(Request $request, $id)
    {
        if(!\Auth::user()->can('edit lead'))
        {
            ToastMagic::error('Permission Denied.');
            return redirect()->back();
        }

        $lead = Lead::findOrFail($id);

        // Validate the request
        $validator = \Validator::make($request->all(), [
            'technician_id' => 'required|exists:users,id',
            'installation_date' => 'required|date',
            'installation_time' => 'required',
            'notes' => 'nullable|string',
        ]);

        if($validator->fails())
        {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $ticketId = 'TKT' . str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // Create the ticket
        $ticket = new Ticket();
        $ticket->lead_id = $lead->id;
        $ticket->ticket_id = $ticketId;
        $ticket->subject = 'Installation for ' . $lead->name;
        $ticket->description = 'Installation request for new customer.';
        $ticket->technician_id = $request->technician_id;
        $ticket->status = 'assigned';
        $ticket->installation_date = $request->installation_date;
        $ticket->installation_time = $request->installation_time;
        $ticket->location = $lead->location;
        $ticket->notes = $request->notes;
        $ticket->created_by = \Auth::user()->id;
        $ticket->save();

        // Update lead status to converted
        $lead->status = 'converted';
        $lead->save();

        // Notify technician (implement later)
        // You'd add notification logic here

        // return redirect()->route('tickets.index')->with('success', __('Lead successfully converted to installation ticket.'));
        return redirect()->back();
    }

    /**
     * Update the lead status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request, $id)
    {
        if(!\Auth::user()->can('edit lead'))
        {
            return response()->json(['error' => __('Permission Denied.')], 401);
        }

        $lead = Lead::findOrFail($id);
        $lead->status = $request->status;
        $lead->save();

        return redirect()->back();
        // return response()->json([
        //     'success' => true,
        //     'message' => __('Lead status updated successfully!'),
        // ]);
    }

    /**
     * Remove the specified lead from storage.
     *
     * @param  \App\Models\Lead  $lead
     * @return \Illuminate\Http\Response
     */
    public function destroy(Lead $lead)
    {
        if(!\Auth::user()->can('delete lead'))
        {
            ToastMagic::error('Permission Denied.');
            return redirect()->back();
        }

        // Check if lead has already been converted to a ticket
        if($lead->status === 'converted')
        {
            ToastMagic::error('Cannot delete a converted lead.');
            return redirect()->back()->with('error', __('Cannot delete a converted lead.'));
        }

        // Delete associated records
        // UserLead::where('lead_id', $lead->id)->delete();

        // Delete the lead
        $lead->delete();
        ToastMagic::success('Lead successfully deleted!');
        return redirect()->route('leads.index')->with('success', __('Lead successfully deleted!'));
    }
}
