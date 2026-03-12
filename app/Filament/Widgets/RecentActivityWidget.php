<?php

namespace App\Filament\Widgets;

use App\Domain\Audits\Models\AuditLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentActivityWidget extends BaseWidget
{
    protected static ?int $sort = 85;

    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('view audit logs') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(AuditLog::query()->with(['actor'])->latest('created_at')->limit(12))
            ->columns([
                TextColumn::make('action')
                    ->badge()
                    ->searchable(),
                TextColumn::make('entity_type')
                    ->label('Entity')
                    ->formatStateUsing(fn (string $state) => class_basename($state))
                    ->toggleable(),
                TextColumn::make('entity_id')
                    ->label('Entity ID')
                    ->toggleable(),
                TextColumn::make('actor.name')
                    ->label('Actor')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('When')
                    ->since()
                    ->dateTimeTooltip(),
            ])
            ->heading('Recent Activity');
    }
}
