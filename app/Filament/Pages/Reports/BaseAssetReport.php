<?php

namespace App\Filament\Pages\Reports;

use App\Filament\Exports\AssetExporter;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseAssetReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $view = 'filament.pages.reports.asset-report';

    protected static ?string $navigationGroup = 'Reports';

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->can('view reports') ?? false;
    }

    abstract protected function getReportQuery(): Builder;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getReportQuery()->with([
                'assetModel',
                'statusLabel',
                'location',
                'activeAssignment.assignedToUser',
                'activeAssignment.assignedToEmployee',
                'activeAssignment.assignedToLocation',
            ]))
            ->columns([
                TextColumn::make('asset_tag')
                    ->label('Asset Tag')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('serial')
                    ->label('Serial')
                    ->toggleable(),
                TextColumn::make('assetModel.name')
                    ->label('Model')
                    ->toggleable(),
                TextColumn::make('statusLabel.name')
                    ->label('Status')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('location.name')
                    ->label('Location')
                    ->toggleable(),
                TextColumn::make('assigned_to_display')
                    ->label('Assigned To')
                    ->toggleable(),
                TextColumn::make('warranty_end_date')
                    ->label('Warranty')
                    ->date()
                    ->toggleable(),
            ])
            ->headerActions([
                Tables\Actions\ExportAction::make()
                    ->label('Export CSV')
                    ->exporter(AssetExporter::class),
            ])
            ->defaultSort('asset_tag');
    }
}
