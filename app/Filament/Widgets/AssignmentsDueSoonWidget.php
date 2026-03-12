<?php

namespace App\Filament\Widgets;

use App\Domain\Assets\Models\AssetAssignment;
use App\Filament\Resources\AssetResource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class AssignmentsDueSoonWidget extends BaseWidget
{
    protected static ?int $sort = 55;

    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $now = now();
        $soon = $now->copy()->addDays(7);

        return $table
            ->query(
                AssetAssignment::query()
                    ->with(['asset.assetModel', 'assignedToUser', 'assignedToEmployee', 'assignedToLocation'])
                    ->where('is_active', true)
                    ->whereNotNull('due_at')
                    ->whereBetween('due_at', [$now, $soon])
                    ->orderBy('due_at')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('asset.asset_tag')
                    ->label('Asset Tag')
                    ->searchable(),
                TextColumn::make('asset.assetModel.name')
                    ->label('Model')
                    ->toggleable(),
                TextColumn::make('assigned_to_name')
                    ->label('Assigned To')
                    ->getStateUsing(fn (AssetAssignment $record) => $record->assigned_to_name),
                TextColumn::make('due_at')
                    ->label('Due')
                    ->date()
                    ->sinceTooltip(),
            ])
            ->recordUrl(fn (AssetAssignment $record) => AssetResource::getUrl('view', ['record' => $record->asset_id]))
            ->heading('Assignments Due Soon (7 Days)');
    }
}
