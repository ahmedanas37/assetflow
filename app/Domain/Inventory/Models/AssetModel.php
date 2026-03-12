<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Assets\Models\Asset;
use App\Domain\Audits\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetModel extends Model
{
    use Auditable;

    /** @use HasFactory<\Database\Factories\Domain\Inventory\Models\AssetModelFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['manufacturer_id',
        'category_id',
        'name',
        'model_number',
        'depreciation_months',
        'notes',
    ];

    protected $casts = [
        'depreciation_months' => 'integer',
    ];

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
