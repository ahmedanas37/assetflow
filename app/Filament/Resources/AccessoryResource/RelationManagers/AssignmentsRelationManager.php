<?php

namespace App\Filament\Resources\AccessoryResource\RelationManagers;

use App\Domain\Accessories\Models\AccessoryAssignment;
use App\Domain\Accessories\Services\AccessoryAssignmentService;
use App\Filament\Resources\AccessoryAssignmentResource;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';

    protected static ?string $title = 'Assignments';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('assigned_to_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => ucfirst($state?->value ?? (string) $state))
                    ->badge(),
                TextColumn::make('assigned_to_name')
                    ->label('Assigned To')
                    ->getStateUsing(fn (AccessoryAssignment $record): ?string => $record->assigned_to_name),
                TextColumn::make('assigned_to_label')
                    ->label('Detail')
                    ->toggleable(),
                TextColumn::make('quantity')
                    ->label('Qty'),
                TextColumn::make('returned_quantity')
                    ->label('Returned')
                    ->toggleable(),
                TextColumn::make('remaining_quantity')
                    ->label('Remaining')
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
                TextColumn::make('accepted_at')
                    ->label('Accepted')
                    ->dateTime()
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
            ->actions([
                AccessoryAssignmentResource::acceptanceLinkAction(),
                Tables\Actions\Action::make('checkin')
                    ->label('Check-in')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (AccessoryAssignment $record): bool => $record->is_active && (auth()->user()?->can('checkin accessories') ?? false))
                    ->authorize(fn (): bool => auth()->user()?->can('checkin accessories') ?? false)
                    ->form([
                        TextInput::make('quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(fn (AccessoryAssignment $record): int => $record->remaining_quantity)
                            ->required(),
                        Textarea::make('notes')
                            ->rows(2),
                    ])
                    ->action(function (AccessoryAssignment $record, array $data): void {
                        $service = app(AccessoryAssignmentService::class);

                        $service->checkin(
                            assignment: $record,
                            actor: auth()->user(),
                            quantity: (int) $data['quantity'],
                            notes: $data['notes'] ?? null,
                        );
                    }),
            ])
            ->bulkActions([])
            ->modifyQueryUsing(fn ($query) => $query->with(['assignedToUser', 'assignedToEmployee', 'assignedToLocation', 'assignedBy']));
    }
}
