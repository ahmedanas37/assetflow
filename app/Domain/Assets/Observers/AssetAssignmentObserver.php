<?php

namespace App\Domain\Assets\Observers;

use App\Domain\Assets\Enums\AssignmentType;
use App\Domain\Assets\Models\AssetAssignment;
use App\Domain\Audits\Services\AuditLogger;

class AssetAssignmentObserver
{
    public function saving(AssetAssignment $assignment): void
    {
        $assignment->is_active = $assignment->returned_at === null;
        $assignment->active_asset_id = $assignment->is_active ? $assignment->asset_id : null;
    }

    public function created(AssetAssignment $assignment): void
    {
        if ($assignment->asset) {
            AuditLogger::log($assignment->asset, 'checked_out', [], [
                'assigned_to_type' => $assignment->assigned_to_type?->value ?? $assignment->assigned_to_type,
                'assigned_to_id' => $assignment->assigned_to_id,
                'assigned_to_label' => $assignment->assigned_to_label,
                'assigned_at' => optional($assignment->assigned_at)->toDateTimeString(),
                'due_at' => optional($assignment->due_at)->toDateTimeString(),
                'notes' => $assignment->notes,
            ]);
        }

        $this->syncAssignedUser($assignment);
    }

    public function updated(AssetAssignment $assignment): void
    {
        if ($assignment->wasChanged('returned_at') && $assignment->returned_at) {
            if ($assignment->asset) {
                AuditLogger::log($assignment->asset, 'checked_in', [
                    'returned_at' => null,
                ], [
                    'returned_at' => $assignment->returned_at->toDateTimeString(),
                    'return_condition' => $assignment->return_condition?->value ?? $assignment->return_condition,
                    'notes' => $assignment->notes,
                ]);
            }
        }

        $this->syncAssignedUser($assignment);
    }

    public function deleted(AssetAssignment $assignment): void
    {
        $this->syncAssignedUser($assignment);
    }

    private function syncAssignedUser(AssetAssignment $assignment): void
    {
        $asset = $assignment->asset;

        if (! $asset) {
            return;
        }

        $activeAssignment = AssetAssignment::query()
            ->where('asset_id', $asset->id)
            ->where('is_active', true)
            ->latest('assigned_at')
            ->first();

        if ($activeAssignment && $activeAssignment->assigned_to_type === AssignmentType::User) {
            $asset->assigned_to_user_id = $activeAssignment->assigned_to_id;
        } else {
            $asset->assigned_to_user_id = null;
        }

        $asset->save();
    }
}
