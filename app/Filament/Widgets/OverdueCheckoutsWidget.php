<?php

namespace App\Filament\Widgets;

use App\Domain\Assets\Models\AssetAssignment;
use App\Domain\Reports\Models\ReportMetric;
use App\Filament\Resources\AssetAssignmentResource;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class OverdueCheckoutsWidget extends BaseWidget
{
    protected static ?int $sort = 35;

    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        return Cache::remember('widget.overdue_checkouts', now()->addSeconds(60), function (): array {
            $overdue = ReportMetric::getValue('assignments.overdue')
                ?? AssetAssignment::query()
                    ->where('is_active', true)
                    ->whereNotNull('due_at')
                    ->where('due_at', '<', now())
                    ->count();

            return [
                Stat::make('Overdue Checkouts', $overdue)
                    ->icon('heroicon-o-exclamation-triangle')
                    ->description($overdue > 0 ? 'Action needed' : 'No overdue items')
                    ->color($overdue > 0 ? 'danger' : 'success')
                    ->url(AssetAssignmentResource::getUrl()),
            ];
        });
    }
}
