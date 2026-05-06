<?php

namespace App\Domain\Assets\Services;

use App\Domain\Assets\Enums\AssetCondition;
use App\Domain\Assets\Enums\AssignmentType;
use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\AssetAssignment;
use App\Domain\Assets\Models\StatusLabel;
use App\Domain\Audits\Services\AuditLogger;
use App\Domain\Locations\Models\Location;
use App\Domain\People\Models\Employee;
use App\Models\User;
use App\Services\ReceiptMailer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignmentService
{
    public function checkout(
        Asset $asset,
        AssignmentType $type,
        int $assignedToId,
        User $actor,
        \DateTimeInterface|string|null $dueAt = null,
        ?string $notes = null,
        ?string $assignedToLabel = null,
    ): AssetAssignment {
        $dueAt = $this->normalizeDueAt($dueAt);

        return DB::transaction(function () use ($asset, $type, $assignedToId, $actor, $dueAt, $notes, $assignedToLabel): AssetAssignment {
            $asset = Asset::query()->lockForUpdate()->findOrFail($asset->id);
            $asset->loadMissing('statusLabel');

            if ($asset->activeAssignment()->exists()) {
                throw ValidationException::withMessages([
                    'asset' => 'This asset is already checked out.',
                ]);
            }

            if (! $asset->isDeployable()) {
                throw ValidationException::withMessages([
                    'asset' => 'This asset is not deployable in its current status.',
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

            if ($type === AssignmentType::Location && empty($assignedToLabel)) {
                throw ValidationException::withMessages([
                    'assigned_to_label' => 'Cubicle or system name is required for location assignments.',
                ]);
            }

            $assignment = new AssetAssignment([
                'asset_id' => $asset->id,
                'assigned_to_type' => $type->value,
                'assigned_to_id' => $assignedToId,
                'assigned_to_label' => $assignedToLabel,
                'assigned_by_user_id' => $actor->id,
                'assigned_at' => now(),
                'due_at' => $dueAt,
                'notes' => $notes,
                'location_at_assignment' => $asset->location?->name,
            ]);

            $assignment->setAuditActor($actor);
            $assignment->save();

            $deployStatus = StatusLabel::query()->where('name', 'Deployed')->first();
            if ($deployStatus) {
                $asset->status_label_id = $deployStatus->id;
            }

            $asset->assigned_to_user_id = $type === AssignmentType::User ? $assignedToId : null;
            $asset->save();

            DB::afterCommit(function () use ($assignment): void {
                app(ReceiptMailer::class)->sendAssetAssignmentReceipt($assignment);
            });

            return $assignment;
        });
    }

    public function checkin(
        Asset $asset,
        User $actor,
        ?AssetCondition $condition = null,
        ?string $notes = null,
        ?int $statusLabelId = null,
    ): AssetAssignment {
        return DB::transaction(function () use ($asset, $actor, $condition, $notes, $statusLabelId): AssetAssignment {
            $asset = Asset::query()->lockForUpdate()->findOrFail($asset->id);

            $assignment = AssetAssignment::query()
                ->where('asset_id', $asset->id)
                ->where('is_active', true)
                ->first();

            if (! $assignment) {
                throw ValidationException::withMessages([
                    'asset' => 'This asset is not checked out.',
                ]);
            }

            $assignment->returned_at = now();
            $assignment->return_condition = $condition?->value;
            $assignment->notes = $notes ?: $assignment->notes;
            $assignment->setAuditActor($actor);
            $assignment->save();

            $asset->assigned_to_user_id = null;

            if ($statusLabelId) {
                $asset->status_label_id = $statusLabelId;
            } else {
                $defaultStatus = StatusLabel::query()->where('name', 'In Stock')->first();
                if ($defaultStatus) {
                    $asset->status_label_id = $defaultStatus->id;
                }
            }

            $asset->save();

            DB::afterCommit(function () use ($assignment): void {
                app(ReceiptMailer::class)->sendAssetReturnConfirmation($assignment);
            });

            return $assignment;
        });
    }

    public function transfer(
        Asset $asset,
        AssignmentType $type,
        int $assignedToId,
        User $actor,
        \DateTimeInterface|string|null $dueAt = null,
        ?string $notes = null,
        ?string $assignedToLabel = null,
    ): AssetAssignment {
        $dueAt = $this->normalizeDueAt($dueAt);

        return DB::transaction(function () use ($asset, $type, $assignedToId, $actor, $dueAt, $notes, $assignedToLabel): AssetAssignment {
            $asset = Asset::query()->lockForUpdate()->findOrFail($asset->id);

            $current = AssetAssignment::query()
                ->where('asset_id', $asset->id)
                ->where('is_active', true)
                ->first();

            if (! $current) {
                throw ValidationException::withMessages([
                    'asset' => 'This asset is not currently checked out.',
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

            $sameTarget = $current->assigned_to_type === $type
                && $current->assigned_to_id === $assignedToId
                && (string) $current->assigned_to_label === (string) $assignedToLabel;

            if ($sameTarget) {
                throw ValidationException::withMessages([
                    'assigned_to_id' => 'This asset is already assigned to the selected target.',
                ]);
            }

            $current->returned_at = now();
            $current->is_active = false;
            $current->setAuditActor($actor);
            $current->save();

            $assignment = new AssetAssignment([
                'asset_id' => $asset->id,
                'assigned_to_type' => $type->value,
                'assigned_to_id' => $assignedToId,
                'assigned_to_label' => $assignedToLabel,
                'assigned_by_user_id' => $actor->id,
                'assigned_at' => now(),
                'due_at' => $dueAt,
                'notes' => $notes,
                'location_at_assignment' => $asset->location?->name,
                'transferred_from_id' => $current->id,
            ]);

            $assignment->setAuditActor($actor);
            $assignment->save();

            $asset->assigned_to_user_id = $type === AssignmentType::User ? $assignedToId : null;
            $asset->save();

            $fromType = $current->assigned_to_type instanceof \BackedEnum
                ? $current->assigned_to_type->value
                : $current->assigned_to_type;

            $toType = $assignment->assigned_to_type instanceof \BackedEnum
                ? $assignment->assigned_to_type->value
                : $assignment->assigned_to_type;

            DB::afterCommit(function () use ($asset, $actor, $current, $assignment, $assignedTo, $fromType, $toType): void {
                AuditLogger::log($asset, 'asset_transferred', [
                    'from_type' => $fromType,
                    'from_id' => $current->assigned_to_id,
                    'from_name' => $current->assigned_to_name,
                    'from_label' => $current->assigned_to_label,
                    'returned_at' => optional($current->returned_at)->toDateTimeString(),
                ], [
                    'to_type' => $toType,
                    'to_id' => $assignment->assigned_to_id,
                    'to_name' => $assignedTo?->name,
                    'to_label' => $assignment->assigned_to_label,
                    'assigned_at' => optional($assignment->assigned_at)->toDateTimeString(),
                    'transferred_from_id' => $assignment->transferred_from_id,
                ], $actor);

                app(ReceiptMailer::class)->sendAssetAssignmentReceipt($assignment);
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
