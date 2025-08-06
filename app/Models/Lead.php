<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'status',
        'by',
        'notes',
        'created_by',
        'date',
        'phone',
        'location',
    ];

    public static $statuses = [
        'new' => 'New',
        'converted' => 'Converted',
        'lost' => 'Lost'
    ];


    public function ticket()
    {
        return $this->hasOne('App\Models\Ticket', 'lead_id', 'id');
    }

    // public function convertToTicket($description = '', $created_by = null)
    // {
    //     if (!$created_by) {
    //         $created_by = \Auth::user()->id;
    //     }

    //     $ticket = new Ticket();
    //     $ticket->subject = $this->subject ?? 'Installation Request for ' . $this->name;
    //     $ticket->description = $description ?: 'Installation request for prospect customer ' . $this->name;
    //     $ticket->lead_id = $this->id;
    //     $ticket->status = 'pending';
    //     $ticket->created_by = $created_by;
    //     $ticket->save();

    //     $this->status = 'converted';
    //     $this->save();
    //     return $ticket;
    // }

    // Relationship to the user who created this lead
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Check if lead is converted to a ticket
    // public function isConverted()
    // {
    //     return !is_null($this->ticket);
    // }
}
