<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RentalPickupInspection extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'inspection_date' => 'datetime',
    ];

    public function rental()
    {
        return $this->belongsTo(Rental::class);
    }
}

