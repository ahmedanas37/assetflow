<?php

namespace App\Filament\Resources\AssetResource\RelationManagers;

use App\Domain\Maintenance\Enums\MaintenanceStatus;
use App\Domain\Maintenance\Enums\MaintenanceType;
use App\Domain\Maintenance\Models\MaintenanceLog;
use App\Domain\Vendors\Models\Vendor;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MaintenanceLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'maintenanceLogs';

    protected static ?string $title = 'Maintenance';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
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
            DatePicker::make('start_date')->required(),
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

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst((string) $state)),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst((string) $state)),
                TextColumn::make('start_date')->date()->sortable(),
                TextColumn::make('end_date')->date()->toggleable(),
                TextColumn::make('cost')->money('PKR')->toggleable(),
                TextColumn::make('vendor.name')->label('Vendor')->toggleable(),
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
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('New Maintenance')
                    ->authorize(fn () => auth()->user()?->can('create maintenance') ?? false),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->authorize(fn (MaintenanceLog $record) => auth()->user()?->can('update', $record) ?? false),
                Tables\Actions\Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (MaintenanceLog $record) => $record->status === MaintenanceStatus::Open)
                    ->authorize(fn (MaintenanceLog $record) => auth()->user()?->can('close', $record) ?? false)
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
                    ->authorize(fn () => auth()->user()?->can('delete maintenance') ?? false),
            ])
            ->modifyQueryUsing(fn ($query) => $query->with(['vendor']));
    }
}
