<?php

namespace App\Filament\Exports;

use App\Domain\Assets\Models\Asset;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class AssetExporter extends Exporter
{
    protected static ?string $model = Asset::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('asset_tag')->label('Asset Tag'),
            ExportColumn::make('serial')->label('Serial'),
            ExportColumn::make('assetModel.name')->label('Model'),
            ExportColumn::make('category.name')->label('Category'),
            ExportColumn::make('statusLabel.name')->label('Status'),
            ExportColumn::make('location.name')->label('Location'),
            ExportColumn::make('assigned_to_display')->label('Assigned To'),
            ExportColumn::make('purchase_date')->label('Induction Date'),
            ExportColumn::make('purchase_cost')->label('Purchase Cost'),
            ExportColumn::make('vendor.name')->label('Vendor'),
            ExportColumn::make('warranty_end_date')->label('Warranty End'),
            ExportColumn::make('notes')->label('Notes'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $successful = $export->successful_rows;

        return "{$successful} assets exported.";
    }
}
