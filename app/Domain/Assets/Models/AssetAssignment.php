<?php

namespace App\Domain\Assets\Models;

use App\Domain\Assets\Enums\AssetCondition;
use App\Domain\Assets\Enums\AssignmentType;
use App\Domain\Audits\Concerns\Auditable;
use App\Domain\Locations\Models\Location;
use App\Domain\People\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AssetAssignment extends Model
{
    use Auditable;

    /** @use HasFactory<\Database\Factories\Domain\Assets\Models\AssetAssignmentFactory> */
    use HasFactory;

    protected $fillable = ['asset_id',
        'assigned_to_type',
        'assigned_to_id',
        'assigned_to_label',
        'assigned_by_user_id',
        'assigned_at',
        'due_at',
        'returned_at',
        'return_condition',
        'notes',
        'location_at_assignment',
        'is_active',
        'active_asset_id',
        'transferred_from_id',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'due_at' => 'datetime',
        'returned_at' => 'datetime',
        'is_active' => 'boolean',
        'active_asset_id' => 'integer',
        'transferred_from_id' => 'integer',
        'assigned_to_type' => AssignmentType::class,
        'return_condition' => AssetCondition::class,
    ];

    private ?User $auditActor = null;

    public function setAuditActor(?User $actor): static
    {
        $this->auditActor = $actor;

        return $this;
    }

    public function auditActor(): ?User
    {
        return $this->auditActor;
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function assignedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function assignedToLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'assigned_to_id');
    }

    public function assignedToEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to_id')->withTrashed();
    }

    public function transferredFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'transferred_from_id');
    }

    public function transferredTo(): HasOne
    {
        return $this->hasOne(self::class, 'transferred_from_id');
    }

    public function getAssignedToNameAttribute(): ?string
    {
        return match ($this->assigned_to_type) {
            AssignmentType::User => $this->assignedToUser?->name,
            AssignmentType::Employee => $this->assignedToEmployee?->name,
            AssignmentType::Location => $this->assignedToLocation?->name,
            default => null,
        };
    }
}
