<?php

namespace App\Filament\Resources\AssetResource\Pages;

use App\Domain\Assets\Enums\AssetCondition;
use App\Domain\Assets\Enums\AssignmentType;
use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\StatusLabel;
use App\Domain\Assets\Services\AssignmentService;
use App\Domain\Locations\Models\Location;
use App\Filament\Resources\AssetResource;
use App\Services\PortalSettings;
use App\Support\EmployeeQuickCreate;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewAsset extends ViewRecord
{
    protected static string $resource = AssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('checkout')
                ->label('Check-out')
                ->icon('heroicon-o-arrow-up-tray')
                ->visible(fn (Asset $record): bool => auth()->user()?->can('checkout', $record) ?? false)
                ->authorize(fn (Asset $record): bool => auth()->user()?->can('checkout', $record) ?? false)
                ->disabled(fn (Asset $record): bool => $record->activeAssignment !== null || ! $record->isDeployable())
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
                    DatePicker::make('due_at')
                        ->label('Due Date'),
                    Textarea::make('notes')
                        ->rows(2),
                ])
                ->action(function (Asset $record, array $data): void {
                    $service = app(AssignmentService::class);
                    $type = AssignmentType::from($data['assigned_to_type']);
                    $assignedToId = match ($type) {
                        AssignmentType::User => (int) $data['assigned_to_user_id'],
                        AssignmentType::Employee => (int) $data['assigned_to_employee_id'],
                        AssignmentType::Location => (int) $data['assigned_to_location_id'],
                    };

                    $assignment = $service->checkout(
                        asset: $record,
                        type: $type,
                        assignedToId: $assignedToId,
                        actor: auth()->user(),
                        dueAt: $data['due_at'] ?? null,
                        notes: $data['notes'] ?? null,
                        assignedToLabel: $data['assigned_to_label'] ?? null,
                    );

                    $assignedTo = $assignment->assigned_to_name
                        ?? ($data['assigned_to_label'] ?? 'Assigned');

                    Notification::make()
                        ->title('Asset assigned')
                        ->body("{$record->asset_tag} assigned to {$assignedTo}.")
                        ->success()
                        ->send();
                }),
            Actions\Action::make('transfer')
                ->label('Transfer')
                ->icon('heroicon-o-arrows-right-left')
                ->visible(fn (Asset $record): bool => (auth()->user()?->can('checkout', $record) ?? false)
                    && app(PortalSettings::class)->getBool('features.asset_transfers', true))
                ->authorize(fn (Asset $record): bool => auth()->user()?->can('checkout', $record) ?? false)
                ->disabled(fn (Asset $record): bool => $record->activeAssignment === null)
                ->form([
                    Select::make('assigned_to_type')
                        ->label('Transfer To')
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
                    DatePicker::make('due_at')
                        ->label('Due Date'),
                    Textarea::make('notes')
                        ->rows(2),
                ])
                ->action(function (Asset $record, array $data): void {
                    $service = app(AssignmentService::class);
                    $type = AssignmentType::from($data['assigned_to_type']);
                    $assignedToId = match ($type) {
                        AssignmentType::User => (int) $data['assigned_to_user_id'],
                        AssignmentType::Employee => (int) $data['assigned_to_employee_id'],
                        AssignmentType::Location => (int) $data['assigned_to_location_id'],
                    };

                    $assignment = $service->transfer(
                        asset: $record,
                        type: $type,
                        assignedToId: $assignedToId,
                        actor: auth()->user(),
                        dueAt: $data['due_at'] ?? null,
                        notes: $data['notes'] ?? null,
                        assignedToLabel: $data['assigned_to_label'] ?? null,
                    );

                    $assignedTo = $assignment->assigned_to_name
                        ?? ($data['assigned_to_label'] ?? 'Assigned');

                    Notification::make()
                        ->title('Asset transferred')
                        ->body("{$record->asset_tag} transferred to {$assignedTo}.")
                        ->success()
                        ->send();
                }),
            Actions\Action::make('checkin')
                ->label('Check-in')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn (Asset $record): bool => auth()->user()?->can('checkin', $record) ?? false)
                ->authorize(fn (Asset $record): bool => auth()->user()?->can('checkin', $record) ?? false)
                ->disabled(fn (Asset $record): bool => $record->activeAssignment === null)
                ->form([
                    Select::make('return_condition')
                        ->options([
                            AssetCondition::Good->value => 'Good',
                            AssetCondition::Fair->value => 'Fair',
                            AssetCondition::Damaged->value => 'Damaged',
                        ])
                        ->required(),
                    Select::make('status_label_id')
                        ->label('Set Status')
                        ->options(fn () => StatusLabel::query()->orderBy('sort_order')->pluck('name', 'id'))
                        ->default(fn () => StatusLabel::query()->where('name', 'In Stock')->value('id')),
                    Textarea::make('notes')
                        ->rows(2),
                ])
                ->action(function (Asset $record, array $data): void {
                    $service = app(AssignmentService::class);

                    $service->checkin(
                        asset: $record,
                        actor: auth()->user(),
                        condition: AssetCondition::from($data['return_condition']),
                        notes: $data['notes'] ?? null,
                        statusLabelId: $data['status_label_id'] ?? null,
                    );

                    Notification::make()
                        ->title('Asset checked in')
                        ->body("{$record->asset_tag} checked in successfully.")
                        ->success()
                        ->send();
                }),
            Actions\Action::make('mark_repair')
                ->label('Mark Repair')
                ->icon('heroicon-o-wrench')
                ->visible(fn (Asset $record): bool => auth()->user()?->can('update', $record) ?? false)
                ->authorize(fn (Asset $record): bool => auth()->user()?->can('update', $record) ?? false)
                ->requiresConfirmation()
                ->action(function (Asset $record): void {
                    $repair = StatusLabel::query()->where('name', 'Repair')->first();
                    if ($repair) {
                        $record->update(['status_label_id' => $repair->id]);
                    }
                }),
            Actions\Action::make('retire')
                ->label('Retire')
                ->icon('heroicon-o-archive-box')
                ->visible(fn (Asset $record): bool => auth()->user()?->can('update', $record) ?? false)
                ->authorize(fn (Asset $record): bool => auth()->user()?->can('update', $record) ?? false)
                ->requiresConfirmation()
                ->action(function (Asset $record): void {
                    $retired = StatusLabel::query()->where('name', 'Retired')->first();
                    if ($retired) {
                        $record->update(['status_label_id' => $retired->id]);
                    }
                }),
            Actions\Action::make('print_label')
                ->label('Print Label')
                ->icon('heroicon-o-qr-code')
                ->url(fn (Asset $record) => route('assetflow.labels.single', $record))
                ->openUrlInNewTab()
                ->visible(fn (): bool => auth()->user()?->can('print labels') ?? false)
                ->authorize(fn (): bool => auth()->user()?->can('print labels') ?? false),
            Actions\Action::make('export')
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn (Asset $record) => route('assetflow.assets.export', $record))
                ->openUrlInNewTab()
                ->visible(fn (): bool => auth()->user()?->can('export assets') ?? false)
                ->authorize(fn (): bool => auth()->user()?->can('export assets') ?? false),
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Photo')
                    ->visible(fn (?Asset $record): bool => filled($record?->image_path))
                    ->schema([
                        ImageEntry::make('image_path')
                            ->label('Photo')
                            ->state(fn (Asset $record): string => route('assetflow.assets.photo', $record))
                            ->height(220)
                            ->width(220)
                            ->square()
                            ->columnSpan('full'),
                    ]),
                Section::make('Summary')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('asset_tag')->label('Asset Tag'),
                        TextEntry::make('statusLabel.name')->label('Status'),
                        TextEntry::make('assigned_to_display')->label('Assigned To')->default('Unassigned'),
                        TextEntry::make('location.name')->label('Home Location'),
                        TextEntry::make('assetModel.name')->label('Model'),
                        TextEntry::make('category.name')->label('Category'),
                        TextEntry::make('serial')->label('Serial')->placeholder('-'),
                        TextEntry::make('vendor.name')->label('Vendor')->placeholder('-'),
                        TextEntry::make('purchase_date')->label('Induction Date')->date()->placeholder('-'),
                        TextEntry::make('purchase_cost')->label('Cost')->money('PKR')->placeholder('-'),
                        TextEntry::make('warranty_end_date')->label('Warranty End')->date()->placeholder('-'),
                    ]),
                Section::make('Notes')
                    ->schema([
                        TextEntry::make('notes')->label('Notes')->placeholder('-'),
                    ]),
                Section::make('Custom Fields')
                    ->visible(fn (?Asset $record): bool => ! empty($record?->custom_fields))
                    ->schema([
                        KeyValueEntry::make('custom_fields')
                            ->label('Custom Fields')
                            ->keyLabel('Field')
                            ->valueLabel('Value')
                            ->state(fn (?Asset $record): array => self::normalizeCustomFields($record?->custom_fields ?? []))
                            ->columnSpan('full'),
                    ]),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function normalizeCustomFields(array $fields): array
    {
        if (empty($fields)) {
            return [];
        }

        return collect($fields)
            ->mapWithKeys(function ($value, $key): array {
                $label = is_int($key) ? 'Field '.($key + 1) : (string) $key;
                $stringValue = match (true) {
                    is_null($value) => '',
                    is_bool($value) => $value ? 'Yes' : 'No',
                    is_scalar($value) => trim((string) $value),
                    default => trim((string) json_encode($value)),
                };

                return [$label => $stringValue];
            })
            ->all();
    }
}
