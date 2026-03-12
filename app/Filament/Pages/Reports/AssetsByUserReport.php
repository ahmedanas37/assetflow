<?php

namespace App\Filament\Pages\Reports;

use App\Domain\Assets\Enums\AssignmentType;
use App\Domain\Assets\Models\Asset;
use Illuminate\Database\Eloquent\Builder;

class AssetsByUserReport extends BaseAssetReport
{
    protected static ?string $navigationLabel = 'Assets by Assignee';

    protected static ?string $title = 'Assets by Assignee';

    protected static ?int $navigationSort = 30;

    protected function getReportQuery(): Builder
    {
        return Asset::query()
            ->whereHas('activeAssignment', function (Builder $query): void {
                $query->whereIn('assigned_to_type', [
                    AssignmentType::User->value,
                    AssignmentType::Employee->value,
                ]);
            })
            ->orderBy('asset_tag');
    }
}
