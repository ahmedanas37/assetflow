<?php

namespace App\Filament\Pages\Reports;

use App\Domain\Assets\Models\Asset;
use Illuminate\Database\Eloquent\Builder;

class RetiredAssetsReport extends BaseAssetReport
{
    protected static ?string $navigationLabel = 'Retired Assets';

    protected static ?string $title = 'Retired Assets';

    protected static ?int $navigationSort = 50;

    protected function getReportQuery(): Builder
    {
        return Asset::query()->whereHas('statusLabel', fn ($query) => $query->where('name', 'Retired'));
    }
}
