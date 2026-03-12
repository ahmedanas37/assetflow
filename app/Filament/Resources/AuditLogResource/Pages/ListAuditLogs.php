<?php

namespace App\Filament\Resources\AuditLogResource\Pages;

use App\Filament\Resources\AuditLogResource;
use App\Filament\Widgets\AuditStatsOverviewWidget;
use Filament\Resources\Pages\ListRecords;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            AuditStatsOverviewWidget::class,
        ];
    }
}
