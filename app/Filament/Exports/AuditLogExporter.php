<?php

namespace App\Filament\Exports;

use App\Domain\Audits\Models\AuditLog;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class AuditLogExporter extends Exporter
{
    protected static ?string $model = AuditLog::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('action')->label('Action'),
            ExportColumn::make('entity_type')->label('Entity Type'),
            ExportColumn::make('entity_id')->label('Entity ID'),
            ExportColumn::make('actor.name')->label('Actor'),
            ExportColumn::make('ip')->label('IP'),
            ExportColumn::make('user_agent')->label('User Agent'),
            ExportColumn::make('old_values')
                ->label('Old Values')
                ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_UNESCAPED_SLASHES) : null),
            ExportColumn::make('new_values')
                ->label('New Values')
                ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_UNESCAPED_SLASHES) : null),
            ExportColumn::make('created_at')->label('Timestamp'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $successful = $export->successful_rows;

        return "{$successful} audit rows exported.";
    }
}
