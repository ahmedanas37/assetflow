<?php

namespace App\Filament\Resources\StatusLabelResource\Pages;

use App\Filament\Resources\StatusLabelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStatusLabel extends EditRecord
{
    protected static string $resource = StatusLabelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
