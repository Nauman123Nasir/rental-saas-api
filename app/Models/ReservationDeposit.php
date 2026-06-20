<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservationDeposit extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'deposit_date' => 'date',
        'refund_date' => 'date',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
}

