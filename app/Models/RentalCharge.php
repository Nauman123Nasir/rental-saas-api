<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentalCharge extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function rental()
    {
        return $this->belongsTo(Rental::class);
    }
}
