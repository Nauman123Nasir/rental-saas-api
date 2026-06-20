<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;

class Payment extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'amount'                => 'decimal:2',
        'payment_datetime_utc'  => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function receipt()
    {
        return $this->hasOne(Receipt::class);
    }
}
