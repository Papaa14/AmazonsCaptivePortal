<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Package;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Devrabiul\ToastMagic\Facades\ToastMagic;

class TicketController extends Controller
{
    /**
     * Display a listing of the installation tickets.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!Auth::user()->can('manage ticket')) {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }

        $query = Ticket::query();

        if (Auth::user()->hasRole('technician')) {
            $query->where('technician_id', Auth::user()->id);
        } else {
            $query->where('created_by', Auth::user()->creatorId());
        }

        $tickets = $query->paginate(10);

        $technicians = User::where('created_by', Auth::user()->creatorId())
            ->whereIn('type', ['technician', 'company'])
            ->get();

        $customers = Customer::where('created_by', Auth::user()->creatorId())
            ->where('service', 'PPPoE')
            ->get();

        return view('tickets.index', compact('tickets', 'technicians', 'customers'));
    }

    public function store(Request $request)
    {
        \Log::info($request);
        $request->hasFile('attachments');
        if (!\Auth::user()->can(abilities: 'create ticket')) {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }

        // Validate the request
        $validator = \Validator::make($request->all(), [
            'technician_id' => 'nullable|exists:users,id',
            'installation_date' => 'required|date',
            'installation_time' => 'required',
            'subject' => 'nullable|string',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'attachments.*' => 'nullable|file|max:5120', // max 5MB per file
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $customer = Customer::findOrFail($request->customer_id);
        $ticketId = 'TKT' . str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $status = $request->technician_id ? 'assigned' : 'open';

        // Save ticket
        $ticket = new Ticket();
        $ticket->customer_id = $customer->id;
        $ticket->ticket_id = $ticketId;
        $ticket->subject = $request->subject;
        $ticket->description = $request->description;
        $ticket->technician_id = $request->technician_id;
        $ticket->status = $status;
        $ticket->installation_date = $request->installation_date;
        $ticket->installation_time = $request->installation_time;
        $ticket->location = $customer->location ?? 'N/A';
        $ticket->notes = $request->notes;
        $ticket->created_by = \Auth::user()->id;
        $ticket->saveOrFail();

        // Optional: create initial message
        if ($request->description) {
            $message = new TicketMessage();
            $message->ticket_id = $ticket->id;
            $message->user_id = \Auth::id();
            $message->message = $request->description;
            $message->save();

            // Save attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('public/tickets'); // <--- FIXED
                    $attachment = new TicketAttachment();
                    $attachment->ticket_message_id = $message->id ?? null; // or null if no message yet
                    $attachment->file_path = $path;
                    $attachment->file_type = $file->getClientOriginalExtension();
                    $attachment->save();
                }
            }
        }
        ToastMagic::success('Ticket created successfully.');
        return redirect()->back();
    }

    public function show(Ticket $ticket) {
        \Log::info('Ticket Show Called: ', ['ticket_id' => $ticket->id]);

        if (!Auth::user()->can('view ticket')) {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }

        if (Auth::user()->hasRole('technician') && $ticket->technician_id !== Auth::user()->id) {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }

        $ticket->load([
            'messages.user',
            'messages.attachments',
        ]);
        $status = [
            'open' => 'Open',
            'assigned' => 'Assigned',
            'in_progress' => 'In Progress',
            'done' => 'Done',
            'closed' => 'Closed',
            'cancelled' => 'Cancelled'
        ];
        $priority = [
            'normal' => 'Normal',
            'low' => 'Low',
            'high' => 'High',
            'urgent' => 'Urgent',
        ];
        $customers = Customer::where('created_by', Auth::user()->creatorId())->where('service', 'PPPoE')->get();
        $technicians = User::where(function ($q) {
                $q->where('type', 'technician')
                ->where('created_by', Auth::user()->creatorId());
            })
            ->orWhere(function ($q) {
                $q->where('id', Auth::user()->creatorId())
                ->where('type', 'company');
            })
            ->get();

        return view('tickets.show', compact('ticket', 'customers', 'status', 'priority', 'technicians'));
    }

    public function storeMessage(Request $request, Ticket $ticket)
    {
        $request->validate([
            'message' => 'nullable|string',
            'attachments.*' => 'nullable|file|max:5120', // 5MB max
        ]);

        $msg = new TicketMessage();
        $msg->ticket_id = $ticket->id;
        $msg->user_id = Auth::id();
        $msg->message = $request->message;
        $msg->save();

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filename = uniqid() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('public/tickets', $filename);
                $attachment = new TicketAttachment();
                $attachment->ticket_message_id = $msg->id;
                $attachment->file_path = $path;
                $attachment->file_type = $file->getClientOriginalExtension();
                $attachment->save();
            }
        }

        ToastMagic::success('Reply posted successfully.',);
        return redirect()->back()->with('success', 'Reply posted successfully.');
    }
    public function update(Request $request, $id)
    {
        if (!\Auth::user()->can('edit ticket')) {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }

        // Validate request
        $validator = \Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'technician_id' => 'nullable|exists:users,id',
            'installation_date' => 'required|date',
            'installation_time' => 'required',
            'subject' => 'nullable|string',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'required|string',
            'priority' => 'required|string',
            'attachments.*' => 'nullable|file|max:5120', // Max 5MB each
        ]);

        if ($validator->fails()) {
            ToastMagic::error($validator->errors());
            return redirect()->back();
        }

        $ticket = Ticket::findOrFail($id);

        // Update fields
        $ticket->customer_id = $request->customer_id;
        $ticket->technician_id = $request->technician_id;
        $ticket->installation_date = $request->installation_date;
        $ticket->installation_time = $request->installation_time;
        $ticket->subject = $request->subject;
        $ticket->description = $request->description;
        $ticket->notes = $request->notes;
        $ticket->status = $request->status;
        $ticket->priority = $request->priority;
        $ticket->save();

        // Create a new message for the update
        if ($request->description) {
            $message = new TicketMessage();
            $message->ticket_id = $ticket->id;
            $message->user_id = \Auth::id();
            $message->message = $request->description;
            $message->save();

            // Save new attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $filename = uniqid() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('public/tickets', $filename);

                    $attachment = new TicketAttachment();
                    $attachment->ticket_message_id = $message->id;
                    $attachment->file_path = $path;
                    $attachment->file_type = $file->getClientOriginalExtension();
                    $attachment->save();
                }
            }
        }
        ToastMagic::success('Ticket updated successfully.');
        return redirect()->route('tickets.show', $ticket->id);
    }


    public function updateStatus(Request $request, $id)
    {
        if(!Auth::user()->can('edit ticket')) {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }

        $ticket = Ticket::findOrFail($id);

        // Validate technician can only update their assigned tickets
        if(Auth::user()->hasRole('technician') && $ticket->technician_id !== Auth::user()->id) {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }

        $ticket->status = $request->status;
        $ticket->save();
        ToastMagic::success('Ticket status updated successfully!');
        return redirect()->back();
    }

    /**
     * Show the form for converting a ticket to a customer.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showConvertToCustomer($id)
    {
        if(!Auth::user()->can('convert ticket')) {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }

        $ticket = Ticket::findOrFail($id);

        // Only tickets marked as "done" can be converted
        if($ticket->status !== 'done') {
            ToastMagic::error('Ticket must be marked as done first.');
            return redirect()->back();
        }

        // Make sure technician can only convert their assigned tickets
        if(Auth::user()->hasRole('technician') && $ticket->technician_id !== Auth::user()->id) {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }

        // Get all packages for selection
        $packages = Package::where('created_by', Auth::user()->creatorId())
            ->where('status', 'active')
            ->get();

        return view('tickets.convert-to-customer', compact('ticket', 'packages'));
    }

    /**
     * Convert ticket to customer.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function convertToCustomer(Request $request, $id)
    {
        if(!Auth::user()->can('convert ticket')) {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }

        $ticket = Ticket::findOrFail($id);

        // Only tickets marked as "done" can be converted
        if($ticket->status !== 'done') {
            return redirect()->back()->with('error', __('Ticket must be marked as done first.'));
        }

        // Make sure technician can only convert their assigned tickets
        if(Auth::user()->hasRole('technician') && $ticket->technician_id !== Auth::user()->id) {
            ToastMagic::error('Permission denied.');
            return redirect()->back();
        }

        // Validate request
        $validator = \Validator::make($request->all(), [
            'fullname' => 'required|string|max:255',
            'contact' => 'required|string|max:20',
            'location' => 'required|string|max:255',
            'package_id' => 'required|exists:packages,id',
            'service' => 'required|in:PPPoE,Static',
        ]);

        if($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Get lead information
        $lead = $ticket->lead;

        // Generate PPPoE credentials
        $username = 'user_' . strtolower(Str::random(8));
        $password = strtoupper(Str::random(8));

        // Create customer
        $customer = new Customer();
        $customer->fullname = $request->fullname;
        $customer->contact = $request->contact;
        $customer->email = $request->email;
        $customer->location = $request->location;
        $customer->housenumber = $request->housenumber;
        $customer->apartment = $request->apartment;
        $customer->username = $username;
        $customer->password = $password; // Store in plain text for PPPoE access
        $customer->account = $username; // Same as username
        $customer->package = $request->package_id;
        $customer->service = $request->service;
        $customer->mac_address = $request->mac_address;
        $customer->is_active = 1;
        $customer->status = 'on';
        $customer->expiry = date('Y-m-d', strtotime('+30 days')); // Default 30 day expiry
        $customer->created_by = Auth::user()->creatorId();
        $customer->ticket_id = $ticket->id;
        $customer->save();

        // Update ticket status to closed
        $ticket->status = 'closed';
        $ticket->save();

        return view('tickets.customer-created', [
            'ticket' => $ticket,
            'customer' => $customer,
            'username' => $username,
            'password' => $password
        ]);
    }
    public function destroy($id)
    {
        $attachment = TicketAttachment::findOrFail($id);
        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();
        ToastMagic::success('Attachment deleted.');
        return back();
    }
}
