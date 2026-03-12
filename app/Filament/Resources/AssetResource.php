<?php

namespace App\Filament\Resources;

use App\Domain\Assets\Enums\AssetCondition;
use App\Domain\Assets\Enums\AssignmentType;
use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\StatusLabel;
use App\Domain\Assets\Services\AssignmentService;
use App\Domain\Inventory\Models\AssetModel;
use App\Domain\Inventory\Models\Category;
use App\Domain\Locations\Models\Location;
use App\Domain\Vendors\Models\Vendor;
use App\Filament\Exports\AssetExporter;
use App\Filament\Resources\AssetResource\Pages;
use App\Filament\Resources\AssetResource\RelationManagers\AssignmentsRelationManager;
use App\Filament\Resources\AssetResource\RelationManagers\AttachmentsRelationManager;
use App\Filament\Resources\AssetResource\RelationManagers\AuditLogsRelationManager;
use App\Filament\Resources\AssetResource\RelationManagers\MaintenanceLogsRelationManager;
use App\Services\PortalSettings;
use App\Support\EmployeeQuickCreate;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Assets';

    protected static ?string $recordTitleAttribute = 'asset_tag';

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'asset_tag',
            'serial',
            'assetModel.name',
            'assignedToUser.name',
            'activeAssignment.assignedToEmployee.name',
            'activeAssignment.assignedToLocation.name',
            'location.name',
        ];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Model' => $record->assetModel?->name,
            'Status' => $record->statusLabel?->name,
            'Assigned To' => $record->assigned_to_display ?? 'Unassigned',
            'Location' => $record->location?->name,
        ];
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Grid::make()
                    ->columns(2)
                    ->schema([
                        Toggle::make('auto_generate_tag')
                            ->label('Auto-generate asset tag')
                            ->default(true)
                            ->dehydrated(false)
                            ->reactive()
                            ->visible(fn (string $context): bool => $context === 'create'),
                        TextInput::make('asset_tag')
                            ->label('Asset Tag')
                            ->required(fn (Get $get, string $context): bool => $context === 'edit' || ! $get('auto_generate_tag'))
                            ->disabled(fn (Get $get, string $context): bool => $context === 'create' && (bool) $get('auto_generate_tag'))
                            ->dehydrated(fn (Get $get, string $context): bool => $context === 'edit' || ! $get('auto_generate_tag'))
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),
                        TextInput::make('serial')
                            ->label('Serial')
                            ->unique(ignoreRecord: true)
                            ->maxLength(100),
                        Select::make('asset_model_id')
                            ->label('Model')
                            ->options(fn () => AssetModel::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (Set $set, ?int $state): void {
                                $model = $state ? AssetModel::find($state) : null;
                                $set('category_id', $model?->category_id);
                            }),
                        Select::make('category_id')
                            ->label('Category')
                            ->options(fn () => Category::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('status_label_id')
                            ->label('Status')
                            ->options(fn () => StatusLabel::query()->orderBy('sort_order')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->default(fn () => StatusLabel::query()->where('is_default', true)->value('id'))
                            ->required(),
                        Select::make('location_id')
                            ->label('Home Location')
                            ->options(fn () => Location::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('vendor_id')
                            ->label('Vendor')
                            ->options(fn () => Vendor::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                        DatePicker::make('purchase_date')
                            ->label('Induction Date'),
                        TextInput::make('purchase_cost')
                            ->label('Purchase Cost')
                            ->numeric()
                            ->prefix('PKR'),
                        DatePicker::make('warranty_end_date')
                            ->label('Warranty End Date'),
                        FileUpload::make('image_path')
                            ->label('Photo')
                            ->disk('private')
                            ->directory('assets/photos')
                            ->visibility('private')
                            ->image()
                            ->maxSize(4096),
                        Placeholder::make('assigned_to_user')
                            ->label('Assigned To')
                            ->content(fn (?Asset $record) => $record?->assigned_to_display ?? 'Unassigned')
                            ->visible(fn (string $context): bool => $context !== 'create'),
                    ]),
                Textarea::make('notes')
                    ->rows(3),
                KeyValue::make('custom_fields')
                    ->label('Custom Fields')
                    ->addable()
                    ->deletable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Photo')
                    ->getStateUsing(fn (Asset $record): ?string => $record->image_path
                        ? route('assetflow.assets.photo', $record)
                        : null)
                    ->square()
                    ->size(36)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('asset_tag')
                    ->label('Asset Tag')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('serial')
                    ->label('Serial')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('assetModel.name')
                    ->label('Model')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->toggleable(),
                TextColumn::make('statusLabel.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (Asset $record): string => $record->statusLabel?->deployable ? 'success' : 'gray')
                    ->sortable(),
                TextColumn::make('location.name')
                    ->label('Home Location')
                    ->toggleable(),
                TextColumn::make('assignedToUser.name')
                    ->label('Assigned To')
                    ->getStateUsing(fn (Asset $record) => $record->assigned_to_display)
                    ->toggleable(),
                TextColumn::make('warranty_end_date')
                    ->label('Warranty')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('alerts')
                    ->label('Alerts')
                    ->badge()
                    ->color('danger')
                    ->getStateUsing(function (Asset $record): ?string {
                        $alerts = [];

                        if ($record->warranty_end_date && $record->warranty_end_date->isBefore(now()->addDays(30))) {
                            $alerts[] = 'Warranty';
                        }

                        if (
                            $record->activeAssignment &&
                            $record->activeAssignment->due_at &&
                            $record->activeAssignment->due_at->isPast()
                        ) {
                            $alerts[] = 'Overdue';
                        }

                        if ($record->assignedToUser && $record->assignedToUser->status?->value === 'inactive') {
                            $alerts[] = 'Inactive User';
                        }
                        if (
                            $record->activeAssignment?->assignedToEmployee &&
                            $record->activeAssignment->assignedToEmployee->status?->value === 'inactive'
                        ) {
                            $alerts[] = 'Inactive Employee';
                        }

                        return empty($alerts) ? null : implode(', ', $alerts);
                    })
                    ->toggleable(),
                IconColumn::make('activeAssignment')
                    ->label('Checked Out')
                    ->boolean(fn (Asset $record): bool => $record->activeAssignment !== null)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status_label_id')
                    ->label('Status')
                    ->relationship('statusLabel', 'name'),
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name'),
                SelectFilter::make('asset_model_id')
                    ->label('Model')
                    ->relationship('assetModel', 'name'),
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'name'),
                TernaryFilter::make('assigned')
                    ->label('Assigned')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('activeAssignment'),
                        false: fn (Builder $query) => $query->whereDoesntHave('activeAssignment'),
                    ),
                Filter::make('warranty_expiring')
                    ->label('Warranty Expiring')
                    ->form([
                        Select::make('days')
                            ->options([
                                30 => '30 days',
                                60 => '60 days',
                                90 => '90 days',
                            ])
                            ->placeholder('Select window'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['days']) {
                            return $query;
                        }

                        $end = Carbon::now()->addDays((int) $data['days']);

                        return $query
                            ->whereNotNull('warranty_end_date')
                            ->whereDate('warranty_end_date', '<=', $end->toDateString());
                    }),
                Filter::make('overdue')
                    ->label('Overdue Checkouts')
                    ->query(function (Builder $query): Builder {
                        return $query->whereHas('activeAssignment', function (Builder $assignmentQuery): void {
                            $assignmentQuery->whereNotNull('due_at')->where('due_at', '<', now());
                        });
                    }),
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Action::make('checkout')
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
                Action::make('transfer')
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
                Action::make('checkin')
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
                Action::make('mark_repair')
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
                Action::make('retire')
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
                Action::make('print_label')
                    ->label('Print Label')
                    ->icon('heroicon-o-qr-code')
                    ->url(fn (Asset $record) => route('assetflow.labels.single', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (): bool => auth()->user()?->can('print labels') ?? false)
                    ->authorize(fn (): bool => auth()->user()?->can('print labels') ?? false),
                Action::make('delivery_receipt')
                    ->label('Delivery Receipt')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (Asset $record) => route('assetflow.receipts.single', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (): bool => auth()->user()?->can('print labels') ?? false)
                    ->authorize(fn (): bool => auth()->user()?->can('print labels') ?? false),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('delete assets') ?? false)
                    ->authorize(fn (): bool => auth()->user()?->can('delete assets') ?? false),
                ExportBulkAction::make()
                    ->exporter(AssetExporter::class)
                    ->label('Export CSV')
                    ->visible(fn (): bool => auth()->user()?->can('export assets') ?? false)
                    ->authorize(fn (): bool => auth()->user()?->can('export assets') ?? false),
                Tables\Actions\BulkAction::make('print_labels')
                    ->label('Print Labels')
                    ->icon('heroicon-o-qr-code')
                    ->visible(fn (): bool => auth()->user()?->can('print labels') ?? false)
                    ->authorize(fn (): bool => auth()->user()?->can('print labels') ?? false)
                    ->action(function (Collection $records) {
                        $ids = $records->pluck('id')->implode(',');

                        return redirect()->to(route('assetflow.labels.batch', ['ids' => $ids]));
                    })
                    ->requiresConfirmation(),
                Tables\Actions\BulkAction::make('delivery_receipts')
                    ->label('Delivery Receipts')
                    ->icon('heroicon-o-document-text')
                    ->visible(fn (): bool => auth()->user()?->can('print labels') ?? false)
                    ->authorize(fn (): bool => auth()->user()?->can('print labels') ?? false)
                    ->action(function (Collection $records) {
                        $ids = $records->pluck('id')->implode(',');

                        return redirect()->to(route('assetflow.receipts.batch', ['ids' => $ids]));
                    })
                    ->requiresConfirmation(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(AssetExporter::class)
                    ->label('Export CSV')
                    ->visible(fn (): bool => auth()->user()?->can('export assets') ?? false)
                    ->authorize(fn (): bool => auth()->user()?->can('export assets') ?? false),
                Tables\Actions\Action::make('import_assets')
                    ->label('Import CSV')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->url(fn () => Pages\ImportAssets::getUrl())
                    ->visible(fn (): bool => auth()->user()?->can('import assets') ?? false)
                    ->authorize(fn (): bool => auth()->user()?->can('import assets') ?? false),
            ])
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->defaultSort('asset_tag');
    }

    public static function getRelations(): array
    {
        return [
            AssignmentsRelationManager::class,
            MaintenanceLogsRelationManager::class,
            AttachmentsRelationManager::class,
            AuditLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssets::route('/'),
            'create' => Pages\CreateAsset::route('/create'),
            'view' => Pages\ViewAsset::route('/{record}'),
            'edit' => Pages\EditAsset::route('/{record}/edit'),
            'import' => Pages\ImportAssets::route('/import'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'assetModel',
                'category',
                'statusLabel',
                'location',
                'assignedToUser',
                'activeAssignment.assignedToUser',
                'activeAssignment.assignedToEmployee',
                'activeAssignment.assignedToLocation',
            ])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
