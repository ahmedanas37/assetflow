<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Assets\Models\Asset;
use App\Domain\Audits\Concerns\Auditable;
use App\Domain\Inventory\Enums\CategoryType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use Auditable;

    /** @use HasFactory<\Database\Factories\Domain\Inventory\Models\CategoryFactory> */
    use HasFactory;

    protected $fillable = ['name',
        'type',
        'depreciation_months',
        'prefix',
        'notes',
    ];

    protected $casts = [
        'type' => CategoryType::class,
        'depreciation_months' => 'integer',
    ];

    public function assetModels(): HasMany
    {
        return $this->hasMany(AssetModel::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
