<?php

namespace App\Filament\Resources\AssetModelResource\Pages;

use App\Filament\Resources\AssetModelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssetModel extends EditRecord
{
    protected static string $resource = AssetModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
            Actions\ForceDeleteAction::make()
                ->visible(fn ($record): bool => method_exists($record, 'trashed') && $record->trashed()),
        ];
    }
}
