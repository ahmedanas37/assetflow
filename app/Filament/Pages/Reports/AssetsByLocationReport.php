<?php

namespace App\Filament\Pages\Reports;

use App\Domain\Assets\Models\Asset;
use Illuminate\Database\Eloquent\Builder;

class AssetsByLocationReport extends BaseAssetReport
{
    protected static ?string $navigationLabel = 'Assets by Location';

    protected static ?string $title = 'Assets by Location';

    protected static ?int $navigationSort = 20;

    protected function getReportQuery(): Builder
    {
        return Asset::query()
            ->whereNotNull('location_id')
            ->orderBy('location_id');
    }
}
