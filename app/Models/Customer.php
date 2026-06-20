<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'customer_code',
        'type', // 'Individual', 'Business'
        'first_name',
        'last_name',
        'company_name',
        'email',
        'phone',
        'status', // 'active', 'inactive', 'suspended'
        'credit_limit',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
    ];

    /**
     * Get the documents for the customer.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }

    /**
     * Get the drivers registered under this customer.
     */
    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }
}
