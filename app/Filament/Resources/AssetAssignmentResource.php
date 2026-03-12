<?php

namespace App\Filament\Resources;

use App\Domain\Assets\Enums\AssignmentType;
use App\Domain\Assets\Models\AssetAssignment;
use App\Filament\Resources\AssetAssignmentResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AssetAssignmentResource extends Resource
{
    protected static ?string $model = AssetAssignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Assignments';

    protected static ?string $recordTitleAttribute = 'asset_id';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('assignment_action')
                    ->label('Action')
                    ->badge()
                    ->getStateUsing(function (AssetAssignment $record): string {
                        if ($record->transferred_from_id) {
                            return 'Transfer In';
                        }

                        if ($record->transferredTo) {
                            return 'Transfer Out';
                        }

                        return 'Checkout';
                    }),
                TextColumn::make('asset.asset_tag')
                    ->label('Asset Tag')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('asset.assetModel.name')
                    ->label('Model')
                    ->toggleable(),
                TextColumn::make('assigned_to_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state?->value ?? (string) $state)),
                TextColumn::make('assigned_to_name')
                    ->label('Assigned To')
                    ->getStateUsing(fn (AssetAssignment $record): ?string => $record->assigned_to_name)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('assignedToUser', fn (Builder $subQuery) => $subQuery->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('assignedToEmployee', fn (Builder $subQuery) => $subQuery->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('assignedToLocation', fn (Builder $subQuery) => $subQuery->where('name', 'like', "%{$search}%"));
                    }),
                TextColumn::make('assigned_to_label')
                    ->label('Detail')
                    ->toggleable(),
                TextColumn::make('assignedBy.name')
                    ->label('Assigned By')
                    ->toggleable(),
                TextColumn::make('assigned_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('due_at')
                    ->dateTime()
                    ->toggleable(),
                TextColumn::make('returned_at')
                    ->dateTime()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('assigned_to_type')
                    ->options([
                        AssignmentType::User->value => 'User',
                        AssignmentType::Employee->value => 'Employee',
                        AssignmentType::Location->value => 'Location',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Active')
                    ->queries(
                        true: fn (Builder $query) => $query->where('is_active', true),
                        false: fn (Builder $query) => $query->where('is_active', false),
                    ),
                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue')
                    ->query(fn (Builder $query) => $query->whereNotNull('due_at')->where('due_at', '<', now())->whereNull('returned_at')),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('assigned_at', 'desc')
            ->persistFiltersInSession()
            ->persistSearchInSession();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssetAssignments::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['asset.assetModel', 'assignedToUser', 'assignedToEmployee', 'assignedToLocation', 'assignedBy', 'transferredTo']);
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
