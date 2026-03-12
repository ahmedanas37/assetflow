<?php

namespace App\Filament\Resources\AssetResource\Pages;

use App\Domain\Inventory\Models\AssetModel;
use App\Filament\Resources\AssetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAsset extends EditRecord
{
    protected static string $resource = AssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['category_id']) && ! empty($data['asset_model_id'])) {
            $data['category_id'] = AssetModel::query()
                ->whereKey($data['asset_model_id'])
                ->value('category_id');
        }

        return $data;
    }
}
