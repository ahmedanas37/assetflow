<?php

namespace App\Filament\Widgets;

use App\Domain\Assets\Models\Asset;
use App\Domain\Reports\Models\ReportMetric;
use App\Filament\Pages\Reports\WarrantyExpiringReport;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class WarrantyExpiringWidget extends BaseWidget
{
    protected static ?int $sort = 30;

    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        return Cache::remember('widget.warranty_expiring', now()->addSeconds(60), function (): array {
            $now = now();

            $in30 = ReportMetric::getValue('assets.warranty_30')
                ?? Asset::query()
                    ->whereNotNull('warranty_end_date')
                    ->whereBetween('warranty_end_date', [$now, $now->copy()->addDays(30)])
                    ->count();

            $in60 = ReportMetric::getValue('assets.warranty_60')
                ?? Asset::query()
                    ->whereNotNull('warranty_end_date')
                    ->whereBetween('warranty_end_date', [$now, $now->copy()->addDays(60)])
                    ->count();

            $in90 = ReportMetric::getValue('assets.warranty_90')
                ?? Asset::query()
                    ->whereNotNull('warranty_end_date')
                    ->whereBetween('warranty_end_date', [$now, $now->copy()->addDays(90)])
                    ->count();

            $reportUrl = WarrantyExpiringReport::getUrl();

            return [
                Stat::make('Warranty 30 Days', $in30)
                    ->description('Expiring soon')
                    ->icon('heroicon-o-clock')
                    ->color($in30 > 0 ? 'warning' : 'success')
                    ->url($reportUrl),
                Stat::make('Warranty 60 Days', $in60)
                    ->description('Mid-term')
                    ->icon('heroicon-o-calendar')
                    ->color($in60 > 0 ? 'warning' : 'success')
                    ->url($reportUrl),
                Stat::make('Warranty 90 Days', $in90)
                    ->description('Planning window')
                    ->icon('heroicon-o-calendar-days')
                    ->color($in90 > 0 ? 'warning' : 'success')
                    ->url($reportUrl),
            ];
        });
    }
}
