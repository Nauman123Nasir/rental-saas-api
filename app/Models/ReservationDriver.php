<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationDriver extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function driver()
    {
        return $this->belongsTo(Customer::class, 'driver_id');
    }
}
