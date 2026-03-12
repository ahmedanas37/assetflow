<?php

namespace App\Filament\Resources\AssetResource\RelationManagers;

use App\Domain\Assets\Models\AssetAssignment;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';

    protected static ?string $title = 'Assignment History';

    public function table(Table $table): Table
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
                TextColumn::make('assigned_to_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => ucfirst($state?->value ?? (string) $state))
                    ->badge(),
                TextColumn::make('assigned_to_name')
                    ->label('Assigned To')
                    ->getStateUsing(fn (AssetAssignment $record): ?string => $record->assigned_to_name),
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
                TextColumn::make('return_condition')
                    ->label('Condition')
                    ->formatStateUsing(function ($state): ?string {
                        if ($state instanceof \BackedEnum) {
                            $state = $state->value;
                        }

                        return $state ? ucfirst((string) $state) : null;
                    })
                    ->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('active')
                    ->label('Active')
                    ->queries(
                        true: fn ($query) => $query->where('is_active', true),
                        false: fn ($query) => $query->where('is_active', false),
                    ),
            ])
            ->defaultSort('assigned_at', 'desc')
            ->headerActions([])
            ->actions([])
            ->bulkActions([])
            ->modifyQueryUsing(fn ($query) => $query->with([
                'assignedToUser',
                'assignedToEmployee',
                'assignedToLocation',
                'assignedBy',
                'transferredTo',
            ]));
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
