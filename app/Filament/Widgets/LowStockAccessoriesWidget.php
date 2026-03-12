<?php

namespace App\Filament\Widgets;

use App\Domain\Accessories\Models\Accessory;
use App\Filament\Resources\AccessoryResource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockAccessoriesWidget extends BaseWidget
{
    protected static ?int $sort = 50;

    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Accessory::query()
                    ->with(['location'])
                    ->whereNotNull('reorder_threshold')
                    ->whereColumn('quantity_available', '<=', 'reorder_threshold')
                    ->orderBy('quantity_available')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Accessory')
                    ->searchable(),
                TextColumn::make('location.name')
                    ->label('Location')
                    ->toggleable(),
                TextColumn::make('quantity_available')
                    ->label('Available')
                    ->color('danger')
                    ->sortable(),
                TextColumn::make('reorder_threshold')
                    ->label('Threshold')
                    ->toggleable(),
            ])
            ->recordUrl(fn (Accessory $record) => AccessoryResource::getUrl('view', ['record' => $record]))
            ->heading('Low Stock Accessories');
    }
}
