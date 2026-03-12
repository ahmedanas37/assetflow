<?php

namespace App\Filament\Pages\Reports;

use App\Domain\Assets\Models\Asset;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WarrantyExpiringReport extends BaseAssetReport
{
    protected static ?string $navigationLabel = 'Warranty Expiring';

    protected static ?string $title = 'Warranty Expiring';

    protected static ?int $navigationSort = 10;

    protected function getReportQuery(): Builder
    {
        return Asset::query()->whereNotNull('warranty_end_date');
    }

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->filters([
                SelectFilter::make('warranty_window')
                    ->label('Window')
                    ->options([
                        30 => '30 days',
                        60 => '60 days',
                        90 => '90 days',
                    ])
                    ->default(30)
                    ->query(function (Builder $query, array $data): Builder {
                        $days = (int) ($data['value'] ?? $data['warranty_window'] ?? 30);
                        $end = now()->addDays($days);

                        return $query->whereBetween('warranty_end_date', [now(), $end]);
                    }),
            ]);
    }
}
