<?php

namespace App\Filament\Widgets;

use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\AssetAssignment;
use App\Domain\Reports\Models\ReportMetric;
use App\Filament\Resources\AssetResource;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class AssetStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 10;

    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        return Cache::remember('widget.asset_stats', now()->addSeconds(60), function (): array {
            $total = ReportMetric::getValue('assets.total') ?? Asset::count();
            $assigned = ReportMetric::getValue('assets.assigned') ?? AssetAssignment::query()->where('is_active', true)->count();
            $inStock = ReportMetric::getValue('assets.in_stock')
                ?? Asset::query()->whereHas('statusLabel', fn ($query) => $query->where('name', 'In Stock'))->count();
            $inRepair = ReportMetric::getValue('assets.in_repair')
                ?? Asset::query()->whereHas('statusLabel', fn ($query) => $query->where('name', 'Repair'))->count();
            $retired = ReportMetric::getValue('assets.retired')
                ?? Asset::query()->whereHas('statusLabel', fn ($query) => $query->where('name', 'Retired'))->count();

            $assignedPercent = $total > 0 ? round(($assigned / $total) * 100) : 0;
            $inStockPercent = $total > 0 ? round(($inStock / $total) * 100) : 0;

            return [
                Stat::make('Total Assets', $total)
                    ->icon('heroicon-o-cube')
                    ->description('All tracked items')
                    ->color('primary')
                    ->url(AssetResource::getUrl()),
                Stat::make('Assigned', $assigned)
                    ->icon('heroicon-o-arrow-up-tray')
                    ->description("{$assignedPercent}% issued")
                    ->color($assigned > 0 ? 'warning' : 'success')
                    ->url(AssetResource::getUrl()),
                Stat::make('In Stock', $inStock)
                    ->icon('heroicon-o-inbox')
                    ->description("{$inStockPercent}% ready")
                    ->color('success')
                    ->url(AssetResource::getUrl()),
                Stat::make('In Repair', $inRepair)
                    ->icon('heroicon-o-wrench')
                    ->description($inRepair > 0 ? 'Needs attention' : 'All clear')
                    ->color($inRepair > 0 ? 'danger' : 'success')
                    ->url(AssetResource::getUrl()),
                Stat::make('Retired', $retired)
                    ->icon('heroicon-o-archive-box')
                    ->description('Lifecycle complete')
                    ->color('gray')
                    ->url(AssetResource::getUrl()),
            ];
        });
    }
}
