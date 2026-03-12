<?php

namespace App\Domain\Assets\Models;

use App\Domain\Audits\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StatusLabel extends Model
{
    use Auditable;

    /** @use HasFactory<\Database\Factories\Domain\Assets\Models\StatusLabelFactory> */
    use HasFactory;

    protected $fillable = ['name',
        'color',
        'deployable',
        'is_default',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'deployable' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
