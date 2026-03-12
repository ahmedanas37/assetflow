<?php

namespace App\Filament\Widgets;

use App\Domain\Assets\Models\Asset;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentlyUpdatedAssetsWidget extends BaseWidget
{
    protected static ?int $sort = 90;

    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Asset::query()
                ->with([
                    'assetModel',
                    'statusLabel',
                    'location',
                    'activeAssignment.assignedToUser',
                    'activeAssignment.assignedToEmployee',
                    'activeAssignment.assignedToLocation',
                ])
                ->latest('updated_at')
                ->limit(10))
            ->columns([
                TextColumn::make('asset_tag')
                    ->label('Asset Tag')
                    ->searchable(),
                TextColumn::make('assetModel.name')
                    ->label('Model')
                    ->toggleable(),
                TextColumn::make('assignedToUser.name')
                    ->label('Assigned To')
                    ->getStateUsing(fn (Asset $record) => $record->assigned_to_display)
                    ->toggleable(),
                TextColumn::make('statusLabel.name')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('location.name')
                    ->label('Location')
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->dateTimeTooltip(),
            ]);
    }
}
