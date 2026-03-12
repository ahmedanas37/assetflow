<?php

namespace App\Filament\Widgets;

use App\Domain\Audits\Models\AuditLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class AuditStatsOverviewWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected static bool $isLazy = true;

    public static function canView(): bool
    {
        return auth()->user()?->can('view audit logs') ?? false;
    }

    protected function getStats(): array
    {
        return Cache::remember('widget.audit_stats', now()->addSeconds(60), function (): array {
            $total = AuditLog::query()->count();
            $last24h = AuditLog::query()->where('created_at', '>=', now()->subDay())->count();
            $last7d = AuditLog::query()->where('created_at', '>=', now()->subDays(7))->count();
            $actors30d = AuditLog::query()
                ->where('created_at', '>=', now()->subDays(30))
                ->distinct('actor_user_id')
                ->count('actor_user_id');

            return [
                Stat::make('Total Audit Events', $total),
                Stat::make('Last 24 Hours', $last24h),
                Stat::make('Last 7 Days', $last7d),
                Stat::make('Active Actors (30d)', $actors30d),
            ];
        });
    }
}
