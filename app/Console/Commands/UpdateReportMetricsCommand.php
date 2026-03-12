<?php

namespace App\Console\Commands;

use App\Domain\Accessories\Models\Accessory;
use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\AssetAssignment;
use App\Domain\Maintenance\Enums\MaintenanceStatus;
use App\Domain\Maintenance\Models\MaintenanceLog;
use App\Domain\Reports\Models\ReportMetric;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class UpdateReportMetricsCommand extends Command
{
    protected $signature = 'assetflow:update-metrics';

    protected $description = 'Update cached dashboard metrics.';

    public function handle(): int
    {
        if (! Schema::hasTable('report_metrics')) {
            $this->warn('Report metrics table is not available yet.');

            return self::SUCCESS;
        }

        $this->updateMetrics();

        return self::SUCCESS;
    }

    private function updateMetrics(): void
    {
        $now = now();

        $duplicateTags = Asset::query()
            ->select('asset_tag')
            ->whereNotNull('asset_tag')
            ->groupBy('asset_tag')
            ->havingRaw('count(*) > 1');

        $duplicateSerials = Asset::query()
            ->select('serial')
            ->whereNotNull('serial')
            ->groupBy('serial')
            ->havingRaw('count(*) > 1');

        $metrics = [
            'assets.total' => Asset::count(),
            'assets.assigned' => AssetAssignment::query()->where('is_active', true)->count(),
            'assets.in_stock' => Asset::query()->whereHas('statusLabel', fn ($query) => $query->where('name', 'In Stock'))->count(),
            'assets.in_repair' => Asset::query()->whereHas('statusLabel', fn ($query) => $query->where('name', 'Repair'))->count(),
            'assets.retired' => Asset::query()->whereHas('statusLabel', fn ($query) => $query->where('name', 'Retired'))->count(),
            'assets.missing_serials' => Asset::query()->whereNull('serial')->count(),
            'assets.duplicate_warnings' => Asset::query()
                ->whereIn('asset_tag', $duplicateTags)
                ->orWhereIn('serial', $duplicateSerials)
                ->count(),
            'accessories.total' => Accessory::count(),
            'accessories.units_available' => (int) Accessory::query()->sum('quantity_available'),
            'accessories.units_checked_out' => (int) (Accessory::query()
                ->selectRaw('SUM(quantity_total - quantity_available) as checked_out')
                ->value('checked_out') ?? 0),
            'accessories.low_stock' => Accessory::query()
                ->whereNotNull('reorder_threshold')
                ->whereColumn('quantity_available', '<=', 'reorder_threshold')
                ->count(),
            'maintenance.open' => MaintenanceLog::query()
                ->where('status', MaintenanceStatus::Open->value)
                ->count(),
            'assets.warranty_30' => Asset::query()
                ->whereNotNull('warranty_end_date')
                ->whereBetween('warranty_end_date', [$now, $now->copy()->addDays(30)])
                ->count(),
            'assets.warranty_60' => Asset::query()
                ->whereNotNull('warranty_end_date')
                ->whereBetween('warranty_end_date', [$now, $now->copy()->addDays(60)])
                ->count(),
            'assets.warranty_90' => Asset::query()
                ->whereNotNull('warranty_end_date')
                ->whereBetween('warranty_end_date', [$now, $now->copy()->addDays(90)])
                ->count(),
            'assignments.overdue' => AssetAssignment::query()
                ->where('is_active', true)
                ->whereNotNull('due_at')
                ->where('due_at', '<', $now)
                ->count(),
            'assignments.due_soon' => AssetAssignment::query()
                ->where('is_active', true)
                ->whereNotNull('due_at')
                ->whereBetween('due_at', [$now, $now->copy()->addDays(7)])
                ->count(),
        ];

        foreach ($metrics as $key => $value) {
            ReportMetric::put($key, $value);
        }

        $this->info('Report metrics updated.');
    }
}
