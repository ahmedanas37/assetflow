<?php

namespace App\Filament\Resources;

use App\Domain\Audits\Models\AuditLog;
use App\Filament\Exports\AuditLogExporter;
use App\Filament\Resources\AuditLogResource\Pages;
use App\Services\PortalSettings;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Audit';

    protected static ?string $recordTitleAttribute = 'action';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('action')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('entity_type')
                    ->label('Entity')
                    ->formatStateUsing(fn (string $state) => class_basename($state))
                    ->toggleable(),
                TextColumn::make('entity_id')
                    ->label('Entity ID')
                    ->toggleable(),
                TextColumn::make('actor.name')
                    ->label('Actor')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('ip')
                    ->label('IP')
                    ->toggleable(),
                TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->formatStateUsing(fn (?string $state) => $state ? Str::limit($state, 48) : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('change_count')
                    ->label('Changes')
                    ->getStateUsing(fn (AuditLog $record): int => is_array($record->new_values) ? count($record->new_values) : 0)
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->options(fn () => AuditLog::query()->distinct()->pluck('action', 'action')->toArray())
                    ->searchable(),
                Tables\Filters\SelectFilter::make('entity_type')
                    ->label('Entity')
                    ->options(fn () => AuditLog::query()
                        ->distinct()
                        ->pluck('entity_type', 'entity_type')
                        ->mapWithKeys(fn (string $value) => [$value => class_basename($value)])
                        ->toArray())
                    ->searchable(),
                Tables\Filters\SelectFilter::make('actor_user_id')
                    ->label('Actor')
                    ->relationship('actor', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('From'),
                        \Filament\Forms\Components\DatePicker::make('to')->label('To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '>=', $date))
                            ->when($data['to'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Action::make('details')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Audit Details')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalWidth('3xl')
                    ->modalContent(fn (AuditLog $record) => view('filament.audit-log-details', [
                        'record' => $record,
                    ])),
            ])
            ->bulkActions([])
            ->headerActions([
                ExportAction::make()
                    ->label('Export')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->exporter(AuditLogExporter::class),
                Action::make('evidence_pack')
                    ->label('Evidence Pack')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->visible(fn (): bool => (auth()->user()?->can('view audit logs') ?? false)
                        && app(PortalSettings::class)->getBool('features.evidence_pack', true))
                    ->authorize(fn (): bool => auth()->user()?->can('view audit logs') ?? false)
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('to')->label('To'),
                    ])
                    ->action(function (array $data) {
                        $params = array_filter([
                            'from' => $data['from'] ?? null,
                            'to' => $data['to'] ?? null,
                        ]);

                        return redirect()->to(route('assetflow.audit.evidence-pack', $params));
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistFiltersInSession()
            ->persistSearchInSession();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['actor']);
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
