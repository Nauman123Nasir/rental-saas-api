<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;

class Rental extends Model
{
    use HasFactory, BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'pickup_datetime_utc' => 'datetime',
        'expected_return_datetime_utc' => 'datetime',
        'actual_return_datetime_utc' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function drivers()
    {
        return $this->hasMany(RentalDriver::class);
    }

    public function extensions()
    {
        return $this->hasMany(RentalExtension::class);
    }

    public function pickupInspection()
    {
        return $this->hasOne(RentalPickupInspection::class);
    }

    public function returnInspection()
    {
        return $this->hasOne(RentalReturnInspection::class);
    }

    public function charges()
    {
        return $this->hasMany(RentalCharge::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }
}
