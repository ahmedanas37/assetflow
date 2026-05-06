<?php

namespace App\Filament\Resources;

use App\Domain\Accessories\Models\AccessoryAssignment;
use App\Domain\Accessories\Services\AccessoryAssignmentService;
use App\Domain\Assets\Enums\AssignmentType;
use App\Filament\Resources\AccessoryAssignmentResource\Pages;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AccessoryAssignmentResource extends Resource
{
    protected static ?string $model = AccessoryAssignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Assignments';

    protected static ?string $recordTitleAttribute = 'accessory_id';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('accessory.name')
                    ->label('Accessory')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('assigned_to_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state?->value ?? (string) $state)),
                TextColumn::make('assigned_to_name')
                    ->label('Assigned To')
                    ->getStateUsing(fn (AccessoryAssignment $record): ?string => $record->assigned_to_name)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('assignedToUser', fn (Builder $subQuery) => $subQuery->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('assignedToEmployee', fn (Builder $subQuery) => $subQuery->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('assignedToLocation', fn (Builder $subQuery) => $subQuery->where('name', 'like', "%{$search}%"));
                    }),
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
                TextColumn::make('accepted_at')
                    ->label('Accepted')
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
            ->actions([
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
            ->defaultSort('assigned_at', 'desc')
            ->persistFiltersInSession()
            ->persistSearchInSession();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccessoryAssignments::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['accessory', 'assignedToUser', 'assignedToEmployee', 'assignedToLocation', 'assignedBy']);
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
