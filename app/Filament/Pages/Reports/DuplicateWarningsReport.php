<?php

namespace App\Filament\Pages\Reports;

use App\Domain\Assets\Models\Asset;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DuplicateWarningsReport extends BaseAssetReport
{
    protected static ?string $navigationLabel = 'Duplicate Warnings';

    protected static ?string $title = 'Duplicate Warnings';

    protected static ?int $navigationSort = 80;

    protected function getReportQuery(): Builder
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
            ->orWhereIn('serial', $duplicateSerials);
    }

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->columns([
                TextColumn::make('asset_tag')
                    ->label('Asset Tag')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('serial')
                    ->label('Serial')
                    ->toggleable(),
                TextColumn::make('duplicate_type')
                    ->label('Duplicate Type')
                    ->badge()
                    ->getStateUsing(function (Asset $record): string {
                        $types = [];

                        if ($record->asset_tag && Asset::query()->where('asset_tag', $record->asset_tag)->count() > 1) {
                            $types[] = 'Tag';
                        }

                        if ($record->serial && Asset::query()->where('serial', $record->serial)->count() > 1) {
                            $types[] = 'Serial';
                        }

                        return empty($types) ? 'None' : implode(', ', $types);
                    }),
                TextColumn::make('assetModel.name')
                    ->label('Model')
                    ->toggleable(),
                TextColumn::make('location.name')
                    ->label('Location')
                    ->toggleable(),
            ])
            ->headerActions([
                Tables\Actions\ExportAction::make()
                    ->label('Export CSV')
                    ->exporter(\App\Filament\Exports\AssetExporter::class),
            ]);
    }
}
