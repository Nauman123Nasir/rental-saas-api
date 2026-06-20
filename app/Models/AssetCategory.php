<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AssetCategory extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
    ];

    public function assets()
    {
        return $this->hasMany(Asset::class, 'category_id');
    }
}
