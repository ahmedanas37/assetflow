<?php

namespace App\Filament\Pages;

use App\Services\PortalSettings;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;
use Illuminate\Support\Facades\Schema;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        $performanceMode = false;

        if (Schema::hasTable('app_settings')) {
            $performanceMode = app(PortalSettings::class)->getBool('performance.mode', false);
        }

        if ($performanceMode) {
            return [
                AccountWidget::class,
                \App\Filament\Widgets\DashboardIntroWidget::class,
                \App\Filament\Widgets\AssetStatsOverviewWidget::class,
                \App\Filament\Widgets\AccessoryStatsOverviewWidget::class,
                \App\Filament\Widgets\OverdueCheckoutsWidget::class,
                \App\Filament\Widgets\WarrantyExpiringWidget::class,
                \App\Filament\Widgets\RecentlyUpdatedAssetsWidget::class,
            ];
        }

        return [
            AccountWidget::class,
            \App\Filament\Widgets\DashboardIntroWidget::class,
            \App\Filament\Widgets\OperationalInsightsWidget::class,
            \App\Filament\Widgets\AssetStatsOverviewWidget::class,
            \App\Filament\Widgets\AccessoryStatsOverviewWidget::class,
            \App\Filament\Widgets\WarrantyExpiringWidget::class,
            \App\Filament\Widgets\OverdueCheckoutsWidget::class,
            \App\Filament\Widgets\AssetsByStatusChartWidget::class,
            \App\Filament\Widgets\AssetsByCategoryChartWidget::class,
            \App\Filament\Widgets\LowStockAccessoriesWidget::class,
            \App\Filament\Widgets\AssignmentsDueSoonWidget::class,
            \App\Filament\Widgets\RecentActivityWidget::class,
            \App\Filament\Widgets\RecentlyUpdatedAssetsWidget::class,
        ];
    }
}
