<?php

namespace App\Filament\Resources\AccessoryResource\Pages;

use App\Domain\Accessories\Models\Accessory;
use App\Domain\Accessories\Services\AccessoryAssignmentService;
use App\Domain\Assets\Enums\AssignmentType;
use App\Domain\Locations\Models\Location;
use App\Filament\Resources\AccessoryResource;
use App\Support\EmployeeQuickCreate;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewAccessory extends ViewRecord
{
    protected static string $resource = AccessoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('checkout')
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
            Actions\Action::make('add_stock')
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
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Summary')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name')->label('Name'),
                        TextEntry::make('category.name')->label('Category')->placeholder('-'),
                        TextEntry::make('manufacturer.name')->label('Manufacturer')->placeholder('-'),
                        TextEntry::make('vendor.name')->label('Vendor')->placeholder('-'),
                        TextEntry::make('location.name')->label('Location')->placeholder('-'),
                        TextEntry::make('model_number')->label('Model Number')->placeholder('-'),
                        TextEntry::make('quantity_total')->label('Total Quantity'),
                        TextEntry::make('quantity_available')->label('Available'),
                        TextEntry::make('quantity_checked_out')->label('Checked Out'),
                        TextEntry::make('reorder_threshold')->label('Reorder Threshold')->placeholder('-'),
                    ]),
                Section::make('Notes')
                    ->schema([
                        TextEntry::make('notes')->label('Notes')->placeholder('-'),
                    ]),
            ]);
    }
}
