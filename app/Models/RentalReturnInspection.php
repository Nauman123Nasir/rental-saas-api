<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RentalReturnInspection extends Model
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

