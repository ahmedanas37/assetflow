<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Audits\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manufacturer extends Model
{
    use Auditable;

    /** @use HasFactory<\Database\Factories\Domain\Inventory\Models\ManufacturerFactory> */
    use HasFactory;

    protected $fillable = ['name',
        'notes',
    ];

    public function assetModels(): HasMany
    {
        return $this->hasMany(AssetModel::class);
    }
}
