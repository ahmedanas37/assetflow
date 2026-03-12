<?php

namespace App\Filament\Pages\Reports;

use App\Domain\Assets\Models\Asset;
use Illuminate\Database\Eloquent\Builder;

class AssetsInRepairReport extends BaseAssetReport
{
    protected static ?string $navigationLabel = 'Assets in Repair';

    protected static ?string $title = 'Assets in Repair';

    protected static ?int $navigationSort = 40;

    protected function getReportQuery(): Builder
    {
        return Asset::query()->whereHas('statusLabel', fn ($query) => $query->where('name', 'Repair'));
    }
}
