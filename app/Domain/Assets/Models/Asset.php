<?php

namespace App\Domain\Assets\Models;

use App\Domain\Assets\Enums\AssignmentType;
use App\Domain\Attachments\Models\Attachment;
use App\Domain\Audits\Concerns\Auditable;
use App\Domain\Audits\Models\AuditLog;
use App\Domain\Inventory\Models\AssetModel;
use App\Domain\Inventory\Models\Category;
use App\Domain\Locations\Models\Location;
use App\Domain\Maintenance\Models\MaintenanceLog;
use App\Domain\Vendors\Models\Vendor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use Auditable;

    /** @use HasFactory<\Database\Factories\Domain\Assets\Models\AssetFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = ['asset_tag',
        'serial',
        'asset_model_id',
        'category_id',
        'status_label_id',
        'location_id',
        'assigned_to_user_id',
        'purchase_date',
        'purchase_cost',
        'vendor_id',
        'warranty_end_date',
        'notes',
        'image_path',
        'custom_fields',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_end_date' => 'date',
        'purchase_cost' => 'decimal:2',
        'custom_fields' => 'array',
    ];

    public function assetModel(): BelongsTo
    {
        return $this->belongsTo(AssetModel::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function statusLabel(): BelongsTo
    {
        return $this->belongsTo(StatusLabel::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function assignedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class);
    }

    public function activeAssignment(): HasOne
    {
        return $this->hasOne(AssetAssignment::class)->where('is_active', true);
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'entity');
    }

    public function isDeployable(): bool
    {
        return (bool) $this->statusLabel?->deployable;
    }

    public function getAssignedToDisplayAttribute(): string
    {
        $assignment = $this->activeAssignment;

        if (! $assignment) {
            return 'Unassigned';
        }

        $name = $assignment->assigned_to_name;

        if (! $name) {
            return 'Unassigned';
        }

        $label = $assignment->assigned_to_label;

        if ($assignment->assigned_to_type === AssignmentType::Location && $label) {
            return "{$name} ({$label})";
        }

        if ($label) {
            return "{$name} · {$label}";
        }

        return $name;
    }
}
