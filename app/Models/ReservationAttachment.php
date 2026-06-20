<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservationAttachment extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
