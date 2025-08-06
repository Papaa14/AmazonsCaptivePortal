<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'customer_id',
        'ticket_id',
        'technician_id',
        'subject',
        'description',
        'status',
        'priority',
        'installation_date',
        'installation_time',
        'location',
        'notes',
        'created_by',
    ];

    public static $statuses = [
        'open' => 'Open',
        'assigned' => 'Assigned',
        'in_progress' => 'In Progress',
        'done' => 'Done',
        'closed' => 'Closed',
        'cancelled' => 'Cancelled'
    ];
    public static $priorities = [
        'normal' => 'Normal',
        'low' => 'Low',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    // Relationship to the lead that created this ticket
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
    public function messages()
    {
        return $this->hasMany(TicketMessage::class);
    }

    // Relationship to the technician assigned to this ticket
    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    // Relationship to the customer created from this ticket
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    // Check if ticket is converted to a customer
    public function isConverted()
    {
        return !is_null($this->customer);
    }
}
