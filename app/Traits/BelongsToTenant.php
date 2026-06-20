<?php

namespace App\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    /**
     * Boot the trait to apply tenant-based global scope and automatic assignment.
     */
    protected static function bootBelongsToTenant(): void
    {
        static::creating(function ($model) {
            if (auth()->guard('api')->check() && ! $model->tenant_id) {
                $model->tenant_id = auth()->guard('api')->user()->tenant_id;
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->guard('api')->check()) {
                $builder->where($builder->getQuery()->from . '.tenant_id', auth()->guard('api')->user()->tenant_id);
            }
        });
    }

    /**
     * Get the tenant that owns the model.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
