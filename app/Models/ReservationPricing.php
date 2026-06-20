<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
