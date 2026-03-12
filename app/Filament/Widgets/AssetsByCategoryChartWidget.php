<?php

namespace App\Filament\Widgets;

use App\Domain\Inventory\Models\Category;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class AssetsByCategoryChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Assets by Category';

    protected static ?string $description = 'Top categories by asset count';

    protected static ?int $sort = 45;

    protected static ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = true;

    protected static ?array $options = [
        'plugins' => [
            'legend' => ['display' => false],
        ],
        'scales' => [
            'x' => [
                'ticks' => ['precision' => 0],
            ],
        ],
    ];

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        return Cache::remember('widget.assets_by_category', now()->addSeconds(120), function (): array {
            $categories = Category::query()
                ->withCount('assets')
                ->orderByDesc('assets_count')
                ->limit(8)
                ->get();

            return [
                'datasets' => [
                    [
                        'label' => 'Assets',
                        'data' => $categories->pluck('assets_count')->values()->all(),
                        'backgroundColor' => '#1F0961',
                        'borderRadius' => 6,
                    ],
                ],
                'labels' => $categories->pluck('name')->values()->all(),
            ];
        });
    }
}
