<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentalDriver extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function rental()
    {
        return $this->belongsTo(Rental::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}

