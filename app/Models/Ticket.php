<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;
    protected $table='tickets';
    protected $fillable=[
        'pnrNumber',
        'flight',
        'flightDateTime',
        'numberOfSeats',
        'status'
    ];

    public function passengers()
    {
        return $this->hasMany(TicketAllocationToPassenger::class,'ticketId');
    }
}
