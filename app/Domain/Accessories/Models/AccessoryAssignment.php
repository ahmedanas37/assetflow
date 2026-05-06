<?php

namespace App\Domain\Accessories\Models;

use App\Domain\Assets\Enums\AssignmentType;
use App\Domain\Audits\Concerns\Auditable;
use App\Domain\Locations\Models\Location;
use App\Domain\People\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessoryAssignment extends Model
{
    use Auditable;

    /** @use HasFactory<\Database\Factories\Domain\Accessories\Models\AccessoryAssignmentFactory> */
    use HasFactory;

    protected $fillable = ['accessory_id',
        'assigned_to_type',
        'assigned_to_id',
        'assigned_to_label',
        'assigned_by_user_id',
        'assigned_at',
        'due_at',
        'returned_at',
        'quantity',
        'returned_quantity',
        'notes',
        'location_at_assignment',
        'is_active',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'due_at' => 'datetime',
        'returned_at' => 'datetime',
        'is_active' => 'boolean',
        'quantity' => 'integer',
        'returned_quantity' => 'integer',
        'assigned_to_type' => AssignmentType::class,
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

    public function accessory(): BelongsTo
    {
        return $this->belongsTo(Accessory::class);
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

    public function getAssignedToNameAttribute(): ?string
    {
        return match ($this->assigned_to_type) {
            AssignmentType::User => $this->assignedToUser?->name,
            AssignmentType::Employee => $this->assignedToEmployee?->name,
            AssignmentType::Location => $this->assignedToLocation?->name,
            default => null,
        };
    }

    public function getRemainingQuantityAttribute(): int
    {
        return max(($this->quantity ?? 0) - ($this->returned_quantity ?? 0), 0);
    }
}
