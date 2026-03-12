<?php

namespace App\Filament\Widgets;

use App\Domain\Accessories\Models\Accessory;
use App\Filament\Resources\AccessoryResource;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class AccessoryStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 15;

    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        return Cache::remember('widget.accessory_stats', now()->addSeconds(60), function (): array {
            $totalTypes = Accessory::count();
            $availableUnits = (int) Accessory::query()->sum('quantity_available');
            $checkedOutUnits = (int) (Accessory::query()
                ->selectRaw('SUM(quantity_total - quantity_available) as checked_out')
                ->value('checked_out') ?? 0);
            $lowStock = Accessory::query()
                ->whereNotNull('reorder_threshold')
                ->whereColumn('quantity_available', '<=', 'reorder_threshold')
                ->count();

            return [
                Stat::make('Accessory Types', $totalTypes)
                    ->icon('heroicon-o-puzzle-piece')
                    ->description('Distinct items')
                    ->color('primary')
                    ->url(AccessoryResource::getUrl()),
                Stat::make('Units Available', $availableUnits)
                    ->icon('heroicon-o-archive-box')
                    ->description('Ready to issue')
                    ->color('success')
                    ->url(AccessoryResource::getUrl()),
                Stat::make('Units Checked Out', $checkedOutUnits)
                    ->icon('heroicon-o-arrow-up-tray')
                    ->description('Currently issued')
                    ->color($checkedOutUnits > 0 ? 'warning' : 'success')
                    ->url(AccessoryResource::getUrl()),
                Stat::make('Low Stock Items', $lowStock)
                    ->icon('heroicon-o-exclamation-triangle')
                    ->description('At or below threshold')
                    ->color($lowStock > 0 ? 'danger' : 'success')
                    ->url(AccessoryResource::getUrl()),
            ];
        });
    }
}
