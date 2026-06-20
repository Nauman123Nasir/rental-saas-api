<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'country_id',
        'timezone_id',
        'currency_id',
        'address',
        'city',
        'state',
        'postal_code',
    ];

    /**
     * Get the tenant that owns the branch.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the users assigned to the branch.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

