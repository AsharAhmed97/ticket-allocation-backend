<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketAllocationToPassenger extends Model
{
    use HasFactory;
    protected $table='tickets_allocation_to_passengers';
    protected $fillable=[
        'ticketId',
        'passengerId',
    ];
}
