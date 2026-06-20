<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationAsset extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'allocated_at' => 'datetime',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function allocatedBy()
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }
}
