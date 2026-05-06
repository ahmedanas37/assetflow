<?php

namespace App\Domain\Accessories\Services;

use App\Domain\Accessories\Models\Accessory;
use App\Domain\Accessories\Models\AccessoryAssignment;
use App\Domain\Assets\Enums\AssignmentType;
use App\Domain\Locations\Models\Location;
use App\Domain\People\Models\Employee;
use App\Models\User;
use App\Services\ReceiptMailer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccessoryAssignmentService
{
    public function checkout(
        Accessory $accessory,
        AssignmentType $type,
        int $assignedToId,
        User $actor,
        int $quantity,
        \DateTimeInterface|string|null $dueAt = null,
        ?string $notes = null,
        ?string $assignedToLabel = null,
    ): AccessoryAssignment {
        $dueAt = $this->normalizeDueAt($dueAt);

        return DB::transaction(function () use ($accessory, $type, $assignedToId, $actor, $quantity, $dueAt, $notes, $assignedToLabel): AccessoryAssignment {
            $accessory = Accessory::query()->lockForUpdate()->findOrFail($accessory->id);

            if ($quantity < 1) {
                throw ValidationException::withMessages([
                    'quantity' => 'Quantity must be at least 1.',
                ]);
            }

            if ($accessory->quantity_available < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'Not enough stock available for this accessory.',
                ]);
            }

            if ($type === AssignmentType::Location && empty($assignedToLabel)) {
                throw ValidationException::withMessages([
                    'assigned_to_label' => 'Cubicle or system name is required for location assignments.',
                ]);
            }

            $assignedTo = match ($type) {
                AssignmentType::User => User::find($assignedToId),
                AssignmentType::Employee => Employee::find($assignedToId),
                AssignmentType::Location => Location::find($assignedToId),
            };

            if (! $assignedTo) {
                throw ValidationException::withMessages([
                    'assigned_to_id' => 'Assigned target is invalid.',
                ]);
            }

            $assignment = new AccessoryAssignment([
                'accessory_id' => $accessory->id,
                'assigned_to_type' => $type->value,
                'assigned_to_id' => $assignedToId,
                'assigned_to_label' => $assignedToLabel,
                'assigned_by_user_id' => $actor->id,
                'assigned_at' => now(),
                'due_at' => $dueAt,
                'quantity' => $quantity,
                'notes' => $notes,
                'location_at_assignment' => $accessory->location?->name,
            ]);

            $assignment->setAuditActor($actor);
            $assignment->save();

            $accessory->quantity_available = max($accessory->quantity_available - $quantity, 0);
            $accessory->save();

            DB::afterCommit(function () use ($assignment): void {
                app(ReceiptMailer::class)->sendAccessoryAssignmentReceipt($assignment);
            });

            return $assignment;
        });
    }

    public function checkin(
        AccessoryAssignment $assignment,
        User $actor,
        int $quantity,
        ?string $notes = null,
    ): AccessoryAssignment {
        return DB::transaction(function () use ($assignment, $actor, $quantity, $notes): AccessoryAssignment {
            $assignment = AccessoryAssignment::query()->lockForUpdate()->findOrFail($assignment->id);

            if (! $assignment->is_active) {
                throw ValidationException::withMessages([
                    'assignment' => 'This assignment is already closed.',
                ]);
            }

            $remaining = $assignment->remaining_quantity;

            if ($quantity < 1 || $quantity > $remaining) {
                throw ValidationException::withMessages([
                    'quantity' => 'Return quantity must be between 1 and the remaining quantity.',
                ]);
            }

            $accessory = Accessory::query()->lockForUpdate()->findOrFail($assignment->accessory_id);

            $assignment->returned_quantity = $assignment->returned_quantity + $quantity;
            if ($notes) {
                $assignment->notes = $notes;
            }
            $assignment->setAuditActor($actor);
            $assignment->save();

            $accessory->quantity_available = min($accessory->quantity_available + $quantity, $accessory->quantity_total);
            $accessory->save();

            DB::afterCommit(function () use ($assignment): void {
                app(ReceiptMailer::class)->sendAccessoryReturnConfirmation($assignment);
            });

            return $assignment;
        });
    }

    private function normalizeDueAt(\DateTimeInterface|string|null $dueAt): ?\DateTimeInterface
    {
        if ($dueAt instanceof \DateTimeInterface) {
            return $dueAt;
        }

        if (is_string($dueAt) && trim($dueAt) !== '') {
            return Carbon::parse($dueAt);
        }

        return null;
    }
}
