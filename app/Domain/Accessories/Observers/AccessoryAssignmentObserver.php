<?php

namespace App\Domain\Accessories\Observers;

use App\Domain\Accessories\Models\AccessoryAssignment;
use App\Domain\Audits\Services\AuditLogger;

class AccessoryAssignmentObserver
{
    public function saving(AccessoryAssignment $assignment): void
    {
        if ($assignment->returned_at) {
            $assignment->is_active = false;

            return;
        }

        $remaining = max(($assignment->quantity ?? 0) - ($assignment->returned_quantity ?? 0), 0);

        if ($remaining === 0) {
            $assignment->is_active = false;
            if (! $assignment->returned_at) {
                $assignment->returned_at = now();
            }
        } else {
            $assignment->is_active = true;
        }
    }

    public function created(AccessoryAssignment $assignment): void
    {
        if (! $assignment->accessory) {
            return;
        }

        AuditLogger::log($assignment->accessory, 'accessory_checked_out', [], [
            'assigned_to_type' => $assignment->assigned_to_type?->value ?? $assignment->assigned_to_type,
            'assigned_to_id' => $assignment->assigned_to_id,
            'assigned_to_label' => $assignment->assigned_to_label,
            'assigned_at' => optional($assignment->assigned_at)->toDateTimeString(),
            'due_at' => optional($assignment->due_at)->toDateTimeString(),
            'quantity' => $assignment->quantity,
            'notes' => $assignment->notes,
        ], $assignment->auditActor());
    }

    public function updated(AccessoryAssignment $assignment): void
    {
        if ($assignment->wasChanged('returned_quantity') || $assignment->wasChanged('returned_at')) {
            if (! $assignment->accessory) {
                return;
            }

            AuditLogger::log($assignment->accessory, 'accessory_checked_in', [
                'returned_quantity' => $assignment->getOriginal('returned_quantity'),
                'returned_at' => $assignment->getOriginal('returned_at'),
            ], [
                'returned_quantity' => $assignment->returned_quantity,
                'returned_at' => optional($assignment->returned_at)->toDateTimeString(),
                'notes' => $assignment->notes,
            ], $assignment->auditActor());
        }
    }
}
