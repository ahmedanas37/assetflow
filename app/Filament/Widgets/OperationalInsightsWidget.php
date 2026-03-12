<?php

namespace App\Filament\Widgets;

use App\Domain\Accessories\Models\Accessory;
use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\AssetAssignment;
use App\Domain\Maintenance\Enums\MaintenanceStatus;
use App\Domain\Maintenance\Models\MaintenanceLog;
use App\Domain\Reports\Models\ReportMetric;
use App\Filament\Pages\Reports\AssetsInRepairReport;
use App\Filament\Pages\Reports\DuplicateWarningsReport;
use App\Filament\Pages\Reports\MissingSerialsReport;
use App\Filament\Pages\Reports\WarrantyExpiringReport;
use App\Filament\Resources\AccessoryResource;
use App\Filament\Resources\AssetAssignmentResource;
use App\Filament\Resources\MaintenanceLogResource;
use Closure;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class OperationalInsightsWidget extends Widget
{
    protected static string $view = 'filament.widgets.operational-insights-widget';

    protected static ?int $sort = 5;

    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return Cache::remember('widget.operational_insights', now()->addSeconds(60), function (): array {
            $now = now();
            $overdue = $this->metricOrCount('assignments.overdue', fn () => AssetAssignment::query()
                ->where('is_active', true)
                ->whereNotNull('due_at')
                ->where('due_at', '<', $now)
                ->count());
            $dueSoon = $this->metricOrCount('assignments.due_soon', fn () => AssetAssignment::query()
                ->where('is_active', true)
                ->whereNotNull('due_at')
                ->whereBetween('due_at', [$now, $now->copy()->addDays(7)])
                ->count());
            $inRepair = $this->metricOrCount('assets.in_repair', fn () => Asset::query()
                ->whereHas('statusLabel', fn ($query) => $query->where('name', 'Repair'))
                ->count());
            $warranty30 = $this->metricOrCount('assets.warranty_30', fn () => Asset::query()
                ->whereNotNull('warranty_end_date')
                ->whereBetween('warranty_end_date', [$now, $now->copy()->addDays(30)])
                ->count());
            $lowStock = $this->metricOrCount('accessories.low_stock', fn () => Accessory::query()
                ->whereNotNull('reorder_threshold')
                ->whereColumn('quantity_available', '<=', 'reorder_threshold')
                ->count());
            $openMaintenance = $this->metricOrCount('maintenance.open', fn () => MaintenanceLog::query()
                ->where('status', MaintenanceStatus::Open->value)
                ->count());
            $missingSerials = $this->metricOrCount('assets.missing_serials', fn () => Asset::query()->whereNull('serial')->count());
            $duplicateWarnings = $this->metricOrCount('assets.duplicate_warnings', fn () => $this->countDuplicateAssets());

            $cards = [
                $this->buildCard(
                    eyebrow: 'Assignments',
                    label: 'Overdue checkouts',
                    value: $overdue,
                    icon: 'heroicon-o-exclamation-triangle',
                    badge: $overdue > 0 ? 'Action needed' : 'All clear',
                    badgeColor: $overdue > 0 ? 'danger' : 'success',
                    url: AssetAssignmentResource::getUrl(),
                ),
                $this->buildCard(
                    eyebrow: 'Assignments',
                    label: 'Due in 7 days',
                    value: $dueSoon,
                    icon: 'heroicon-o-calendar',
                    badge: $dueSoon > 0 ? 'Follow up' : 'On track',
                    badgeColor: $dueSoon > 0 ? 'warning' : 'success',
                    url: AssetAssignmentResource::getUrl(),
                ),
                $this->buildCard(
                    eyebrow: 'Warranty',
                    label: 'Expiring in 30 days',
                    value: $warranty30,
                    icon: 'heroicon-o-clock',
                    badge: $warranty30 > 0 ? 'Plan renewals' : 'Covered',
                    badgeColor: $warranty30 > 0 ? 'warning' : 'success',
                    url: WarrantyExpiringReport::getUrl(),
                ),
                $this->buildCard(
                    eyebrow: 'Maintenance',
                    label: 'Open maintenance',
                    value: $openMaintenance,
                    icon: 'heroicon-o-wrench',
                    badge: $openMaintenance > 0 ? 'In progress' : 'Clear',
                    badgeColor: $openMaintenance > 0 ? 'warning' : 'success',
                    url: MaintenanceLogResource::getUrl(),
                ),
                $this->buildCard(
                    eyebrow: 'Assets',
                    label: 'In repair',
                    value: $inRepair,
                    icon: 'heroicon-o-wrench-screwdriver',
                    badge: $inRepair > 0 ? 'Needs attention' : 'Healthy',
                    badgeColor: $inRepair > 0 ? 'danger' : 'success',
                    url: AssetsInRepairReport::getUrl(),
                ),
                $this->buildCard(
                    eyebrow: 'Inventory',
                    label: 'Low stock accessories',
                    value: $lowStock,
                    icon: 'heroicon-o-archive-box',
                    badge: $lowStock > 0 ? 'Reorder' : 'Stocked',
                    badgeColor: $lowStock > 0 ? 'danger' : 'success',
                    url: AccessoryResource::getUrl(),
                ),
                $this->buildCard(
                    eyebrow: 'Data quality',
                    label: 'Missing serials',
                    value: $missingSerials,
                    icon: 'heroicon-o-document-magnifying-glass',
                    badge: $missingSerials > 0 ? 'Complete records' : 'Complete',
                    badgeColor: $missingSerials > 0 ? 'warning' : 'success',
                    url: MissingSerialsReport::getUrl(),
                ),
                $this->buildCard(
                    eyebrow: 'Data quality',
                    label: 'Duplicate warnings',
                    value: $duplicateWarnings,
                    icon: 'heroicon-o-shield-exclamation',
                    badge: $duplicateWarnings > 0 ? 'Resolve conflicts' : 'Unique',
                    badgeColor: $duplicateWarnings > 0 ? 'danger' : 'success',
                    url: DuplicateWarningsReport::getUrl(),
                ),
            ];

            return [
                'cards' => $cards,
            ];
        });
    }

    private function metricOrCount(string $key, Closure $fallback): int
    {
        $value = ReportMetric::getValue($key);

        if (is_numeric($value)) {
            return (int) $value;
        }

        return (int) $fallback();
    }

    private function countDuplicateAssets(): int
    {
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

        return Asset::query()
            ->whereIn('asset_tag', $duplicateTags)
            ->orWhereIn('serial', $duplicateSerials)
            ->count();
    }

    private function buildCard(
        string $eyebrow,
        string $label,
        int $value,
        string $icon,
        string $badge,
        string $badgeColor,
        string $url,
    ): array {
        return [
            'eyebrow' => $eyebrow,
            'label' => $label,
            'value' => $value,
            'icon' => $icon,
            'badge' => $badge,
            'badge_color' => $badgeColor,
            'url' => $url,
        ];
    }
}
