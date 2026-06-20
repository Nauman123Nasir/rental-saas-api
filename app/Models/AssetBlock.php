<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetBlock extends Model
{
    protected $fillable = [
        'tenant_id',
        'asset_id',
        'block_type',
        'start_datetime',
        'end_datetime',
        'reason',
        'reference_type',
        'reference_id',
        'cost',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'cost' => 'decimal:2',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    // For polymorphic relations like Reservations or Rentals
    public function reference()
    {
        return $this->morphTo();
    }
}
