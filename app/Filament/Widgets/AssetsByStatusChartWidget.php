<?php

namespace App\Filament\Widgets;

use App\Domain\Assets\Models\StatusLabel;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class AssetsByStatusChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Assets by Status';

    protected static ?string $description = 'Deployment posture snapshot';

    protected static ?int $sort = 40;

    protected static ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = true;

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        return Cache::remember('widget.assets_by_status', now()->addSeconds(120), function (): array {
            $statuses = StatusLabel::query()
                ->withCount('assets')
                ->orderBy('sort_order')
                ->get()
                ->filter(fn (StatusLabel $label) => $label->assets_count > 0);

            $labels = $statuses->pluck('name')->values()->all();
            $data = $statuses->pluck('assets_count')->values()->all();
            $colors = $this->resolveColors($statuses->pluck('color')->values()->all(), count($labels));

            return [
                'datasets' => [
                    [
                        'label' => 'Assets',
                        'data' => $data,
                        'backgroundColor' => $colors,
                        'borderWidth' => 0,
                    ],
                ],
                'labels' => $labels,
            ];
        });
    }

    /**
     * @param  array<int, string|null>  $colors
     * @return array<int, string>
     */
    private function resolveColors(array $colors, int $count): array
    {
        $palette = [
            '#1F0961',
            '#175DFD',
            '#63B3FD',
            '#6C3BF7',
            '#C031FD',
            '#FC86FD',
            '#94A3B8',
            '#F59E0B',
            '#10B981',
            '#EF4444',
        ];

        return collect($colors)
            ->map(fn (?string $color, int $index) => $color ?: ($palette[$index % count($palette)]))
            ->pad($count, $palette[0])
            ->values()
            ->all();
    }
}
