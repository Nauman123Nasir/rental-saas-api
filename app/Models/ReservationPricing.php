<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReservationPricing extends Model
{
    use HasFactory;

    protected $table = 'reservation_pricing';
    protected $guarded = [];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
}

