<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;

class Reservation extends Model
{
    use HasFactory, BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'pickup_datetime_utc' => 'datetime',
        'return_datetime_utc' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function pickupBranch()
    {
        return $this->belongsTo(Branch::class, 'pickup_branch_id');
    }

    public function returnBranch()
    {
        return $this->belongsTo(Branch::class, 'return_branch_id');
    }

    public function assets()
    {
        return $this->hasMany(ReservationAsset::class);
    }

    public function drivers()
    {
        return $this->hasMany(ReservationDriver::class);
    }

    public function pricing()
    {
        return $this->hasMany(ReservationPricing::class);
    }

    public function discounts()
    {
        return $this->hasMany(ReservationDiscount::class);
    }

    public function deposits()
    {
        return $this->hasMany(ReservationDeposit::class);
    }

    public function attachments()
    {
        return $this->hasMany(ReservationAttachment::class);
    }

    public function notes()
    {
        return $this->hasMany(ReservationNote::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
