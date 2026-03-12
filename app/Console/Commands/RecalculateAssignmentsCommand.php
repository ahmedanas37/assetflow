<?php

namespace App\Console\Commands;

use App\Domain\Assets\Enums\AssignmentType;
use App\Domain\Assets\Models\Asset;
use Illuminate\Console\Command;

class RecalculateAssignmentsCommand extends Command
{
    protected $signature = 'assetflow:recalculate-assignments {--chunk=200 : Number of assets per batch}';

    protected $description = 'Sync assigned_to_user_id based on active assignments.';

    public function handle(): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $updated = 0;

        Asset::query()
            ->with('activeAssignment')
            ->chunkById($chunkSize, function ($assets) use (&$updated): void {
                foreach ($assets as $asset) {
                    $assignment = $asset->activeAssignment;
                    $assignedUserId = null;

                    if ($assignment && $assignment->assigned_to_type === AssignmentType::User) {
                        $assignedUserId = $assignment->assigned_to_id;
                    }

                    if ($asset->assigned_to_user_id !== $assignedUserId) {
                        $asset->assigned_to_user_id = $assignedUserId;
                        $asset->save();
                        $updated++;
                    }
                }
            });

        $this->info("Updated {$updated} assets.");

        return self::SUCCESS;
    }
}
