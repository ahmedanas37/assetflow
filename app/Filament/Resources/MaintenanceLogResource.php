<?php

namespace App\Filament\Resources;

use App\Domain\Assets\Models\Asset;
use App\Domain\Maintenance\Enums\MaintenanceStatus;
use App\Domain\Maintenance\Enums\MaintenanceType;
use App\Domain\Maintenance\Models\MaintenanceLog;
use App\Domain\Vendors\Models\Vendor;
use App\Filament\Resources\MaintenanceLogResource\Pages;
use App\Filament\Resources\MaintenanceLogResource\RelationManagers\AttachmentsRelationManager;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MaintenanceLogResource extends Resource
{
    protected static ?string $model = MaintenanceLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationGroup = 'Maintenance';

    protected static ?string $recordTitleAttribute = 'asset_id';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Select::make('asset_id')
                    ->label('Asset')
                    ->options(fn () => Asset::query()->orderBy('asset_tag')->pluck('asset_tag', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('type')
                    ->options([
                        MaintenanceType::Repair->value => 'Repair',
                        MaintenanceType::Upgrade->value => 'Upgrade',
                        MaintenanceType::Inspection->value => 'Inspection',
                    ])
                    ->required(),
                Select::make('status')
                    ->options([
                        MaintenanceStatus::Open->value => 'Open',
                        MaintenanceStatus::Closed->value => 'Closed',
                    ])
                    ->default(MaintenanceStatus::Open->value)
                    ->required(),
                DatePicker::make('start_date')
                    ->required(),
                DatePicker::make('end_date'),
                TextInput::make('cost')
                    ->numeric()
                    ->prefix('PKR'),
                Select::make('vendor_id')
                    ->label('Vendor')
                    ->options(fn () => Vendor::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                TextInput::make('performed_by'),
                Textarea::make('notes')->rows(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('asset.asset_tag')
                    ->label('Asset Tag')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst((string) $state)),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst((string) $state)),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->toggleable(),
                TextColumn::make('cost')
                    ->money('PKR')
                    ->toggleable(),
                TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        MaintenanceStatus::Open->value => 'Open',
                        MaintenanceStatus::Closed->value => 'Closed',
                    ]),
                SelectFilter::make('type')
                    ->options([
                        MaintenanceType::Repair->value => 'Repair',
                        MaintenanceType::Upgrade->value => 'Upgrade',
                        MaintenanceType::Inspection->value => 'Inspection',
                    ]),
                SelectFilter::make('vendor_id')
                    ->label('Vendor')
                    ->relationship('vendor', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (MaintenanceLog $record) => $record->status === MaintenanceStatus::Open
                        && (auth()->user()?->can('close maintenance') ?? false))
                    ->authorize(fn (MaintenanceLog $record) => auth()->user()?->can('close maintenance') ?? false)
                    ->requiresConfirmation()
                    ->action(function (MaintenanceLog $record): void {
                        $record->update([
                            'status' => MaintenanceStatus::Closed->value,
                            'end_date' => $record->end_date ?? now()->toDateString(),
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('delete maintenance') ?? false)
                    ->authorize(fn (): bool => auth()->user()?->can('delete maintenance') ?? false),
            ])
            ->persistFiltersInSession()
            ->persistSearchInSession();
    }

    public static function getRelations(): array
    {
        return [
            AttachmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaintenanceLogs::route('/'),
            'create' => Pages\CreateMaintenanceLog::route('/create'),
            'edit' => Pages\EditMaintenanceLog::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['asset', 'vendor']);
    }
}
