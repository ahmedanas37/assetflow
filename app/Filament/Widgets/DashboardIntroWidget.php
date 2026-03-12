<?php

namespace App\Filament\Widgets;

use App\Domain\Reports\Models\ReportMetric;
use App\Services\PortalSettings;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class DashboardIntroWidget extends Widget
{
    protected static string $view = 'filament.widgets.dashboard-intro-widget';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $settings = app(PortalSettings::class);

        $updatedAt = ReportMetric::query()
            ->where('key', 'assets.total')
            ->value('updated_at');

        $metricsUpdated = $updatedAt
            ? Carbon::parse($updatedAt)->diffForHumans()
            : 'Not yet refreshed';

        return [
            'company' => $settings->companyName(),
            'product' => $settings->productName(),
            'logoUrl' => $settings->logoUrl(),
            'metricsUpdated' => $metricsUpdated,
        ];
    }
}
