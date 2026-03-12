<?php

namespace App\Filament\Resources;

use App\Domain\Accessories\Models\Accessory;
use App\Domain\Accessories\Services\AccessoryAssignmentService;
use App\Domain\Assets\Enums\AssignmentType;
use App\Domain\Inventory\Enums\CategoryType;
use App\Domain\Inventory\Models\Category;
use App\Domain\Inventory\Models\Manufacturer;
use App\Domain\Locations\Models\Location;
use App\Domain\Vendors\Models\Vendor;
use App\Filament\Resources\AccessoryResource\Pages;
use App\Filament\Resources\AccessoryResource\RelationManagers\AssignmentsRelationManager;
use App\Support\EmployeeQuickCreate;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AccessoryResource extends Resource
{
    protected static ?string $model = Accessory::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Grid::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(150),
                        Select::make('category_id')
                            ->label('Category')
                            ->options(fn () => Category::query()
                                ->where('type', CategoryType::Accessory->value)
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('manufacturer_id')
                            ->label('Manufacturer')
                            ->options(fn () => Manufacturer::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                        Select::make('vendor_id')
                            ->label('Vendor')
                            ->options(fn () => Vendor::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                        Select::make('location_id')
                            ->label('Location')
                            ->options(fn () => Location::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                        TextInput::make('model_number')
                            ->label('Model Number')
                            ->maxLength(100),
                        TextInput::make('quantity_total')
                            ->label('Total Quantity')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                        TextInput::make('reorder_threshold')
                            ->label('Reorder Threshold')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Optional low stock alert threshold.'),
                        Placeholder::make('quantity_available_display')
                            ->label('Available')
                            ->content(fn (?Accessory $record) => $record?->quantity_available ?? 0)
                            ->visible(fn (string $context): bool => $context !== 'create'),
                        Placeholder::make('quantity_checked_out_display')
                            ->label('Checked Out')
                            ->content(fn (?Accessory $record) => $record?->quantity_checked_out ?? 0)
                            ->visible(fn (string $context): bool => $context !== 'create'),
                        FileUpload::make('image_path')
                            ->label('Photo')
                            ->disk('private')
                            ->directory('accessories/photos')
                            ->visibility('private')
                            ->image()
                            ->maxSize(4096),
                    ]),
                Textarea::make('notes')
                    ->rows(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->toggleable(),
                TextColumn::make('manufacturer.name')
                    ->label('Manufacturer')
                    ->toggleable(),
                TextColumn::make('location.name')
                    ->label('Location')
                    ->toggleable(),
                TextColumn::make('quantity_total')
                    ->label('Total')
                    ->sortable(),
                TextColumn::make('quantity_available')
                    ->label('Available')
                    ->color(fn (Accessory $record): ?string => $record->reorder_threshold !== null
                        && $record->quantity_available <= $record->reorder_threshold
                            ? 'danger'
                            : null)
                    ->sortable(),
                TextColumn::make('quantity_checked_out')
                    ->label('Checked Out')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(fn () => Category::query()
                        ->where('type', CategoryType::Accessory->value)
                        ->orderBy('name')
                        ->pluck('name', 'id')),
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->options(fn () => Location::query()->orderBy('name')->pluck('name', 'id')),
                Filter::make('low_stock')
                    ->label('Low Stock')
                    ->query(fn (Builder $query) => $query
                        ->whereNotNull('reorder_threshold')
                        ->whereColumn('quantity_available', '<=', 'reorder_threshold')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Action::make('checkout')
                    ->label('Check-out')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->visible(fn (Accessory $record): bool => auth()->user()?->can('checkout', $record) ?? false)
                    ->authorize(fn (Accessory $record): bool => auth()->user()?->can('checkout', $record) ?? false)
                    ->disabled(fn (Accessory $record): bool => $record->quantity_available <= 0)
                    ->form([
                        Select::make('assigned_to_type')
                            ->label('Assign To')
                            ->options([
                                AssignmentType::User->value => 'User',
                                AssignmentType::Employee->value => 'Employee',
                                AssignmentType::Location->value => 'Location',
                            ])
                            ->default(AssignmentType::Employee->value)
                            ->required()
                            ->reactive(),
                        Select::make('assigned_to_user_id')
                            ->label('User')
                            ->options(fn () => \App\Models\User::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => $get('assigned_to_type') === AssignmentType::User->value)
                            ->required(fn (Get $get): bool => $get('assigned_to_type') === AssignmentType::User->value),
                        Select::make('assigned_to_employee_id')
                            ->label('Employee')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => EmployeeQuickCreate::searchResults($search))
                            ->getOptionLabelUsing(fn ($value): ?string => EmployeeQuickCreate::optionLabel($value))
                            ->placeholder('Search employees...')
                            ->createOptionForm(EmployeeQuickCreate::form())
                            ->createOptionUsing(fn (array $data): int => EmployeeQuickCreate::create($data))
                            ->visible(fn (Get $get): bool => $get('assigned_to_type') === AssignmentType::Employee->value)
                            ->required(fn (Get $get): bool => $get('assigned_to_type') === AssignmentType::Employee->value),
                        Select::make('assigned_to_location_id')
                            ->label('Location')
                            ->options(fn () => Location::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => $get('assigned_to_type') === AssignmentType::Location->value)
                            ->required(fn (Get $get): bool => $get('assigned_to_type') === AssignmentType::Location->value),
                        TextInput::make('assigned_to_label')
                            ->label(fn (Get $get): string => $get('assigned_to_type') === AssignmentType::Location->value
                                ? 'Cubicle / System Name'
                                : 'System Name (optional)')
                            ->required(fn (Get $get): bool => $get('assigned_to_type') === AssignmentType::Location->value)
                            ->maxLength(100),
                        TextInput::make('quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(fn (Accessory $record): int => $record->quantity_available)
                            ->required()
                            ->helperText(fn (Accessory $record): string => 'Available: '.$record->quantity_available),
                        DatePicker::make('due_at')
                            ->label('Due Date'),
                        Textarea::make('notes')
                            ->rows(2),
                    ])
                    ->action(function (Accessory $record, array $data): void {
                        $service = app(AccessoryAssignmentService::class);
                        $type = AssignmentType::from($data['assigned_to_type']);
                        $assignedToId = match ($type) {
                            AssignmentType::User => (int) $data['assigned_to_user_id'],
                            AssignmentType::Employee => (int) $data['assigned_to_employee_id'],
                            AssignmentType::Location => (int) $data['assigned_to_location_id'],
                        };

                        $assignment = $service->checkout(
                            accessory: $record,
                            type: $type,
                            assignedToId: $assignedToId,
                            actor: auth()->user(),
                            quantity: (int) $data['quantity'],
                            dueAt: $data['due_at'] ?? null,
                            notes: $data['notes'] ?? null,
                            assignedToLabel: $data['assigned_to_label'] ?? null,
                        );

                        $assignedTo = $assignment->assigned_to_name
                            ?? ($data['assigned_to_label'] ?? 'Assigned');

                        Notification::make()
                            ->title('Accessory assigned')
                            ->body("{$record->name} assigned to {$assignedTo}.")
                            ->success()
                            ->send();
                    }),
                Action::make('add_stock')
                    ->label('Add Stock')
                    ->icon('heroicon-o-plus-circle')
                    ->visible(fn (Accessory $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->authorize(fn (Accessory $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->form([
                        TextInput::make('quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                    ])
                    ->action(function (Accessory $record, array $data): void {
                        $quantity = (int) $data['quantity'];

                        DB::transaction(function () use ($record, $quantity): void {
                            $accessory = Accessory::query()->lockForUpdate()->findOrFail($record->id);
                            $accessory->quantity_total += $quantity;
                            $accessory->quantity_available += $quantity;
                            $accessory->save();
                        });

                        Notification::make()
                            ->title('Stock updated')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('delete accessories') ?? false)
                    ->authorize(fn (): bool => auth()->user()?->can('delete accessories') ?? false),
            ])
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [
            AssignmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccessories::route('/'),
            'create' => Pages\CreateAccessory::route('/create'),
            'view' => Pages\ViewAccessory::route('/{record}'),
            'edit' => Pages\EditAccessory::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['category', 'manufacturer', 'location']);
    }
}
