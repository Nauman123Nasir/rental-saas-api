<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'category_id',
        'asset_code',
        'name',
        'brand',
        'model',
        'year',
        'vin_number',
        'serial_number',
        'status',
        'ownership_type',
        'current_mileage',
        'current_hours',
        'fuel_type',
        'transmission',
        'daily_rate',
        'weekly_rate',
        'monthly_rate',
        'hourly_rate',
        'currency_id',
    ];

    protected $casts = [
        'year' => 'integer',
        'current_mileage' => 'integer',
        'current_hours' => 'decimal:2',
        'daily_rate' => 'decimal:2',
        'weekly_rate' => 'decimal:2',
        'monthly_rate' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    public function blocks()
    {
        return $this->hasMany(AssetBlock::class);
    }

    // Convenience relation for only maintenance blocks
    public function maintenanceBlocks()
    {
        return $this->hasMany(AssetBlock::class)->where('block_type', 'Maintenance');
    }
}
