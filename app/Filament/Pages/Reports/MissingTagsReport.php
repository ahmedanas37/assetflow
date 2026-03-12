<?php

namespace App\Filament\Pages\Reports;

use App\Domain\Assets\Models\Asset;
use Illuminate\Database\Eloquent\Builder;

class MissingTagsReport extends BaseAssetReport
{
    protected static ?string $navigationLabel = 'Missing Tags';

    protected static ?string $title = 'Missing Asset Tags';

    protected static ?int $navigationSort = 70;

    protected function getReportQuery(): Builder
    {
        return Asset::query()->whereNull('asset_tag');
    }
}
