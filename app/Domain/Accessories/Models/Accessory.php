<?php

namespace App\Domain\Accessories\Models;

use App\Domain\Audits\Concerns\Auditable;
use App\Domain\Inventory\Models\Category;
use App\Domain\Inventory\Models\Manufacturer;
use App\Domain\Locations\Models\Location;
use App\Domain\Vendors\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Accessory extends Model
{
    use Auditable;

    /** @use HasFactory<\Database\Factories\Domain\Accessories\Models\AccessoryFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = ['name',
        'category_id',
        'manufacturer_id',
        'vendor_id',
        'location_id',
        'model_number',
        'quantity_total',
        'quantity_available',
        'reorder_threshold',
        'notes',
        'image_path',
    ];

    protected $casts = [
        'quantity_total' => 'integer',
        'quantity_available' => 'integer',
        'reorder_threshold' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Accessory $accessory): void {
            if ($accessory->quantity_available === null) {
                $accessory->quantity_available = $accessory->quantity_total ?? 0;
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AccessoryAssignment::class);
    }

    public function getQuantityCheckedOutAttribute(): int
    {
        return max(($this->quantity_total ?? 0) - ($this->quantity_available ?? 0), 0);
    }
}
