<?php

namespace App\Filament\Pages\Reports;

use App\Domain\Assets\Models\Asset;
use Illuminate\Database\Eloquent\Builder;

class MissingSerialsReport extends BaseAssetReport
{
    protected static ?string $navigationLabel = 'Missing Serials';

    protected static ?string $title = 'Missing Serials';

    protected static ?int $navigationSort = 60;

    protected function getReportQuery(): Builder
    {
        return Asset::query()->whereNull('serial');
    }
}
