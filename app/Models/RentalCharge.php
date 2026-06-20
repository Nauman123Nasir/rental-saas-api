<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RentalCharge extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function rental()
    {
        return $this->belongsTo(Rental::class);
    }
}

